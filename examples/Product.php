<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Saeedvir\ShoppingCart\Traits\Buyable;

class Product extends Model
{
    use Buyable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Check if product is in stock.
     */
    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Decrease stock.
     */
    public function decreaseStock(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $this->decrement('stock', $quantity);
    }

    /**
     * Increase stock.
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }
}
