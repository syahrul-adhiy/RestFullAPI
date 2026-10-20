<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'user_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'material',
        'status',
        'image_path',
        'document_path',
        'img',
        'product_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category_id' => 'integer',
        'user_id' => 'integer',
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
        'document_url',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $baseSlug = Str::slug($product->name);
                $product->slug = $baseSlug . '-' . Str::lower(Str::random(6));
            }
        });
    }

    /**
     * Category relationship.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Creator (user) relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Stock movements audit trail relationship.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderBy('created_at', 'desc');
    }

    /**
     * Accessor for product_id (maps to id or product_id).
     */
    public function getProductIdAttribute(): mixed
    {
        return $this->attributes['product_id'] ?? $this->attributes['id'] ?? null;
    }

    /**
     * Accessor for img attribute (maps to img or image_path).
     */
    public function getImgAttribute(): ?string
    {
        return $this->attributes['img'] ?? $this->attributes['image_path'] ?? null;
    }

    /**
     * Mutator for img attribute.
     */
    public function setImgAttribute($value): void
    {
        $this->attributes['img'] = $value;
        $this->attributes['image_path'] = $value;
    }

    /**
     * Dynamic URL for product image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Dynamic URL for product tech pack / document.
     */
    public function getDocumentUrlAttribute(): ?string
    {
        if (empty($this->document_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->document_path);
    }

    /**
     * Scope for multi-field text search.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('material', 'like', "%{$term}%");
        });
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeFilterCategory(Builder $query, mixed $categoryId): Builder
    {
        if (blank($categoryId)) {
            return $query;
        }

        return $query->where('category_id', (int) $categoryId);
    }

    /**
     * Scope for filtering by product status.
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by price range.
     */
    public function scopeFilterPriceRange(Builder $query, ?float $minPrice, ?float $maxPrice): Builder
    {
        if (!is_null($minPrice) && $minPrice >= 0) {
            $query->where('price', '>=', $minPrice);
        }

        if (!is_null($maxPrice) && $maxPrice > 0) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Scope for filtering stock availability.
     */
    public function scopeFilterInStock(Builder $query, mixed $inStock): Builder
    {
        if (is_null($inStock) || $inStock === '') {
            return $query;
        }

        $flag = filter_var($inStock, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($flag === true) {
            return $query->where('stock', '>', 0);
        } elseif ($flag === false) {
            return $query->where('stock', '<=', 0);
        }

        return $query;
    }

    /**
     * Scope for dynamic sorting.
     */
    public function scopeApplySorting(Builder $query, ?string $sortBy, ?string $sortOrder): Builder
    {
        $allowedSorts = ['id', 'name', 'price', 'stock', 'created_at', 'status'];
        $column = in_array(strtolower((string) $sortBy), $allowedSorts, true) ? strtolower((string) $sortBy) : 'created_at';
        $direction = strtolower((string) $sortOrder) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction);
    }
}
