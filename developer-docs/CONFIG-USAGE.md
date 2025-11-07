# Configuration Usage Reference

This document details how each configuration item in `config/shopping-cart.php` is used throughout the package.

## ✅ Fully Implemented Config Items

### `storage`
**Config:** `shopping-cart.storage`  
**Used in:**
- `ShoppingCartServiceProvider.php` - Determines which storage driver to use
- **Values:** `'session'` or `'database'`

### `database.connection`
**Config:** `shopping-cart.database.connection`  
**Used in:**
- `Models/Cart.php` - Sets database connection for Cart model
- `Models/CartItem.php` - Sets database connection for CartItem model
- `Storage/DatabaseStorage.php` - Uses connection for all database queries
- `database/migrations/create_shopping_cart_tables.php` - Uses connection for migrations
- **Default:** `null` (uses default Laravel connection)

### `database.carts_table`
**Config:** `shopping-cart.database.carts_table`  
**Used in:**
- `Models/Cart.php` - Sets table name
- `database/migrations/create_shopping_cart_tables.php` - Creates table
- **Default:** `'carts'`

### `database.cart_items_table`
**Config:** `shopping-cart.database.cart_items_table`  
**Used in:**
- `Models/CartItem.php` - Sets table name
- `database/migrations/create_shopping_cart_tables.php` - Creates table
- **Default:** `'cart_items'`

### `session.key`
**Config:** `shopping-cart.session.key`  
**Used in:**
- `Storage/SessionStorage.php` - Session key prefix for storing cart data
- **Default:** `'shopping_cart'`

### `tax.enabled`
**Config:** `shopping-cart.tax.enabled`  
**Used in:**
- `Models/CartItem.php` - getTaxAttribute() method
- `CartItem.php` - getTax() method
- **Default:** `true`

### `tax.default_rate`
**Config:** `shopping-cart.tax.default_rate`  
**Used in:**
- `Storage/DatabaseStorage.php` - Default tax rate when creating items
- `CartItem.php` - Default tax rate in constructor
- **Example:** `0.15` (15% tax)

### `tax.included_in_price`
**Config:** `shopping-cart.tax.included_in_price`  
**Used in:**
- `Models/CartItem.php` - getTaxAttribute() and getTotalAttribute() methods
- `CartItem.php` - getTax() and getTotal() methods
- **Default:** `false`

### `currency.code`
**Config:** `shopping-cart.currency.code`  
**Used in:**
- `Helpers/Currency.php` - code() method
- `Helpers/helpers.php` - cart_currency_code() helper
- **Default:** `'USD'`

### `currency.symbol`
**Config:** `shopping-cart.currency.symbol`  
**Used in:**
- `Helpers/Currency.php` - symbol() and format() methods
- `Helpers/helpers.php` - cart_currency_symbol() helper
- **Default:** `'$'`

### `currency.decimals`
**Config:** `shopping-cart.currency.decimals`  
**Used in:**
- `Helpers/Currency.php` - format() method for number formatting
- **Default:** `2`

### `currency.decimal_separator`
**Config:** `shopping-cart.currency.decimal_separator`  
**Used in:**
- `Helpers/Currency.php` - format() method for decimal separator
- **Default:** `'.'`

### `currency.thousand_separator`
**Config:** `shopping-cart.currency.thousand_separator`  
**Used in:**
- `Helpers/Currency.php` - format() method for thousand separator
- **Default:** `','`

### `expiration`
**Config:** `shopping-cart.expiration`  
**Used in:**
- `Models/Cart.php` - refreshExpiration() method
- `Storage/DatabaseStorage.php` - getExpirationTime() method
- **Default:** `10080` (7 days in minutes)

## ⏳ Reserved for Future Implementation

### `events.enabled`
**Config:** `shopping-cart.events.enabled`  
**Status:** Reserved for future event system implementation  
**Planned Usage:** Enable/disable cart event firing (CartItemAdded, CartCleared, etc.)

### `conditions.apply_order`
**Config:** `shopping-cart.conditions.apply_order`  
**Status:** Reserved for advanced condition ordering  
**Planned Usage:** Define the order in which conditions (discount, tax, fee) are applied

## Usage Examples

### Using Currency Helpers

```php
use Saeedvir\ShoppingCart\Facades\Cart;

// Get formatted amounts
echo Cart::formattedSubtotal(); // "$99.99"
echo Cart::formattedTax();      // "$15.00"
echo Cart::formattedTotal();    // "$114.99"

// Per-item formatting
$item = Cart::items()->first();
echo $item->formattedPrice();    // "$99.99"
echo $item->formattedSubtotal(); // "$199.98"

// Using helpers
echo cart_currency(99.99);        // "$99.99"
echo cart_currency_symbol();      // "$"
echo cart_currency_code();        // "USD"
```

### Using Database Connection

```php
// In .env file
CART_DB_CONNECTION=mysql_secondary

// Package will automatically use this connection for all cart operations
```

### Configuring Tax

```php
// In config/shopping-cart.php
'tax' => [
    'enabled' => true,
    'default_rate' => 0.15, // 15%
    'included_in_price' => false,
],

// Or in .env
CART_STORAGE=database
CART_EXPIRATION=4320 // 3 days
```

## Summary

✅ **Implemented:** 15/17 config items (88%)  
⏳ **Reserved:** 2/17 config items (12%)

All essential configuration items are fully implemented and integrated into the package logic. The reserved items are placeholders for future advanced features.
