<?php

namespace Saeedvir\ShoppingCart\Traits;

use Saeedvir\ShoppingCart\Facades\Cart;

trait Buyable
{
    /**
     * Add this model to the cart.
     */
    public function addToCart(int $quantity = 1, array $attributes = [])
    {
        return Cart::add($this, $quantity, $attributes);
    }

    /**
     * Check if this model is in the cart.
     */
    public function inCart(string $instance = 'default'): bool
    {
        return Cart::instance($instance)
            ->items()
            ->contains(function ($item) {
                return $item->buyableType === get_class($this)
                    && $item->buyableId === $this->id;
            });
    }

    /**
     * Remove this model from the cart.
     */
    public function removeFromCart(string $instance = 'default'): bool
    {
        $item = Cart::instance($instance)
            ->items()
            ->first(function ($item) {
                return $item->buyableType === get_class($this)
                    && $item->buyableId === $this->id;
            });

        if ($item) {
            return Cart::instance($instance)->remove($item->id);
        }

        return false;
    }

    /**
     * Get the cart item for this model.
     */
    public function cartItem(string $instance = 'default')
    {
        return Cart::instance($instance)
            ->items()
            ->first(function ($item) {
                return $item->buyableType === get_class($this)
                    && $item->buyableId === $this->id;
            });
    }
}
