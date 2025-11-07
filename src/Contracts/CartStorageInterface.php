<?php

namespace Saeedvir\ShoppingCart\Contracts;

interface CartStorageInterface
{
    /**
     * Get cart data.
     */
    public function get(string $identifier, string $instance = 'default'): ?array;

    /**
     * Store cart data.
     */
    public function put(string $identifier, array $data, string $instance = 'default'): void;

    /**
     * Check if cart exists.
     */
    public function has(string $identifier, string $instance = 'default'): bool;

    /**
     * Remove cart.
     */
    public function forget(string $identifier, string $instance = 'default'): void;

    /**
     * Clear all carts.
     */
    public function flush(): void;
}
