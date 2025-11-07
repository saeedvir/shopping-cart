# Installation Guide

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0

## Step 1: Install via Composer

```bash
composer require saeedvir/shopping-cart
```

## Step 2: Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=shopping-cart-config
```

This will create `config/shopping-cart.php` in your Laravel application.

## Step 3: Configure Storage

### Option A: Session Storage (Default)

No additional setup required. The cart will use Laravel's session storage.

### Option B: Database Storage

1. Publish the migrations:

```bash
php artisan vendor:publish --tag=shopping-cart-migrations
```

2. Run the migrations:

```bash
php artisan migrate
```

3. Update your `.env` file:

```env
CART_STORAGE=database
```

Or update `config/shopping-cart.php`:

```php
'storage' => 'database',
```

## Step 4: Configure Tax Settings (Optional)

Update `config/shopping-cart.php`:

```php
'tax' => [
    'enabled' => true,
    'default_rate' => 0.15, // 15% tax rate
    'included_in_price' => false,
],
```

## Step 5: Configure Currency (Optional)

Update `config/shopping-cart.php`:

```php
'currency' => [
    'code' => 'USD',
    'symbol' => '$',
    'decimals' => 2,
    'decimal_separator' => '.',
    'thousand_separator' => ',',
],
```

Or use environment variables:

```env
CART_CURRENCY=USD
CART_CURRENCY_SYMBOL=$
```

## Step 6: Add Buyable Trait to Models (Optional)

Add the `Buyable` trait to any model you want to add to the cart:

```php
use Saeedvir\ShoppingCart\Traits\Buyable;

class Product extends Model
{
    use Buyable;
    
    // Your model code...
}
```

## Verification

Test that the installation was successful:

```php
use Saeedvir\ShoppingCart\Facades\Cart;

// Add a test item
Cart::add([
    'buyable_type' => 'Product',
    'buyable_id' => 1,
    'name' => 'Test Product',
    'price' => 99.99,
], 1);

// Verify item was added
$count = Cart::count();
dd($count); // Should output: 1
```

## Next Steps

- Read the [README.md](README.md) for usage examples
- Review [USAGE.md](USAGE.md) for detailed feature documentation
- Check out example implementations in the `examples/` directory

## Troubleshooting

### Session Issues

If you're having issues with session storage, make sure your Laravel session is configured correctly:

```bash
php artisan config:cache
php artisan session:table
php artisan migrate
```

### Database Issues

If migrations fail, check your database connection in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Cache Issues

Clear your application cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```
