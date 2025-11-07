# Usage Guide

## Table of Contents

1. [Basic Cart Operations](#basic-cart-operations)
2. [Working with Attributes](#working-with-attributes)
3. [Tax Management](#tax-management)
4. [Discounts and Coupons](#discounts-and-coupons)
5. [Multiple Cart Instances](#multiple-cart-instances)
6. [Storage Options](#storage-options)
7. [Advanced Features](#advanced-features)

## Basic Cart Operations

### Adding Items

```php
use Saeedvir\ShoppingCart\Facades\Cart;

// Add a product model
$product = Product::find(1);
Cart::add($product, 2); // quantity: 2

// Add with array
Cart::add([
    'buyable_type' => Product::class,
    'buyable_id' => 1,
    'name' => 'Laptop',
    'price' => 999.99,
], 1);

// Using Buyable trait
$product->addToCart(1);
```

### Retrieving Items

```php
// Get all items
$items = Cart::items();

foreach ($items as $item) {
    echo $item->name;
    echo $item->quantity;
    echo $item->price;
    echo $item->getSubtotal();
}

// Get specific item
$item = Cart::get($itemId);

// Get item count
$totalItems = Cart::count();

// Check if empty
if (Cart::isEmpty()) {
    echo "Your cart is empty";
}
```

### Updating Items

```php
// Update quantity
Cart::update($itemId, ['quantity' => 5]);

// Update price
Cart::update($itemId, ['price' => 89.99]);

// Update multiple fields
Cart::update($itemId, [
    'quantity' => 3,
    'price' => 79.99,
]);
```

### Removing Items

```php
// Remove specific item
Cart::remove($itemId);

// Remove using Buyable trait
$product->removeFromCart();

// Clear entire cart
Cart::clear();

// Destroy cart (removes from storage)
Cart::destroy();
```

## Working with Attributes

Attributes allow you to store item variations like size, color, etc.

### Adding Items with Attributes

```php
$product = Product::find(1);

Cart::add($product, 1, [
    'size' => 'Large',
    'color' => 'Blue',
    'custom_text' => 'Happy Birthday',
]);
```

### Accessing Attributes

```php
$item = Cart::get($itemId);

// Access custom attributes
$size = $item->getCustomAttribute('size');
$color = $item->getCustomAttribute('color');

// All attributes
$attributes = $item->attributes;
```

### Updating Attributes

```php
Cart::update($itemId, [
    'attributes' => [
        'size' => 'Medium',
        'color' => 'Red',
    ]
]);
```

## Tax Management

### Configure Tax Settings

In `config/shopping-cart.php`:

```php
'tax' => [
    'enabled' => true,
    'default_rate' => 0.15, // 15%
    'included_in_price' => false,
],
```

### Getting Tax Information

```php
// Cart-level tax
$taxAmount = Cart::tax();
$subtotal = Cart::subtotal();
$total = Cart::total(); // includes tax

// Item-level tax
$item = Cart::get($itemId);
$itemTax = $item->getTax();
$itemTotal = $item->getTotal();
```

### Custom Tax Rates per Item

```php
Cart::add([
    'buyable_type' => Product::class,
    'buyable_id' => 1,
    'name' => 'Product',
    'price' => 100,
    'tax_rate' => 0.20, // 20% tax for this item
], 1);
```

## Discounts and Coupons

### Applying Discounts

```php
// Fixed amount discount
Cart::condition('holiday_sale', 'discount', 10.00, 'fixed');

// Percentage discount
Cart::condition('black_friday', 'discount', 20, 'percentage');

// Get discount amount
$discount = Cart::discount();
```

### Coupon Codes

```php
// Apply coupon with custom validation
Cart::applyCoupon('SAVE20', function ($code, $cart) {
    // Fetch coupon from database
    $coupon = Coupon::where('code', $code)
        ->where('is_active', true)
        ->where('expires_at', '>', now())
        ->first();
    
    if (!$coupon) {
        return false; // Invalid coupon
    }
    
    // Check minimum purchase
    if ($cart->subtotal() < $coupon->minimum_purchase) {
        return false;
    }
    
    // Apply discount
    if ($coupon->type === 'percentage') {
        $cart->condition('coupon', 'discount', $coupon->value, 'percentage');
    } else {
        $cart->condition('coupon', 'discount', $coupon->value, 'fixed');
    }
    
    return true;
});
```

### Managing Conditions

```php
// Add shipping fee
Cart::condition('shipping', 'fee', 5.99, 'fixed');

// Remove specific condition
Cart::removeCondition('shipping');

// View all conditions
$conditions = Cart::toArray()['conditions'];
```

## Multiple Cart Instances

Support different cart types (shopping cart, wishlist, comparison):

### Using Different Instances

```php
// Add to wishlist
Cart::instance('wishlist')->add($product);

// Add to comparison
Cart::instance('comparison')->add($product);

// Default cart
Cart::instance('default')->add($product);

// Or without specifying (uses default)
Cart::add($product);
```

### Managing Multiple Instances

```php
// Get wishlist items
$wishlistItems = Cart::instance('wishlist')->items();

// Move item from wishlist to cart
$item = Cart::instance('wishlist')->get($itemId);
Cart::instance('default')->add([
    'buyable_type' => $item->buyableType,
    'buyable_id' => $item->buyableId,
    'name' => $item->name,
    'price' => $item->price,
], $item->quantity);
Cart::instance('wishlist')->remove($itemId);

// Clear specific instance
Cart::instance('wishlist')->clear();
```

## Storage Options

### Session Storage

Default storage option. Cart is tied to user's session:

```php
// In config/shopping-cart.php
'storage' => 'session',
```

**Pros:**
- Simple setup
- No database queries
- Good for guest users

**Cons:**
- Lost when session expires
- Can't persist across devices

### Database Storage

Persistent storage in database:

```php
// In config/shopping-cart.php
'storage' => 'database',
```

**Pros:**
- Persistent across sessions
- Works across devices for logged-in users
- Can implement cart recovery

**Cons:**
- Requires database migrations
- Slightly slower than session

### Cart Expiration

```php
// In config/shopping-cart.php
'expiration' => 10080, // 7 days in minutes

// Or in .env
CART_EXPIRATION=10080
```

## Advanced Features

### Cart Metadata

Store additional information with the cart:

```php
// Set metadata
Cart::setMetadata('gift_message', 'Happy Birthday!');
Cart::setMetadata('gift_wrap', true);
Cart::setMetadata('delivery_date', '2025-12-25');

// Get metadata
$message = Cart::getMetadata('gift_message');
$allMetadata = Cart::getMetadata();
```

### Cart Summary

Get complete cart information:

```php
$summary = Cart::toArray();

/*
[
    'identifier' => 'user_123',
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

// Convert to JSON
$json = Cart::toJson();
```

### Checking Item Existence

```php
// Check if specific product is in cart
if ($product->inCart()) {
    echo "Product is already in cart";
}

// Get cart item for product
$cartItem = $product->cartItem();
if ($cartItem) {
    echo "Quantity in cart: " . $cartItem->quantity;
}
```

### Conditional Logic

```php
// Free shipping over $50
if (Cart::subtotal() >= 50) {
    Cart::removeCondition('shipping');
} else {
    Cart::condition('shipping', 'fee', 5.99, 'fixed');
}

// Bulk discount
if (Cart::count() >= 10) {
    Cart::condition('bulk_discount', 'discount', 10, 'percentage');
}
```

### Item-Specific Conditions

```php
// Get item
$item = Cart::get($itemId);

// Apply item-specific discount
$item->applyConditions([
    [
        'name' => 'clearance',
        'type' => 'discount',
        'value' => 50,
        'target' => 'percentage',
    ]
]);
```

### Custom Identifier

```php
// In Service Provider or middleware
$storage = app(CartStorageInterface::class);
$customIdentifier = 'guest_' . request()->ip();
$cart = new Cart($storage, $customIdentifier);
```

## Best Practices

1. **Always validate input**: Validate quantities and prices before adding to cart
2. **Use transactions**: When processing orders, use database transactions
3. **Clear expired carts**: Set up a scheduled job to clean expired carts
4. **Validate stock**: Check product availability before checkout
5. **Secure prices**: Always fetch prices from database, not user input
6. **Handle concurrency**: Consider race conditions when updating quantities

## Example: Complete Checkout Flow

```php
use Saeedvir\ShoppingCart\Facades\Cart;
use Illuminate\Support\Facades\DB;

public function checkout(Request $request)
{
    // Validate cart is not empty
    if (Cart::isEmpty()) {
        return redirect()->back()->with('error', 'Cart is empty');
    }
    
    // Apply coupon if provided
    if ($request->coupon_code) {
        try {
            Cart::applyCoupon($request->coupon_code, function($code, $cart) {
                $coupon = Coupon::active()->where('code', $code)->first();
                if (!$coupon) return false;
                
                $cart->condition('coupon', 'discount', $coupon->value, $coupon->type);
                return true;
            });
        } catch (InvalidCouponException $e) {
            return redirect()->back()->with('error', 'Invalid coupon code');
        }
    }
    
    // Add shipping
    Cart::condition('shipping', 'fee', 9.99, 'fixed');
    
    // Create order
    DB::beginTransaction();
    try {
        $order = Order::create([
            'user_id' => auth()->id(),
            'subtotal' => Cart::subtotal(),
            'tax' => Cart::tax(),
            'discount' => Cart::discount(),
            'total' => Cart::total(),
        ]);
        
        foreach (Cart::items() as $item) {
            $order->items()->create([
                'product_id' => $item->buyableId,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'attributes' => $item->attributes,
            ]);
        }
        
        // Clear cart after successful order
        Cart::destroy();
        
        DB::commit();
        
        return redirect()->route('order.success', $order);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Order failed');
    }
}
```
