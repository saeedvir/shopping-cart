<?php

namespace Saeedvir\ShoppingCart\Support;

use Illuminate\Support\Facades\Cache;

class ConfigCache
{
    /**
     * Memoize a config value for the duration of the request.
     */
    protected static function memo(string $key, callable $callback, int $ttl = 3600)
    {
        return Cache::memo()->remember("cart.config.{$key}", $ttl, $callback);
    }

    /**
     * Get currency decimals.
     */
    public static function currencyDecimals(): int
    {
        return self::memo('currency.decimals', 
            fn() => config('shopping-cart.currency.decimals', 2));
    }

    /**
     * Get currency symbol.
     */
    public static function currencySymbol(): string
    {
        return self::memo('currency.symbol',
            fn() => config('shopping-cart.currency.symbol', '$'));
    }

    /**
     * Get currency code.
     */
    public static function currencyCode(): string
    {
        return self::memo('currency.code',
            fn() => config('shopping-cart.currency.code', 'USD'));
    }

    /**
     * Get decimal separator.
     */
    public static function decimalSeparator(): string
    {
        return self::memo('currency.decimal_sep',
            fn() => config('shopping-cart.currency.decimal_separator', '.'));
    }

    /**
     * Get thousand separator.
     */
    public static function thousandSeparator(): string
    {
        return self::memo('currency.thousand_sep',
            fn() => config('shopping-cart.currency.thousand_separator', ','));
    }

    /**
     * Check if tax is enabled.
     */
    public static function taxEnabled(): bool
    {
        return self::memo('tax.enabled',
            fn() => config('shopping-cart.tax.enabled', true));
    }

    /**
     * Check if tax is included in price.
     */
    public static function taxIncluded(): bool
    {
        return self::memo('tax.included',
            fn() => config('shopping-cart.tax.included_in_price', false));
    }

    /**
     * Get default tax rate.
     */
    public static function taxDefaultRate(): float
    {
        return self::memo('tax.default_rate',
            fn() => (float) config('shopping-cart.tax.default_rate', 0));
    }

    /**
     * Get maximum items per cart.
     */
    public static function maxItems(): int
    {
        return self::memo('limits.max_items',
            fn() => config('shopping-cart.limits.max_items', 100));
    }

    /**
     * Get maximum quantity per item.
     */
    public static function maxQuantity(): int
    {
        return self::memo('limits.max_quantity',
            fn() => config('shopping-cart.limits.max_quantity_per_item', 999));
    }

    /**
     * Get database connection name.
     */
    public static function databaseConnection(): ?string
    {
        return self::memo('database.connection',
            fn() => config('shopping-cart.database.connection'));
    }

    /**
     * Get carts table name.
     */
    public static function cartsTable(): string
    {
        return self::memo('database.carts_table',
            fn() => config('shopping-cart.database.carts_table', 'carts'));
    }

    /**
     * Get cart items table name.
     */
    public static function cartItemsTable(): string
    {
        return self::memo('database.cart_items_table',
            fn() => config('shopping-cart.database.cart_items_table', 'cart_items'));
    }

    /**
     * Get session key.
     */
    public static function sessionKey(): string
    {
        return self::memo('session.key',
            fn() => config('shopping-cart.session.key', 'shopping_cart'));
    }

    /**
     * Get cart expiration time in minutes.
     */
    public static function expiration(): ?int
    {
        return self::memo('expiration',
            fn() => config('shopping-cart.expiration'));
    }

    /**
     * Get storage driver.
     */
    public static function storage(): string
    {
        return self::memo('storage',
            fn() => config('shopping-cart.storage', 'session'));
    }
}
