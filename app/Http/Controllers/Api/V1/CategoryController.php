<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount('products');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($isActive)) {
                $query->where('is_active', $isActive);
            }
        }

        $categories = $query->orderBy('name', 'asc')->get();

        return $this->successResponse(
            data: CategoryResource::collection($categories),
            message: 'Daftar kategori berhasil diambil.'
        );
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $category = Category::create($request->validated());

        return $this->successResponse(
            data: new CategoryResource($category),
            message: 'Kategori baru berhasil dibuat.',
            statusCode: 201
        );
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        $category->loadCount('products');

        return $this->successResponse(
            data: new CategoryResource($category),
            message: 'Detail kategori berhasil diambil.'
        );
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return $this->successResponse(
            data: new CategoryResource($category->fresh()),
            message: 'Data kategori berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        // Safe mutation check: prevent deleting category if it still has active products
        if ($category->products()->count() > 0) {
            return $this->errorResponse(
                message: 'Kategori tidak dapat dihapus karena masih memiliki produk topi yang terkait. Pindahkan atau hapus produk terlebih dahulu.',
                statusCode: 422
            );
        }

        $category->delete();

        return $this->successResponse(
            data: null,
            message: 'Kategori berhasil dihapus secara aman.'
        );
    }
}
