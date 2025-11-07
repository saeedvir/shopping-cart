<?php

namespace Saeedvir\ShoppingCart\Storage;

use Saeedvir\ShoppingCart\Contracts\CartStorageInterface;
use Saeedvir\ShoppingCart\Support\ConfigCache;
use Illuminate\Support\Facades\Session;

class SessionStorage implements CartStorageInterface
{
    protected function getKey(string $identifier, string $instance): string
    {
        $baseKey = ConfigCache::sessionKey();
        return "{$baseKey}.{$identifier}.{$instance}";
    }

    public function get(string $identifier, string $instance = 'default'): ?array
    {
        return Session::get($this->getKey($identifier, $instance));
    }

    public function put(string $identifier, array $data, string $instance = 'default'): void
    {
        Session::put($this->getKey($identifier, $instance), $data);
    }

    public function has(string $identifier, string $instance = 'default'): bool
    {
        return Session::has($this->getKey($identifier, $instance));
    }

    public function forget(string $identifier, string $instance = 'default'): void
    {
        Session::forget($this->getKey($identifier, $instance));
    }

    public function flush(): void
    {
        $baseKey = ConfigCache::sessionKey();
        Session::forget($baseKey);
    }
}
