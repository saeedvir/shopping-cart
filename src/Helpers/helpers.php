<?php

use Saeedvir\ShoppingCart\Helpers\Currency;

if (!function_exists('cart_currency')) {
    /**
     * Format amount with cart currency.
     */
    function cart_currency(float $amount, bool $includeSymbol = true): string
    {
        return Currency::format($amount, $includeSymbol);
    }
}

if (!function_exists('cart_currency_symbol')) {
    /**
     * Get cart currency symbol.
     */
    function cart_currency_symbol(): string
    {
        return Currency::symbol();
    }
}

if (!function_exists('cart_currency_code')) {
    /**
     * Get cart currency code.
     */
    function cart_currency_code(): string
    {
        return Currency::code();
    }
}
