<?php

namespace Saeedvir\ShoppingCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Saeedvir\ShoppingCart\Cart instance(string $instance)
 * @method static \Saeedvir\ShoppingCart\CartItem add($buyable, int $quantity = 1, array $attributes = [])
 * @method static \Saeedvir\ShoppingCart\CartItem|null update(string $itemId, array $data)
 * @method static bool remove(string $itemId)
 * @method static \Saeedvir\ShoppingCart\CartItem|null get(string $itemId)
 * @method static \Illuminate\Support\Collection items()
 * @method static int count()
 * @method static bool isEmpty()
 * @method static void clear()
 * @method static void destroy()
 * @method static \Saeedvir\ShoppingCart\Cart condition(string $name, string $type, float $value, string $target = 'subtotal', array $rules = [])
 * @method static \Saeedvir\ShoppingCart\Cart applyCoupon(string $code, callable $validator = null)
 * @method static \Saeedvir\ShoppingCart\Cart removeCondition(string $name)
 * @method static float subtotal()
 * @method static float tax()
 * @method static float discount()
 * @method static float total()
 * @method static \Saeedvir\ShoppingCart\Cart setMetadata(string $key, $value)
 * @method static mixed getMetadata(string $key = null)
 * @method static array toArray()
 * @method static string toJson(int $options = 0)
 *
 * @see \Saeedvir\ShoppingCart\Cart
 */
class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cart';
    }
}
