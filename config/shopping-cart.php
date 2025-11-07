<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage driver that will be used
    | to store cart data. You may set this to "session" or "database".
    |
    | Supported: "session", "database"
    |
    */

    'storage' => env('CART_STORAGE', 'session'),

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Configure the database tables and connection when using database storage.
    |
    */

    'database' => [
        'connection' => env('CART_DB_CONNECTION', null),
        'carts_table' => 'carts',
        'cart_items_table' => 'cart_items',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    |
    | Configure the session key when using session storage.
    |
    */

    'session' => [
        'key' => 'shopping_cart',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    |
    | Configure default tax settings for cart items.
    |
    */

    'tax' => [
        'enabled' => true,
        'default_rate' => 0.0, // Default tax rate (e.g., 0.15 for 15%)
        'included_in_price' => false, // Whether tax is included in item price
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Configure currency formatting.
    |
    */

    'currency' => [
        'code' => env('CART_CURRENCY', 'USD'),
        'symbol' => env('CART_CURRENCY_SYMBOL', '$'),
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousand_separator' => ',',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Expiration
    |--------------------------------------------------------------------------
    |
    | Set the expiration time for inactive carts (in minutes).
    | Set to null for no expiration.
    |
    */

    'expiration' => env('CART_EXPIRATION', 10080), // 7 days

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable cart events.
    | Note: Event system is reserved for future implementation.
    |
    */

    'events' => [
        'enabled' => true, // Reserved for future use
    ],

    /*
    |--------------------------------------------------------------------------
    | Conditions
    |--------------------------------------------------------------------------
    |
    | Configure default conditions (discounts, fees, etc.).
    | Note: Condition ordering is reserved for future advanced features.
    |
    */

    'conditions' => [
        'apply_order' => ['discount', 'tax', 'fee'], // Reserved for future use
    ],

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

];
