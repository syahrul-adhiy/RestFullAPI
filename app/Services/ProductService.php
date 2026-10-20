<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ProductService
{
    /**
     * Create a new product with optional attachments and initial stock log within a transaction.
     */
    public function createProduct(
        array $data,
        ?UploadedFile $image,
        ?UploadedFile $document,
        User $user
    ): Product {
        return DB::transaction(function () use ($data, $image, $document, $user) {
            $imagePath = null;
            $documentPath = null;

            if ($image) {
                $imagePath = $image->store('products/images', 'public');
            }

            if ($document) {
                $documentPath = $document->store('products/documents', 'public');
            }

            $product = Product::create([
                'category_id' => $data['category_id'],
                'user_id' => $user->id,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'sku' => strtoupper($data['sku']),
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'stock' => $data['stock'] ?? 0,
                'material' => $data['material'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'image_path' => $imagePath,
                'img' => $imagePath,
                'document_path' => $documentPath,
            ]);

            // Record initial stock movement if stock > 0
            if ($product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => 'in',
                    'quantity' => $product->stock,
                    'previous_stock' => 0,
                    'current_stock' => $product->stock,
                    'notes' => 'Inisialisasi stok awal saat pendaftaran produk.',
                ]);
            }

            return $product->load(['category', 'creator']);
        });
    }

    /**
     * Safely update an existing product with file replacement and transaction protection.
     */
    public function updateProduct(
        Product $product,
        array $data,
        ?UploadedFile $image,
        ?UploadedFile $document
    ): Product {
        return DB::transaction(function () use ($product, $data, $image, $document) {
            if ($image) {
                $oldImage = $product->img ?? $product->image_path;
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }
                $storedPath = $image->store('products/images', 'public');
                $data['image_path'] = $storedPath;
                $data['img'] = $storedPath;
            }

            if ($document) {
                if ($product->document_path && Storage::disk('public')->exists($product->document_path)) {
                    Storage::disk('public')->delete($product->document_path);
                }
                $data['document_path'] = $document->store('products/documents', 'public');
            }

            if (isset($data['sku'])) {
                $data['sku'] = strtoupper($data['sku']);
            }

            $product->update($data);

            return $product->fresh(['category', 'creator']);
        });
    }

    /**
     * Dedicated method for uploading/replacing product image.
     */
    public function uploadProductImage(Product $product, UploadedFile $image): Product
    {
        return DB::transaction(function () use ($product, $image) {
            $oldImage = $product->img ?? $product->image_path;
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }

            $newPath = $image->store('products/images', 'public');
            $product->update([
                'image_path' => $newPath,
                'img' => $newPath,
            ]);

            return $product->fresh(['category', 'creator']);
        });
    }

    /**
     * Adjust product stock with concurrency lock (lockForUpdate) and immutable audit log.
     */
    public function adjustStock(
        Product $product,
        string $type,
        int $quantity,
        ?string $notes,
        User $user
    ): Product {
        return DB::transaction(function () use ($product, $type, $quantity, $notes, $user) {
            // Lock record for update to prevent concurrent race conditions
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            $previousStock = $lockedProduct->stock;
            $newStock = $previousStock;

            switch ($type) {
                case 'in':
                    $newStock = $previousStock + $quantity;
                    break;
                case 'out':
                    if ($previousStock < $quantity) {
                        throw new InvalidArgumentException("Stok tidak mencukupi untuk pengurangan ini. Stok saat ini: {$previousStock}, kuantitas diminta: {$quantity}.");
                    }
                    $newStock = $previousStock - $quantity;
                    break;
                case 'adjustment':
                    $newStock = $quantity;
                    break;
                default:
                    throw new InvalidArgumentException("Tipe mutasi stok '{$type}' tidak didukung.");
            }

            $lockedProduct->stock = $newStock;
            $lockedProduct->save();

            StockMovement::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $newStock,
                'notes' => $notes ?? 'Penyesuaian stok manual oleh pengguna.',
            ]);

            return $lockedProduct->fresh(['category', 'creator', 'stockMovements.user']);
        });
    }

    /**
     * Safely soft-delete a product.
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            return (bool) $product->delete();
        });
    }
}
