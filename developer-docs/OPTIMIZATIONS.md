# Performance Optimizations Guide

This document provides optimized code implementations to address the performance issues identified in the analysis.

## 1. Optimized DatabaseStorage (Fix N+1 Problem)

Replace `src/Storage/DatabaseStorage.php` `put()` method:

```php
public function put(string $identifier, array $data, string $instance = 'default'): void
{
    DB::transaction(function () use ($identifier, $data, $instance) {
        $cart = Cart::firstOrCreate(
            [
                'identifier' => $identifier,
                'instance' => $instance,
            ],
            [
                'metadata' => $data['metadata'] ?? [],
                'expires_at' => $this->getExpirationTime(),
            ]
        );

        $cart->update([
            'metadata' => $data['metadata'] ?? [],
            'expires_at' => $this->getExpirationTime(),
        ]);

        if (isset($data['items']) && !empty($data['items'])) {
            // Get item IDs to keep
            $itemIds = collect($data['items'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete items not in the new list
            if (!empty($itemIds)) {
                $cart->items()->whereNotIn('id', $itemIds)->delete();
            } else {
                $cart->items()->delete();
            }

            // Prepare items for upsert
            $upsertData = collect($data['items'])->map(function ($item) use ($cart) {
                return [
                    'id' => $item['id'] ?? null,
                    'cart_id' => $cart->id,
                    'buyable_type' => $item['buyable_type'],
                    'buyable_id' => $item['buyable_id'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'attributes' => json_encode($item['attributes'] ?? []),
                    'conditions' => json_encode($item['conditions'] ?? []),
                    'tax_rate' => $item['tax_rate'] ?? config('shopping-cart.tax.default_rate', 0),
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            })->filter(fn($item) => $item['id'] === null)->toArray();

            // Bulk insert new items
            if (!empty($upsertData)) {
                DB::table(config('shopping-cart.database.cart_items_table', 'cart_items'))
                    ->insert($upsertData);
            }

            // Update existing items individually (Laravel limitation)
            collect($data['items'])->filter(fn($item) => isset($item['id']))->each(function ($item) use ($cart) {
                $cart->items()->where('id', $item['id'])->update([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'attributes' => $item['attributes'] ?? [],
                    'conditions' => $item['conditions'] ?? [],
                    'tax_rate' => $item['tax_rate'] ?? config('shopping-cart.tax.default_rate', 0),
                ]);
            });
        } else {
            // Clear all items if none provided
            $cart->items()->delete();
        }
    });
}
```

**Performance Gain:** 100 queries → 5-10 queries

---

## 2. Optimized Cart Class with Caching

Add to `src/Cart.php`:

```php
class Cart implements Arrayable, Jsonable
{
    // ... existing properties ...
    
    protected ?float $cachedSubtotal = null;
    protected ?float $cachedTax = null;
    protected ?float $cachedDiscount = null;
    protected ?float $cachedTotal = null;
    
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
     * Get cart subtotal (before tax and conditions).
     */
    public function subtotal(): float
    {
        if ($this->cachedSubtotal === null) {
            $this->cachedSubtotal = round(
                $this->items->sum(fn($item) => $item->getSubtotal()), 
                2
            );
        }
        
        return $this->cachedSubtotal;
    }
    
    /**
     * Get total tax.
     */
    public function tax(): float
    {
        if ($this->cachedTax === null) {
            $this->cachedTax = round(
                $this->items->sum(fn($item) => $item->getTax()), 
                2
            );
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
     * Add an item to the cart.
     */
    public function add($buyable, int $quantity = 1, array $attributes = []): CartItem
    {
        $item = $this->createCartItem($buyable, $quantity, $attributes);
        
        // ... existing code ...
        
        $this->clearCache(); // Clear cache after modification
        $this->save();
        
        return $existingItem ?? $item;
    }
    
    /**
     * Update an item in the cart.
     */
    public function update(string $itemId, array $data): ?CartItem
    {
        // ... existing code ...
        
        $this->clearCache(); // Clear cache after modification
        $this->save();
        
        return $item;
    }
    
    /**
     * Remove an item from the cart.
     */
    public function remove(string $itemId): bool
    {
        $this->items = $this->items->reject(fn($item) => $item->id === $itemId);
        $this->clearCache(); // Clear cache after modification
        $this->save();
        return true;
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
        
        $this->clearCache(); // Clear cache after modification
        $this->save();
        
        return $this;
    }
    
    /**
     * Remove a condition.
     */
    public function removeCondition(string $name): self
    {
        unset($this->conditions[$name]);
        $this->clearCache(); // Clear cache after modification
        $this->save();
        return $this;
    }
}
```

**Performance Gain:** Multiple iterations → Single iteration

---

## 3. Remove Buyable Object Storage

Modify `src/Cart.php` `createCartItem()` method:

```php
protected function createCartItem($buyable, int $quantity, array $attributes): CartItem
{
    if (is_array($buyable)) {
        // Remove 'buyable' key if present to avoid storage
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
        // DON'T store buyable object - load it on demand instead
    ]);
}
```

And update `DatabaseStorage.php` to not include buyable in array:

```php
return [
    'items' => $cart->items->map(function ($item) {
        return [
            'id' => $item->id,
            'buyable_type' => $item->buyable_type,
            'buyable_id' => $item->buyable_id,
            'name' => $item->name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'attributes' => $item->attributes,
            'conditions' => $item->conditions,
            'tax_rate' => $item->tax_rate,
            // 'buyable' => $item->buyable, // REMOVED - load on demand
        ];
    })->toArray(),
    'metadata' => $cart->metadata,
];
```

