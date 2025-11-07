<?php

namespace Saeedvir\ShoppingCart;

use Illuminate\Support\ServiceProvider;
use Saeedvir\ShoppingCart\Contracts\CartStorageInterface;
use Saeedvir\ShoppingCart\Storage\SessionStorage;
use Saeedvir\ShoppingCart\Storage\DatabaseStorage;
use Saeedvir\ShoppingCart\Support\ConfigCache;

class ShoppingCartServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/shopping-cart.php',
            'shopping-cart'
        );

        $this->app->singleton(CartStorageInterface::class, function ($app) {
            $driver = ConfigCache::storage();

            return match($driver) {
                'database' => new DatabaseStorage(),
                'session' => new SessionStorage(),
                default => new SessionStorage(),
            };
        });

        $this->app->singleton('cart', function ($app) {
            $storage = $app->make(CartStorageInterface::class);
            $identifier = $this->getIdentifier();

            return new Cart($storage, $identifier);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/shopping-cart.php' => config_path('shopping-cart.php'),
            ], 'shopping-cart-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations/2025_01_07_000001_create_shopping_cart_tables.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_shopping_cart_tables.php'),
                __DIR__.'/../database/migrations/2025_01_07_000002_add_cart_indexes.php' => database_path('migrations/'.date('Y_m_d_His', time()+1).'_add_cart_indexes.php'),
            ], 'shopping-cart-migrations');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get the cart identifier (session ID or user ID).
     */
    protected function getIdentifier(): string
    {
        // Use authenticated user ID if available, otherwise use session ID
        if (auth()->check()) {
            return 'user_'.auth()->id();
        }

        return session()->getId();
    }
}
