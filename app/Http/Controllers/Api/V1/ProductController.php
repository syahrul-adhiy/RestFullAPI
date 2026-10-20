<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\AdjustStockRequest;
use App\Http\Requests\Product\ProductFilterRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UploadImageRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of products with search, filter, and sorting.
     */
    public function index(ProductFilterRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = Product::with(['category', 'creator'])
            ->search($request->input('search'))
            ->filterCategory($request->input('category_id'))
            ->filterStatus($request->input('status'))
            ->filterPriceRange(
                $request->has('min_price') ? (float) $request->input('min_price') : null,
                $request->has('max_price') ? (float) $request->input('max_price') : null
            )
            ->filterInStock($request->input('in_stock'))
            ->applySorting(
                $request->input('sort_by', 'created_at'),
                $request->input('sort_order', 'desc')
            );

        $paginator = $query->paginate($perPage)->appends($request->query());

        $meta = [
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'filters_applied' => array_filter([
                'search' => $request->input('search'),
                'category_id' => $request->input('category_id'),
                'status' => $request->input('status'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'in_stock' => $request->input('in_stock'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
            ], fn($val) => !is_null($val)),
        ];

        return $this->successResponse(
            data: ProductResource::collection($paginator->items()),
            message: 'Daftar produk topi berhasil diambil.',
            statusCode: 200,
            meta: $meta
        );
    }

    /**
     * Store a newly created product with transactional and file upload safety.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        Gate::authorize('create', Product::class);

        $product = $this->productService->createProduct(
            data: $request->validated(),
            image: $request->file('image'),
            document: $request->file('document'),
            user: $request->user()
        );

        return $this->successResponse(
            data: new ProductResource($product),
            message: 'Produk topi baru berhasil didaftarkan.',
            statusCode: 201
        );
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'creator', 'stockMovements.user']);

        return $this->successResponse(
            data: new ProductResource($product),
            message: 'Detail produk topi berhasil diambil.'
        );
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $updatedProduct = $this->productService->updateProduct(
            product: $product,
            data: $request->validated(),
            image: $request->file('image'),
            document: $request->file('document')
        );

        return $this->successResponse(
            data: new ProductResource($updatedProduct),
            message: 'Data produk topi berhasil diperbarui secara aman.'
        );
    }

    /**
     * Remove the specified product (soft-delete).
     */
    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);

        $this->productService->deleteProduct($product);

        return $this->successResponse(
            data: null,
            message: 'Produk topi berhasil dipindahkan ke arsip (soft-delete).'
        );
    }

    /**
     * Dedicated file upload endpoint for product image.
     */
    public function uploadImage(UploadImageRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $updatedProduct = $this->productService->uploadProductImage(
            product: $product,
            image: $request->file('image')
        );

        return $this->successResponse(
            data: new ProductResource($updatedProduct),
            message: 'Berkas gambar produk topi berhasil diunggah dan diperbarui.'
        );
    }

    /**
     * Concurrency-safe stock adjustment mutation with audit logging.
     */
    public function adjustStock(AdjustStockRequest $request, Product $product): JsonResponse
    {
        Gate::authorize('adjustStock', $product);

        $validated = $request->validated();

        try {
            $updatedProduct = $this->productService->adjustStock(
                product: $product,
                type: $validated['type'],
                quantity: (int) $validated['quantity'],
                notes: $validated['notes'] ?? null,
                user: $request->user()
            );

            return $this->successResponse(
                data: new ProductResource($updatedProduct),
                message: 'Mutasi persediaan stok berhasil dieksekusi secara aman.'
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: 422
            );
        }
    }

    /**
     * Retrieve immutable stock movements audit trail for a product.
     */
    public function stockMovements(Product $product): JsonResponse
    {
        $movements = $product->stockMovements()->with('user')->get();

        return $this->successResponse(
            data: StockMovementResource::collection($movements),
            message: 'Riwayat audit mutasi stok produk berhasil diambil.'
        );
    }
}