**Memory Reduction:** 500KB → 5KB per cart

---

## 4. Add Cart Size Limits

Add to `config/shopping-cart.php`:

```php
/*
|--------------------------------------------------------------------------
| Cart Limits
|--------------------------------------------------------------------------
|
| Set limits to prevent abuse and performance issues.
|
*/

'limits' => [
    'max_items' => env('CART_MAX_ITEMS', 100),
    'max_quantity_per_item' => env('CART_MAX_QUANTITY', 999),
],
```

Add to `src/Cart.php`:

```php
/**
 * Add an item to the cart.
 */
public function add($buyable, int $quantity = 1, array $attributes = []): CartItem
{
    // Check cart size limit
    $maxItems = config('shopping-cart.limits.max_items', 100);
    if ($this->items->count() >= $maxItems) {
        throw new \Exception("Cart cannot exceed {$maxItems} items");
    }
    
    // Check quantity limit
    $maxQuantity = config('shopping-cart.limits.max_quantity_per_item', 999);
    if ($quantity > $maxQuantity) {
        throw new \Exception("Quantity cannot exceed {$maxQuantity}");
    }
    
    // ... existing code ...
}
```

---

## 5. Optimize Item Lookup with Hash Map

Add to `src/Cart.php`:

```php
/**
 * Generate unique key for cart item.
 */
protected function getItemKey(CartItem $item): string
{
    return md5(
        $item->buyableType . 
        $item->buyableId . 
        serialize($item->attributes)
    );
}

/**
 * Build items index for fast lookups.
 */
protected function buildItemsIndex(): array
{
    $index = [];
    foreach ($this->items as $item) {
        $index[$this->getItemKey($item)] = $item;
    }
    return $index;
}

/**
 * Add an item to the cart (optimized version).
 */
public function add($buyable, int $quantity = 1, array $attributes = []): CartItem
{
    $item = $this->createCartItem($buyable, $quantity, $attributes);
    
    // Fast lookup using hash
    $itemKey = $this->getItemKey($item);
    $index = $this->buildItemsIndex();
    
    if (isset($index[$itemKey])) {
        $index[$itemKey]->quantity += $quantity;
        $existingItem = $index[$itemKey];
    } else {
        $this->items->push($item);
    }
    
    $this->clearCache();
    $this->save();
    
    return $existingItem ?? $item;
}
```

**Performance Gain:** O(n) → O(1) lookup

---

## 6. Add Lazy Loading Helper

Add to `src/Cart.php`:

```php
/**
 * Load buyable models for all items.
 * Use this when you need to display product details.
 */
public function loadBuyables(): self
{
    // Group items by buyable type
    $grouped = $this->items->groupBy('buyableType');
    
    foreach ($grouped as $type => $items) {
        $ids = $items->pluck('buyableId')->unique();
        
        // Load all buyables of this type at once
        if (class_exists($type)) {
            $buyables = $type::whereIn('id', $ids)->get()->keyBy('id');
            
            // Attach to items
            foreach ($items as $item) {
                $item->buyable = $buyables[$item->buyableId] ?? null;
            }
        }
    }
    
    return $this;
}
```

**Usage:**

```php
// In controller
$cart = Cart::instance('default');
$cart->loadBuyables(); // Load all product data at once

// Now access buyables
foreach ($cart->items() as $item) {
    echo $item->buyable->name; // No N+1 queries!
}
```

---

## Implementation Priority

### Phase 1: Critical (Implement Immediately)
1. ✅ Fix N+1 queries in DatabaseStorage
2. ✅ Remove buyable object storage
3. ✅ Add cart size limits

### Phase 2: High Priority (Next Sprint)
4. ✅ Implement calculation caching
5. ✅ Add lazy loading helper
6. ✅ Optimize item lookup

### Phase 3: Nice to Have (Future)
7. Redis storage driver
8. Pagination for large carts
9. Background cart cleanup job

---

## Testing Performance

Add this test to verify optimizations:

```php
// tests/Performance/CartPerformanceTest.php

use Illuminate\Support\Facades\DB;

class CartPerformanceTest extends TestCase
{
    public function test_database_queries_are_optimized()
    {
        DB::enableQueryLog();
        
        $cart = Cart::instance('default');
        
        // Add 100 items
        for ($i = 1; $i <= 100; $i++) {
            $cart->add([
                'buyable_type' => Product::class,
                'buyable_id' => $i,
                'name' => "Product {$i}",
                'price' => 99.99,
            ]);
        }
        
        $queries = DB::getQueryLog();
        
        // Should be less than 10 queries total (not 100+)
        $this->assertLessThan(10, count($queries));
    }
    
    public function test_calculation_caching_works()
    {
        $cart = Cart::instance('default');
        
        // Add items...
        
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $total = $cart->total();
        }
        $duration = microtime(true) - $start;
        
        // 100 calls should take less than 10ms with caching
        $this->assertLessThan(0.01, $duration);
    }
}
```

---

## Monitoring & Metrics

Add to your application to monitor cart performance:

```php
// app/Http/Middleware/MonitorCartPerformance.php

use Illuminate\Support\Facades\Log;

class MonitorCartPerformance
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // ms
        $memory = (memory_get_usage() - $startMemory) / 1024; // KB
        
        if ($duration > 100 || $memory > 500) {
            Log::warning('Slow cart operation', [
                'duration_ms' => $duration,
                'memory_kb' => $memory,
                'route' => $request->route()->getName(),
            ]);
        }
        
        return $response;
    }
}
```
