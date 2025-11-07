<?php

namespace Saeedvir\ShoppingCart\Storage;

use Saeedvir\ShoppingCart\Contracts\CartStorageInterface;
use Saeedvir\ShoppingCart\Models\Cart;
use Saeedvir\ShoppingCart\Support\ConfigCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseStorage implements CartStorageInterface
{
    public function get(string $identifier, string $instance = 'default'): ?array
    {
        $query = Cart::where('identifier', $identifier)
            ->where('instance', $instance)
            ->active()
            ->with('items.buyable');

        if ($connection = ConfigCache::databaseConnection()) {
            $query->on($connection);
        }

        $cart = $query->first();

        if (!$cart) {
            return null;
        }

        $cart->refreshExpiration();

        return [
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'buyable_type' => $item->buyable_type,
                    'buyable_id' => $item->buyable_id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'attributes' => $item->attributes,
                    'conditions' => $item->conditions,
                    'tax_rate' => $item->tax_rate,
                    // 'buyable' => $item->buyable, // REMOVED - load on demand to save memory
                ];
            })->toArray(),
            'metadata' => $cart->metadata,
        ];
    }

    public function put(string $identifier, array $data, string $instance = 'default'): void
    {
        DB::transaction(function () use ($identifier, $data, $instance) {
            // Use updateOrCreate to handle both insert and update cases
            $cart = Cart::updateOrCreate(
                [
                    'identifier' => $identifier,
                    'instance' => $instance,
                ],
                [
                    'metadata' => $data['metadata'] ?? [],
                    'expires_at' => $this->getExpirationTime(),
                ]
            );

            // Refresh to get latest data
            $cart->refresh();

            if (isset($data['items']) && !empty($data['items'])) {
                // Get existing item IDs
                $existingIds = $cart->items()->pluck('id')->toArray();
                $newIds = collect($data['items'])->pluck('id')->filter()->toArray();
                
                // Delete items not in the new list
                $idsToDelete = array_diff($existingIds, $newIds);
                if (!empty($idsToDelete)) {
                    $cart->items()->whereIn('id', $idsToDelete)->delete();
                }

                // Separate items for insert vs update
                $itemsToInsert = [];
                $itemsToUpdate = [];

                foreach ($data['items'] as $item) {
                    $itemData = [
                        'cart_id' => $cart->id,
                        'buyable_type' => $item['buyable_type'],
                        'buyable_id' => $item['buyable_id'],
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'attributes' => json_encode($item['attributes'] ?? []),
                        'conditions' => json_encode($item['conditions'] ?? []),
                        'tax_rate' => $item['tax_rate'] ?? ConfigCache::taxDefaultRate(),
                        'updated_at' => now(),
                    ];

                    if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                        $itemsToUpdate[$item['id']] = $itemData;
                    } else {
                        $itemData['created_at'] = now();
                        $itemsToInsert[] = $itemData;
                    }
                }

                // Bulk insert new items
                if (!empty($itemsToInsert)) {
                    DB::table(ConfigCache::cartItemsTable())
                        ->insert($itemsToInsert);
                }

                // Bulk update existing items
                foreach ($itemsToUpdate as $id => $itemData) {
                    DB::table(ConfigCache::cartItemsTable())
                        ->where('id', $id)
                        ->update($itemData);
                }
            } else {
                // Clear all items if none provided
                $cart->items()->delete();
            }
        });
    }

    public function has(string $identifier, string $instance = 'default'): bool
    {
        $query = Cart::where('identifier', $identifier)
            ->where('instance', $instance)
            ->active();

        if ($connection = ConfigCache::databaseConnection()) {
            $query->on($connection);
        }

        return $query->exists();
    }

    public function forget(string $identifier, string $instance = 'default'): void
    {
        $query = Cart::where('identifier', $identifier)
            ->where('instance', $instance);

        if ($connection = ConfigCache::databaseConnection()) {
            $query->on($connection);
        }

        $query->delete();
    }

    public function flush(): void
    {
        Cart::truncate();
    }

    protected function getExpirationTime(): ?Carbon
    {
        if ($expiration = ConfigCache::expiration()) {
            return Carbon::now()->addMinutes($expiration);
        }

        return null;
    }
}
