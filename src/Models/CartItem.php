<?php

namespace Saeedvir\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Saeedvir\ShoppingCart\Support\ConfigCache;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'buyable_type',
        'buyable_id',
        'name',
        'quantity',
        'price',
        'attributes',
        'conditions',
        'tax_rate',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'attributes' => 'array',
        'conditions' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(ConfigCache::cartItemsTable());
        
        if ($connection = ConfigCache::databaseConnection()) {
            $this->setConnection($connection);
        }
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function buyable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the subtotal for this item (price * quantity).
     */
    public function getSubtotalAttribute(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    /**
     * Get the tax amount for this item.
     */
    public function getTaxAttribute(): float
    {
        if (!ConfigCache::taxEnabled()) {
            return 0;
        }

        $subtotal = $this->subtotal;
        
        if (ConfigCache::taxIncluded()) {
            // Tax is already included in price
            return round($subtotal - ($subtotal / (1 + $this->tax_rate)), 2);
        }

        return round($subtotal * $this->tax_rate, 2);
    }

    /**
     * Get the total for this item including tax.
     */
    public function getTotalAttribute(): float
    {
        $subtotal = $this->subtotal;
        
        if (ConfigCache::taxIncluded()) {
            return $subtotal;
        }

        return round($subtotal + $this->tax, 2);
    }

    /**
     * Apply conditions (discounts, fees) to this item.
     */
    public function applyConditions(array $conditions): void
    {
        $this->conditions = array_merge($this->conditions ?? [], $conditions);
        $this->save();
    }

    /**
     * Get a specific custom attribute value.
     */
    public function getCustomAttribute(string $key): mixed
    {
        if (isset($this->attributes['attributes'])) {
            $attributes = is_string($this->attributes['attributes']) 
                ? json_decode($this->attributes['attributes'], true) 
                : $this->attributes['attributes'];
            
            return $attributes[$key] ?? null;
        }

        return null;
    }
}
