<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_movements';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'previous_stock',
        'current_stock',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'current_stock' => 'integer',
    ];

    /**
     * Associated product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who initiated the stock adjustment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
