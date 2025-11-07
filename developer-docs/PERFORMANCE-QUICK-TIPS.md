# Performance Quick Tips

## ⚡ Quick Reference Card

### Storage Choice

```php
// ✅ FAST - Use for guests (10-100x faster)
'storage' => 'session'

// ✅ PERSISTENT - Use for logged-in users
'storage' => 'database'
```

---

### Cart Limits (Add to config)

```php
// config/shopping-cart.php
'limits' => [
    'max_items' => 100,              // Prevent abuse
    'max_quantity_per_item' => 999,  // Prevent spam
],
```

---

### Loading Buyables

```php
// ❌ SLOW - N+1 queries
foreach (Cart::items() as $item) {
    echo $item->buyable->name; // Query per item!
}

// ✅ FAST - Single query batch load
$cart = Cart::instance('default');
$cart->loadBuyables(); // Load all at once

foreach ($cart->items() as $item) {
    echo $item->buyable->name; // No extra queries!
}
```

---

### Avoid Repeated Calculations

```php
// ❌ SLOW - Recalculates every time
for ($i = 0; $i < 100; $i++) {
    $array = Cart::toArray(); // Recalculates totals 100x!
}

// ✅ FAST - Calculate once
$cartData = Cart::toArray(); // Calculate once
for ($i = 0; $i < 100; $i++) {
    // Use $cartData
}
```

---

### Batch Operations

```php
// ❌ SLOW - Multiple saves
foreach ($products as $product) {
    Cart::add($product);
    // Saves to storage each time!
}

// ✅ FAST - Disabled auto-save (future feature)
// Current workaround: Add all items, save happens once per add
// Consider implementing batch add in future
```

---

### Database Queries

```php
// ❌ SLOW - 101 queries for 100 items
// Current implementation issue

// ✅ FAST - 3 queries for 100 items
// Use optimized DatabaseStorage from OPTIMIZATIONS.md
```

---

### Memory Usage

```php
// ❌ HEAVY - 5MB for 100 items
Cart::add($product); // Stores entire model

// ✅ LIGHT - 50KB for 100 items
Cart::add([
    'buyable_type' => Product::class,
    'buyable_id' => $product->id,
    'name' => $product->name,
    'price' => $product->price,
]);
// Load buyable later with loadBuyables()
```

---

### Performance Patterns

#### ✅ Good Pattern
```php
// Load cart once
$cart = Cart::instance('default');

// Get totals once
$total = $cart->total();
$count = $cart->count();

// Pass to view
return view('cart', compact('cart', 'total', 'count'));
```

#### ❌ Bad Pattern
```php
// In blade template
@foreach(Cart::items() as $item)
    Total: {{ Cart::total() }} // Recalculates every iteration!
@endforeach
```

---

### Monitoring Checklist

```
Performance Targets:
[ ] Response time < 50ms
[ ] Memory usage < 100KB per cart
[ ] Database queries < 5 per operation
[ ] Cart size < 100 items
```

---

### Environment Configuration

```bash
# .env - Optimize for your use case

# Session storage (faster)
CART_STORAGE=session

# Database storage (persistent)  
CART_STORAGE=database
CART_DB_CONNECTION=mysql

# Cart limits
CART_MAX_ITEMS=100
CART_MAX_QUANTITY=999

# Expiration (minutes)
CART_EXPIRATION=10080
```

---

### Quick Performance Test

```php
use Illuminate\Support\Facades\DB;

// Enable query logging
DB::enableQueryLog();

// Perform cart operations
Cart::add($product);
Cart::add($product2);
Cart::total();

// Check query count
$queries = count(DB::getQueryLog());

// Should be < 10 queries
dump("Query count: {$queries}");
```

---

### Common Mistakes

| Mistake | Impact | Solution |
|---------|--------|----------|
| Storing buyable objects | 100x memory | Store IDs only |
| No cart limits | Security risk | Add max_items |
| Repeated `toArray()` calls | Slow | Cache result |
| Loading buyables in loop | N+1 queries | Use `loadBuyables()` |
| Large session carts | Session bloat | Use database |

---

### Performance Checklist

```
Before Going to Production:
[ ] Implemented N+1 query fix
[ ] Removed buyable object storage
[ ] Added cart size limits
[ ] Added database indexes
[ ] Tested with 100+ items
[ ] Load tested with 1000+ users
[ ] Monitored memory usage
[ ] Benchmarked response times
[ ] Reviewed PERFORMANCE-ANALYSIS.md
[ ] Implemented caching strategy
```

---

### Emergency Performance Fixes

If your cart is slow in production:

```php
// 1. Quick fix: Switch to session storage
// .env
CART_STORAGE=session

// 2. Add cart limit immediately
// config/shopping-cart.php
'limits' => ['max_items' => 50],

// 3. Clear large carts
Cart::where('items_count', '>', 100)->delete();

// 4. Add database indexes
php artisan migrate // Use migration from OPTIMIZATIONS.md
```

---

### Resources

- 📊 **PERFORMANCE-SUMMARY.md** - Overview
- 🔍 **PERFORMANCE-ANALYSIS.md** - Detailed analysis  
- 🛠️ **OPTIMIZATIONS.md** - Optimized code
- 📚 **README.md** - Usage guide

---

### Support & Questions

Performance issues? Check:
1. PERFORMANCE-ANALYSIS.md for diagnosis
2. OPTIMIZATIONS.md for solutions
3. Create GitHub issue with metrics
