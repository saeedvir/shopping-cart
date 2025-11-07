<?php

namespace Saeedvir\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Saeedvir\ShoppingCart\Support\ConfigCache;

class Cart extends Model
{
    protected $fillable = [
        'identifier',
        'instance',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(ConfigCache::cartsTable());
        
        if ($connection = ConfigCache::databaseConnection()) {
            $this->setConnection($connection);
        }
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return Carbon::now()->greaterThan($this->expires_at);
    }

    public function refreshExpiration(): void
    {
        if ($expiration = ConfigCache::expiration()) {
            $this->expires_at = Carbon::now()->addMinutes($expiration);
            $this->save();
        }
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }
}
