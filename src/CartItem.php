<?php

namespace Saeedvir\ShoppingCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Saeedvir\ShoppingCart\Support\ConfigCache;

class CartItem implements Arrayable, Jsonable
{
    public string $id;
    public string $buyableType;
    public int $buyableId;
    public string $name;
    public int $quantity;
    public float $price;
    public array $attributes;
    public array $conditions;
    public float $taxRate;
    public $buyable;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? uniqid('cart_item_');
        $this->buyableType = $data['buyable_type'];
        $this->buyableId = $data['buyable_id'];
        $this->name = $data['name'];
        $this->quantity = $data['quantity'] ?? 1;
        $this->price = (float) $data['price'];
        $this->attributes = $data['attributes'] ?? [];
        $this->conditions = $data['conditions'] ?? [];
        $this->taxRate = (float) ($data['tax_rate'] ?? ConfigCache::taxDefaultRate());
        $this->buyable = $data['buyable'] ?? null;
    }

    public function getSubtotal(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    public function getTax(): float
    {
        if (!ConfigCache::taxEnabled()) {
            return 0;
        }

        $subtotal = $this->getSubtotal();
        
        if (ConfigCache::taxIncluded()) {
            return round($subtotal - ($subtotal / (1 + $this->taxRate)), 2);
        }

        return round($subtotal * $this->taxRate, 2);
    }

    public function getTotal(): float
    {
        $subtotal = $this->getSubtotal();
        $discount = $this->getConditionTotal('discount');
        $fee = $this->getConditionTotal('fee');
        
        if (ConfigCache::taxIncluded()) {
            return round($subtotal - $discount + $fee, 2);
        }

        return round($subtotal + $this->getTax() - $discount + $fee, 2);
    }

    public function getConditionTotal(string $type): float
    {
        $total = 0;

        foreach ($this->conditions as $condition) {
            if ($condition['type'] === $type) {
                $total += $this->calculateConditionValue($condition);
            }
        }

        return round($total, 2);
    }

    protected function calculateConditionValue(array $condition): float
    {
        $value = $condition['value'];
        
        if ($condition['target'] === 'percentage') {
            return ($this->getSubtotal() * $value) / 100;
        }

        return $value;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'buyable_type' => $this->buyableType,
            'buyable_id' => $this->buyableId,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'attributes' => $this->attributes,
            'conditions' => $this->conditions,
            'tax_rate' => $this->taxRate,
            'subtotal' => $this->getSubtotal(),
            'tax' => $this->getTax(),
            'total' => $this->getTotal(),
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get formatted price.
     */
    public function formattedPrice(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->price);
    }

    /**
     * Get formatted subtotal.
     */
    public function formattedSubtotal(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->getSubtotal());
    }

    /**
     * Get formatted tax.
     */
    public function formattedTax(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->getTax());
    }

    /**
     * Get formatted total.
     */
    public function formattedTotal(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->getTotal());
    }
}
