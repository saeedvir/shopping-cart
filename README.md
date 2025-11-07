# Laravel Shopping Cart

A comprehensive shopping cart package for Laravel 11/12 with tax calculation, discounts, coupons, and flexible storage options.

## Features

- **🧩 Simple Item Management** – Add, update, and remove items with an expressive API.  
- **🎨 Product Variations & Attributes** – Support for color, size, packaging, and other options.  
- **💰 Built-in Tax Handling** – Configure global or per-item taxes and retrieve detailed summaries.  
- **🏷️ Discounts & Coupons** – Apply percentage or fixed discounts, coupon codes, and conditional fees.  
- **💾 Flexible Storage Drivers** – Use session for temporary carts or database for persistent ones.  
- **🧺 Multiple Cart Instances** – Create separate carts like “shopping cart,” “wishlist,” or “saved items.”  
- **📝 Custom Metadata** – Attach custom info like delivery notes or shipping method.  
- **💵 Currency Formatting** – Format subtotals, taxes, and totals with symbols and localization.  
- **⚡ Performance Optimized** – Smart caching and minimal queries for large carts.  
- **🔧 Fully Extensible** – Easily extend core services or override default behavior.

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0

## Installation

Install the package via Composer:

```bash
composer require saeedvir/shopping-cart
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=shopping-cart-config
```

If using database storage, publish and run the migrations:

```bash
php artisan vendor:publish --tag=shopping-cart-migrations
php artisan migrate
```

**Note:** The package includes performance optimizations with database indexes. Make sure to run migrations to benefit from optimal query performance.

## Configuration

The configuration file is located at `config/shopping-cart.php`. You can configure:

- Storage driver (session or database)
- Tax settings
- Currency formatting
- Cart expiration
- And more...

```php
return [
    'storage' => 'session', // or 'database'
    
    'tax' => [
        'enabled' => true,
        'default_rate' => 0.15, // 15%
        'included_in_price' => false,
    ],
    
    'currency' => [
        'code' => 'USD',
        'symbol' => '$',
    ],
];
```

## Basic Usage

### Adding Items to Cart

```php
use Saeedvir\ShoppingCart\Facades\Cart;

// Add a product model
$product = Product::find(1);
Cart::add($product, 2); // Add 2 items

// Add with custom attributes
Cart::add($product, 1, [
    'size' => 'Large',
    'color' => 'Red',
]);

// Add manually
Cart::add([
    'buyable_type' => Product::class,
    'buyable_id' => 1,
    'name' => 'Product Name',
    'price' => 99.99,
], 1);
```

### Using the Buyable Trait

Add the `Buyable` trait to your product model:

```php
use Saeedvir\ShoppingCart\Traits\Buyable;

class Product extends Model
{
    use Buyable;
}

// Now you can use convenient methods
$product->addToCart(2);
$product->inCart(); // Returns true/false
$product->removeFromCart();
```

### Retrieving Cart Items

```php
// Get all items
$items = Cart::items();

// Get item count
$count = Cart::count();

// Check if cart is empty
if (Cart::isEmpty()) {
    // Cart is empty
}

// Get a specific item
$item = Cart::get($itemId);
```

### Updating Items

```php
Cart::update($itemId, [
    'quantity' => 3,
    'price' => 89.99,
    'attributes' => ['color' => 'Blue'],
]);
```

### Removing Items

```php
// Remove a specific item
Cart::remove($itemId);

// Clear entire cart
Cart::clear();

// Destroy cart (remove from storage)
Cart::destroy();
```

## Advanced Features

### Multiple Cart Instances

Support different cart types (cart, wishlist, saved items):

```php
// Use wishlist instance
Cart::instance('wishlist')->add($product);

// Switch back to default cart
Cart::instance('default')->items();
```

### Tax Calculation

Taxes are automatically calculated based on configuration:

```php
// Get cart totals
$subtotal = Cart::subtotal(); // Before tax
$tax = Cart::tax(); // Tax amount
$total = Cart::total(); // Final total

// Per-item tax
foreach (Cart::items() as $item) {
    echo $item->getTax();
    echo $item->getTotal();
}
```

### Discounts and Conditions

Apply discounts, fees, and other conditions:

```php
// Apply percentage discount
Cart::condition('sale', 'discount', 10, 'percentage');

// Apply fixed discount
Cart::condition('coupon', 'discount', 5.00, 'fixed');

// Apply fee
Cart::condition('handling', 'fee', 2.50, 'fixed');

// Remove condition
Cart::removeCondition('sale');
```

### Coupon Codes

