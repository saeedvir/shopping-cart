<?php

namespace Saeedvir\ShoppingCart\Helpers;

use Saeedvir\ShoppingCart\Support\ConfigCache;

class Currency
{
    /**
     * Format a price according to currency settings.
     */
    public static function format(float $amount, bool $includeSymbol = true): string
    {
        $decimals = ConfigCache::currencyDecimals();
        $decimalSeparator = ConfigCache::decimalSeparator();
        $thousandSeparator = ConfigCache::thousandSeparator();
        
        $formatted = number_format($amount, $decimals, $decimalSeparator, $thousandSeparator);
        
        if ($includeSymbol) {
            $symbol = ConfigCache::currencySymbol();
            return $symbol . $formatted;
        }
        
        return $formatted;
    }
    
    /**
     * Get currency code.
     */
    public static function code(): string
    {
        return ConfigCache::currencyCode();
    }
    
    /**
     * Get currency symbol.
     */
    public static function symbol(): string
    {
        return ConfigCache::currencySymbol();
    }
    
    /**
     * Format with currency code.
     */
    public static function formatWithCode(float $amount): string
    {
        $formatted = self::format($amount, false);
        $code = self::code();
        
        return "{$formatted} {$code}";
    }
}
