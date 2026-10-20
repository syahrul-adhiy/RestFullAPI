<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'slug',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            if (empty($category->name) && !empty($category->category)) {
                $category->name = $category->category;
            }
            if (empty($category->category) && !empty($category->name)) {
                $category->category = $category->name;
            }
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name ?? $category->category ?? 'category');
            }
        });
    }

    /**
     * Accessor for category (maps to category or name).
     */
    public function getCategoryAttribute(): ?string
    {
        return $this->attributes['category'] ?? $this->attributes['name'] ?? null;
    }

    /**
     * Mutator for category attribute.
     */
    public function setCategoryAttribute($value): void
    {
        $this->attributes['category'] = $value;
        $this->attributes['name'] = $value;
    }

    /**
     * Products belonging to this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