```php
// Apply coupon with validation
Cart::applyCoupon('SAVE20', function ($code, $cart) {
    // Validate coupon
    $coupon = Coupon::where('code', $code)->first();
    
    if (!$coupon || $coupon->isExpired()) {
        return false;
    }
    
    // Apply discount
    $cart->condition('coupon', 'discount', $coupon->value, 'percentage');
    
    return true;
});
```

### Metadata

Store additional cart information:

```php
// Set metadata
Cart::setMetadata('note', 'Gift wrapping requested');
Cart::setMetadata('shipping_method', 'express');

// Get metadata
$note = Cart::getMetadata('note');
$allMetadata = Cart::getMetadata();
```

### Currency Formatting

Get formatted prices with currency symbols:

```php
// Cart-level formatted amounts
echo Cart::formattedSubtotal(); // "$249.99"
echo Cart::formattedTax();      // "$37.50"
echo Cart::formattedDiscount(); // "$25.00"
echo Cart::formattedTotal();    // "$262.49"

// Item-level formatted amounts
$item = Cart::items()->first();
echo $item->formattedPrice();    // "$99.99"
echo $item->formattedSubtotal(); // "$199.98"
echo $item->formattedTax();      // "$30.00"
echo $item->formattedTotal();    // "$229.98"

// Using helper functions
echo cart_currency(99.99);       // "$99.99"
echo cart_currency_symbol();     // "$"
echo cart_currency_code();       // "USD"
```

### Loading Product Details (Performance Optimized)

To avoid N+1 queries when displaying products:

```php
// Load all product data at once (no N+1 queries!)
$cart = Cart::instance('default');
$cart->loadBuyables();

// Now safely access product details
foreach ($cart->items() as $item) {
    echo $item->buyable->name;
    echo $item->buyable->description;
    // No additional queries!
}
```

### Cart Summary

```php
// Get complete cart data
$summary = Cart::toArray();

/*
Returns:
[
    'identifier' => 'session_abc123',
    'instance' => 'default',
    'items' => [...],
    'count' => 5,
    'subtotal' => 249.99,
    'tax' => 37.50,
    'discount' => 25.00,
    'total' => 262.49,
    'conditions' => [...],
    'metadata' => [...],
]
*/
```

## Storage Options

### Session Storage (Default)

Cart data is stored in the user's session:

```php
'storage' => 'session',
```

### Database Storage

Cart data is persisted to the database:

```php
'storage' => 'database',
```

Database storage provides:
- Persistent carts across sessions
- Cart recovery for logged-in users
- Automatic cart expiration
- Better scalability

## Performance

The package is highly optimized for production use:

- **99% less memory usage**: Smart storage without full object serialization
- **95-99% fewer database queries**: Bulk operations and intelligent caching
- **4x faster calculations**: Built-in calculation caching
- **Database indexes**: Optimized queries for large datasets
- **Supports 10,000+ concurrent users**
- **Handles 1000+ item carts efficiently**

See `PERFORMANCE-SUMMARY.md` for detailed benchmarks and `PERFORMANCE-QUICK-TIPS.md` for best practices.

## Events

The package fires events for cart operations (when enabled in config):

- `CartItemAdded` (planned)
- `CartItemUpdated` (planned)
- `CartItemRemoved` (planned)
- `CartCleared` (planned)
- `CartDestroyed` (planned)

## API Reference

### Cart Methods

| Method | Description |
|--------|-------------|
| `instance(string $instance)` | Set the cart instance |
| `add($buyable, int $quantity, array $attributes)` | Add item to cart |
| `update(string $itemId, array $data)` | Update cart item |
| `remove(string $itemId)` | Remove item from cart |
| `get(string $itemId)` | Get specific item |
| `items()` | Get all items |
| `count()` | Get total item count |
| `isEmpty()` | Check if cart is empty |
| `clear()` | Clear cart contents |
| `destroy()` | Destroy cart |
| `condition(...)` | Apply condition |
| `applyCoupon(string $code, callable $validator)` | Apply coupon |
| `removeCondition(string $name)` | Remove condition |
| `subtotal()` | Get subtotal |
| `tax()` | Get tax total |
| `discount()` | Get discount total |
| `total()` | Get final total |
| `setMetadata(string $key, $value)` | Set metadata |
| `getMetadata(string $key)` | Get metadata |

### CartItem Methods

| Method | Description |
|--------|-------------|
| `getSubtotal()` | Get item subtotal |
| `getTax()` | Get item tax |
| `getTotal()` | Get item total |
| `toArray()` | Convert to array |

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Saeedvir](https://github.com/saeedvir)

## Support

For support, please open an issue on GitHub.
