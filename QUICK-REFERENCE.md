# Quick Reference

## Installation

```bash
composer require saeedvir/shopping-cart
php artisan vendor:publish --tag=shopping-cart-config
php artisan vendor:publish --tag=shopping-cart-migrations # if using database
php artisan migrate # if using database
```

## Basic Operations

```php
use Saeedvir\ShoppingCart\Facades\Cart;

// Add item
Cart::add($product, $quantity, $attributes);

// Get items
Cart::items();

// Update item
Cart::update($itemId, ['quantity' => 5]);

// Remove item
Cart::remove($itemId);

// Clear cart
Cart::clear();
```

## Get Totals

```php
Cart::subtotal();  // Before tax
Cart::tax();       // Tax amount
Cart::discount();  // Discount amount
Cart::total();     // Final total
Cart::count();     // Item count
```

## Discounts & Coupons

```php
// Fixed discount
Cart::condition('sale', 'discount', 10.00, 'fixed');

// Percentage discount
Cart::condition('sale', 'discount', 20, 'percentage');

// Apply coupon
Cart::applyCoupon('CODE', $validatorCallback);

// Remove condition
Cart::removeCondition('sale');
```

## Multiple Instances

```php
Cart::instance('wishlist')->add($product);
Cart::instance('default')->items();
```

## Metadata

```php
Cart::setMetadata('note', 'Gift wrap');
Cart::getMetadata('note');
```

## Buyable Trait

```php
// In your model
use Saeedvir\ShoppingCart\Traits\Buyable;

class Product extends Model {
    use Buyable;
}

// Usage
$product->addToCart(2);
$product->inCart();
$product->removeFromCart();
```

## Configuration

**config/shopping-cart.php**

```php
'storage' => 'session', // or 'database'
'tax' => [
    'enabled' => true,
    'default_rate' => 0.15,
],
'expiration' => 10080, // minutes
```

## Item Properties

```php
$item->id
$item->name
$item->quantity
$item->price
$item->attributes
$item->taxRate
$item->getSubtotal()
$item->getTax()
$item->getTotal()
```

## Cart Array Structure

```php
Cart::toArray();

/*
[
    'identifier' => 'user_123',
    'instance' => 'default',
    'items' => [...],
    'count' => 5,
    'subtotal' => 100.00,
    'tax' => 15.00,
    'discount' => 10.00,
    'total' => 105.00,
    'conditions' => [...],
    'metadata' => [...],
]
*/
```

## Common Patterns

### Add with attributes
```php
Cart::add($product, 1, [
    'size' => 'L',
    'color' => 'Blue'
]);
```

### Conditional shipping
```php
if (Cart::subtotal() < 50) {
    Cart::condition('shipping', 'fee', 5.99);
}
```

### Move to cart from wishlist
```php
$item = Cart::instance('wishlist')->get($id);
Cart::instance('default')->add([
    'buyable_type' => $item->buyableType,
    'buyable_id' => $item->buyableId,
    'name' => $item->name,
    'price' => $item->price,
], $item->quantity);
Cart::instance('wishlist')->remove($id);
```
