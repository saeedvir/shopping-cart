<?php

namespace Saeedvir\ShoppingCart;

use Saeedvir\ShoppingCart\Contracts\CartStorageInterface;
use Saeedvir\ShoppingCart\Exceptions\InvalidCouponException;
use Saeedvir\ShoppingCart\Support\ConfigCache;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

class Cart implements Arrayable, Jsonable
{
    protected CartStorageInterface $storage;
    protected string $identifier;
    protected string $instance = 'default';
    protected Collection $items;
    protected array $metadata = [];
    protected array $conditions = [];
    
    // Calculation cache
    protected ?float $cachedSubtotal = null;
    protected ?float $cachedTax = null;
    protected ?float $cachedDiscount = null;
    protected ?float $cachedTotal = null;

    public function __construct(CartStorageInterface $storage, string $identifier)
    {
        $this->storage = $storage;
        $this->identifier = $identifier;
        $this->items = collect();
        $this->load();
    }

    /**
     * Set the cart instance.
     */
    public function instance(string $instance): self
    {
        $this->instance = $instance;
        $this->load();
        return $this;
    }

    /**
     * Load cart from storage.
     */
    protected function load(): void
    {
        $data = $this->storage->get($this->identifier, $this->instance);

        if ($data) {
            $this->items = collect($data['items'] ?? [])->map(fn($item) => new CartItem($item));
            $this->metadata = $data['metadata'] ?? [];
            $this->conditions = $data['conditions'] ?? [];
        }
        
        $this->clearCache();
    }
    
    /**
     * Clear calculation cache.
     */
    protected function clearCache(): void
    {
        $this->cachedSubtotal = null;
        $this->cachedTax = null;
        $this->cachedDiscount = null;
        $this->cachedTotal = null;
    }

    /**
     * Save cart to storage.
     */
    protected function save(): void
    {
        $this->storage->put($this->identifier, [
            'items' => $this->items->map(fn($item) => $item->toArray())->toArray(),
            'metadata' => $this->metadata,
            'conditions' => $this->conditions,
        ], $this->instance);
    }

    /**
     * Add an item to the cart.
     */
    public function add($buyable, int $quantity = 1, array $attributes = []): CartItem
    {
        // Check cart size limit
        $maxItems = ConfigCache::maxItems();
        if ($this->items->count() >= $maxItems) {
            throw new \Exception("Cart cannot exceed {$maxItems} items");
        }
        
        // Check quantity limit
        $maxQuantity = ConfigCache::maxQuantity();
        if ($quantity > $maxQuantity) {
            throw new \Exception("Quantity cannot exceed {$maxQuantity}");
        }
        
        $item = $this->createCartItem($buyable, $quantity, $attributes);
        
        // Check if item already exists
        $existingItem = $this->items->first(function ($existing) use ($item) {
            return $existing->buyableType === $item->buyableType
                && $existing->buyableId === $item->buyableId
                && $existing->attributes == $item->attributes;
        });

        if ($existingItem) {
            $existingItem->quantity += $quantity;
        } else {
            $this->items->push($item);
        }

        $this->clearCache();
        $this->save();
        
        return $existingItem ?? $item;
    }

    /**
     * Create a cart item from buyable.
     */
    protected function createCartItem($buyable, int $quantity, array $attributes): CartItem
    {
        if (is_array($buyable)) {
            // Remove buyable object to prevent memory bloat
            unset($buyable['buyable']);
            
            return new CartItem(array_merge($buyable, [
                'quantity' => $quantity,
                'attributes' => $attributes,
            ]));
        }

        return new CartItem([
            'buyable_type' => get_class($buyable),
            'buyable_id' => $buyable->id,
            'name' => $buyable->name ?? 'Product',
            'price' => $buyable->price ?? 0,
            'quantity' => $quantity,
            'attributes' => $attributes,
            // DON'T store buyable object - load on demand to save memory
        ]);
    }

