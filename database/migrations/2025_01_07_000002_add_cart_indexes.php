<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('shopping-cart.database.connection');
        $cartsTable = config('shopping-cart.database.carts_table', 'carts');
        $itemsTable = config('shopping-cart.database.cart_items_table', 'cart_items');

        // Add composite index for faster cart lookups
        Schema::connection($connection)->table($cartsTable, function (Blueprint $table) {
            // Index for finding active carts by identifier and instance
            $table->index(['identifier', 'instance', 'expires_at'], 'carts_lookup_index');
        });

        // Add indexes for cart items
        Schema::connection($connection)->table($itemsTable, function (Blueprint $table) {
            // Index for finding items by buyable (for inventory checks)
            $table->index(['buyable_type', 'buyable_id'], 'items_buyable_index');
            
            // Index for cart_id lookups (already exists via foreign key, but explicit is better)
            if (!Schema::hasColumn($table->getTable(), 'cart_id_index')) {
                $table->index('cart_id', 'items_cart_index');
            }
        });
    }

    public function down(): void
    {
        $connection = config('shopping-cart.database.connection');
        $cartsTable = config('shopping-cart.database.carts_table', 'carts');
        $itemsTable = config('shopping-cart.database.cart_items_table', 'cart_items');

        Schema::connection($connection)->table($cartsTable, function (Blueprint $table) {
            $table->dropIndex('carts_lookup_index');
        });

        Schema::connection($connection)->table($itemsTable, function (Blueprint $table) {
            $table->dropIndex('items_buyable_index');
            
            if (Schema::hasColumn($table->getTable(), 'cart_id_index')) {
                $table->dropIndex('items_cart_index');
            }
        });
    }
};
