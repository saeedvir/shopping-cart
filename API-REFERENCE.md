# API Reference

## Cart Facade

### `Cart::instance(string $instance): Cart`

Set the cart instance to use.

```php
Cart::instance('wishlist')->add($product);
```

**Parameters:**
- `$instance` (string): Instance name (e.g., 'default', 'wishlist', 'comparison')

**Returns:** Cart instance

---

### `Cart::add($buyable, int $quantity = 1, array $attributes = []): CartItem`

Add an item to the cart.

```php
Cart::add($product, 2, ['size' => 'L']);
```

**Parameters:**
- `$buyable` (Model|array): Product model or array with cart item data
- `$quantity` (int): Quantity to add (default: 1)
- `$attributes` (array): Custom attributes (e.g., size, color)

**Returns:** CartItem instance

---

### `Cart::update(string $itemId, array $data): ?CartItem`

Update an item in the cart.

```php
Cart::update($itemId, ['quantity' => 5, 'price' => 99.99]);
```

**Parameters:**
- `$itemId` (string): Cart item ID
- `$data` (array): Data to update (quantity, price, attributes)

**Returns:** CartItem instance or null if not found

---

### `Cart::remove(string $itemId): bool`

Remove an item from the cart.

```php
Cart::remove($itemId);
```

**Parameters:**
- `$itemId` (string): Cart item ID

**Returns:** Boolean success status

---

### `Cart::get(string $itemId): ?CartItem`

Get a specific cart item.

```php
$item = Cart::get($itemId);
```

**Parameters:**
- `$itemId` (string): Cart item ID

**Returns:** CartItem instance or null

---

### `Cart::items(): Collection`

Get all cart items.

```php
$items = Cart::items();
```

**Returns:** Collection of CartItem instances

---

### `Cart::count(): int`

Get total item count (sum of all quantities).

```php
$count = Cart::count();
```

**Returns:** Integer count

---

### `Cart::isEmpty(): bool`

Check if cart is empty.

```php
if (Cart::isEmpty()) {
    // Cart is empty
}
```

**Returns:** Boolean

---

### `Cart::clear(): void`

Clear all items from the cart.

```php
Cart::clear();
```

---

### `Cart::destroy(): void`

Destroy the cart (remove from storage).

```php
Cart::destroy();
```

---

### `Cart::condition(string $name, string $type, float $value, string $target = 'subtotal', array $rules = []): Cart`

Apply a condition (discount, fee, tax).

```php
Cart::condition('holiday_sale', 'discount', 20, 'percentage');
Cart::condition('shipping', 'fee', 5.99, 'fixed');
```

**Parameters:**
- `$name` (string): Unique condition name
- `$type` (string): Condition type ('discount', 'fee', 'tax')
- `$value` (float): Condition value
- `$target` (string): Target for calculation ('subtotal', 'total', 'percentage', 'fixed')
- `$rules` (array): Optional rules for condition

**Returns:** Cart instance (chainable)

---

### `Cart::applyCoupon(string $code, callable $validator = null): Cart`

Apply a coupon code.

```php
Cart::applyCoupon('SAVE20', function($code, $cart) {
    $coupon = Coupon::where('code', $code)->first();
    if (!$coupon) return false;
    
    $cart->condition('coupon', 'discount', $coupon->value, 'percentage');
    return true;
});
```

**Parameters:**
- `$code` (string): Coupon code
- `$validator` (callable): Optional validation callback

**Returns:** Cart instance

**Throws:** InvalidCouponException if validation fails

---

### `Cart::removeCondition(string $name): Cart`

Remove a specific condition.

```php
Cart::removeCondition('coupon');
```

**Parameters:**
- `$name` (string): Condition name to remove

**Returns:** Cart instance

---

### `Cart::subtotal(): float`

Get cart subtotal (before tax and conditions).

```php
$subtotal = Cart::subtotal();
```

**Returns:** Float amount

---

### `Cart::tax(): float`

Get total tax amount.

```php
$tax = Cart::tax();
```

**Returns:** Float amount

---

### `Cart::discount(): float`

Get total discount amount.

```php
$discount = Cart::discount();
```

**Returns:** Float amount

---

### `Cart::total(): float`

Get cart total (subtotal + tax - discount + fees).

```php
$total = Cart::total();
```

**Returns:** Float amount

---

### `Cart::setMetadata(string $key, $value): Cart`

Set cart metadata.

```php
Cart::setMetadata('gift_message', 'Happy Birthday!');
```

**Parameters:**
- `$key` (string): Metadata key
- `$value` (mixed): Metadata value

**Returns:** Cart instance

---

### `Cart::getMetadata(string $key = null): mixed`

Get cart metadata.

```php
$message = Cart::getMetadata('gift_message');
$all = Cart::getMetadata(); // Get all metadata
```

**Parameters:**
- `$key` (string|null): Metadata key or null for all

**Returns:** Mixed value or array

---

### `Cart::toArray(): array`

Convert cart to array.

```php
$data = Cart::toArray();
```

**Returns:** Array with cart data

---

### `Cart::toJson(int $options = 0): string`

Convert cart to JSON.

```php
$json = Cart::toJson();
```

**Parameters:**
- `$options` (int): JSON encode options

**Returns:** JSON string

---

## CartItem Methods

### `$item->getSubtotal(): float`

Get item subtotal (price × quantity).

```php
$subtotal = $item->getSubtotal();
```

---

### `$item->getTax(): float`

Get item tax amount.

```php
$tax = $item->getTax();
```

---

### `$item->getTotal(): float`

Get item total (including tax).

```php
$total = $item->getTotal();
```

---

### `$item->getCustomAttribute(string $key): mixed`

Get custom attribute value.

```php
$size = $item->getCustomAttribute('size');
```

---

### `$item->toArray(): array`

Convert item to array.

```php
$data = $item->toArray();
```

---

## Buyable Trait

### `$model->addToCart(int $quantity = 1, array $attributes = []): CartItem`

Add model to cart.

```php
$product->addToCart(2, ['size' => 'L']);
```

---

### `$model->inCart(string $instance = 'default'): bool`

Check if model is in cart.

```php
if ($product->inCart()) {
    // Product is in cart
}
```

---

### `$model->removeFromCart(string $instance = 'default'): bool`

Remove model from cart.

```php
$product->removeFromCart();
```

---

### `$model->cartItem(string $instance = 'default'): ?CartItem`

Get cart item for this model.

```php
$cartItem = $product->cartItem();
echo $cartItem->quantity;
```

---

## Configuration Options

### Storage

```php
'storage' => 'session', // 'session' or 'database'
```

### Tax Settings

```php
'tax' => [
    'enabled' => true,
    'default_rate' => 0.15,
    'included_in_price' => false,
],
```

### Currency

```php
'currency' => [
    'code' => 'USD',
    'symbol' => '$',
    'decimals' => 2,
],
```

### Expiration

```php
'expiration' => 10080, // Minutes (7 days)
```