    /**
     * Update an item in the cart.
     */
    public function update(string $itemId, array $data): ?CartItem
    {
        $item = $this->items->firstWhere('id', $itemId);

        if (!$item) {
            return null;
        }

        if (isset($data['quantity'])) {
            $item->quantity = $data['quantity'];
        }

        if (isset($data['price'])) {
            $item->price = $data['price'];
        }

        if (isset($data['attributes'])) {
            $item->attributes = array_merge($item->attributes, $data['attributes']);
        }

        $this->clearCache();
        $this->save();

        return $item;
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(string $itemId): bool
    {
        $this->items = $this->items->reject(fn($item) => $item->id === $itemId);
        $this->clearCache();
        $this->save();
        return true;
    }

    /**
     * Get an item by ID.
     */
    public function get(string $itemId): ?CartItem
    {
        return $this->items->firstWhere('id', $itemId);
    }

    /**
     * Get all items.
     */
    public function items(): Collection
    {
        return $this->items;
    }

    /**
     * Get item count.
     */
    public function count(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if cart is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Clear the cart.
     */
    public function clear(): void
    {
        $this->items = collect();
        $this->conditions = [];
        $this->metadata = [];
        $this->save();
    }

    /**
     * Destroy the cart.
     */
    public function destroy(): void
    {
        $this->storage->forget($this->identifier, $this->instance);
        $this->items = collect();
        $this->conditions = [];
        $this->metadata = [];
    }

    /**
     * Apply a condition (discount, fee, tax, etc.).
     */
    public function condition(string $name, string $type, float $value, string $target = 'subtotal', array $rules = []): self
    {
        $this->conditions[$name] = [
            'name' => $name,
            'type' => $type,
            'value' => $value,
            'target' => $target,
            'rules' => $rules,
        ];

        $this->clearCache();
        $this->save();

        return $this;
    }

    /**
     * Apply a coupon code.
     */
    public function applyCoupon(string $code, callable $validator = null): self
    {
        if ($validator && !$validator($code, $this)) {
            throw new InvalidCouponException("Invalid coupon code: {$code}");
        }

        // Store coupon in metadata
        $this->metadata['coupon'] = $code;
        $this->save();

        return $this;
    }

    /**
     * Remove a condition.
     */
    public function removeCondition(string $name): self
    {
        unset($this->conditions[$name]);
        $this->clearCache();
        $this->save();
        return $this;
    }

    /**
     * Get cart subtotal (before tax and conditions).
     */
    public function subtotal(): float
    {
        if ($this->cachedSubtotal === null) {
            $this->cachedSubtotal = round($this->items->sum(fn($item) => $item->getSubtotal()), 2);
        }
        
        return $this->cachedSubtotal;
    }

    /**
     * Get total tax.
     */
    public function tax(): float
    {
        if ($this->cachedTax === null) {
            $this->cachedTax = round($this->items->sum(fn($item) => $item->getTax()), 2);
        }
        
        return $this->cachedTax;
    }

    /**
     * Get total discounts.
     */
    public function discount(): float
    {
        if ($this->cachedDiscount === null) {
            $discount = 0;

            foreach ($this->conditions as $condition) {
                if ($condition['type'] === 'discount') {
                    $discount += $this->calculateConditionValue($condition);
                }
            }

            $this->cachedDiscount = round($discount, 2);
        }
        
        return $this->cachedDiscount;
    }

    /**
     * Get cart total.
     */
    public function total(): float
    {
        if ($this->cachedTotal === null) {
            $subtotal = $this->subtotal();
            $tax = $this->tax();
            $discount = $this->discount();
            
            // Calculate fees
            $fees = 0;
            foreach ($this->conditions as $condition) {
                if ($condition['type'] === 'fee') {
                    $fees += $this->calculateConditionValue($condition);
                }
            }

            $this->cachedTotal = round($subtotal + $tax - $discount + $fees, 2);
        }
        
        return $this->cachedTotal;
    }

    /**
     * Calculate condition value.
     */
    protected function calculateConditionValue(array $condition): float
    {
        $base = match($condition['target']) {
            'subtotal' => $this->subtotal(),
            'total' => $this->subtotal() + $this->tax(),
            default => $this->subtotal(),
        };

        if (strpos((string) $condition['value'], '%') !== false) {
            $percentage = (float) str_replace('%', '', (string) $condition['value']);
            return ($base * $percentage) / 100;
        }

        return (float) $condition['value'];
    }

    /**
     * Set metadata.
     */
    public function setMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        $this->save();
        return $this;
    }

    /**
     * Get metadata.
     */
    public function getMetadata(string $key = null)
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? null;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'instance' => $this->instance,
            'items' => $this->items->map(fn($item) => $item->toArray())->toArray(),
            'count' => $this->count(),
            'subtotal' => $this->subtotal(),
            'tax' => $this->tax(),
            'discount' => $this->discount(),
            'total' => $this->total(),
            'conditions' => $this->conditions,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON.
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get formatted subtotal.
     */
    public function formattedSubtotal(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->subtotal());
    }

    /**
     * Get formatted tax.
     */
    public function formattedTax(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->tax());
    }

    /**
     * Get formatted discount.
     */
    public function formattedDiscount(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->discount());
    }

    /**
     * Get formatted total.
     */
    public function formattedTotal(): string
    {
        return \Saeedvir\ShoppingCart\Helpers\Currency::format($this->total());
    }

    /**
     * Load buyable models for all items.
     * Use this when you need to display product details to avoid N+1 queries.
     */
    public function loadBuyables(): self
    {
        // Group items by buyable type
        $grouped = $this->items->groupBy('buyableType');
        
        foreach ($grouped as $type => $items) {
            if (!class_exists($type)) {
                continue;
            }
            
            $ids = $items->pluck('buyableId')->unique()->toArray();
            
            // Load all buyables of this type at once
            $buyables = $type::whereIn('id', $ids)->get()->keyBy('id');
            
            // Attach to items
            foreach ($items as $item) {
                $item->buyable = $buyables[$item->buyableId] ?? null;
            }
        }
        
        return $this;
    }
}
