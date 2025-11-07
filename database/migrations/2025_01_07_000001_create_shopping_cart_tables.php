<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('shopping-cart.database.connection');
        
        Schema::connection($connection)->create(config('shopping-cart.database.carts_table', 'carts'), function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique(); // Session ID or User ID
            $table->string('instance')->default('default'); // Support multiple cart instances
            $table->json('metadata')->nullable(); // Additional cart data
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['identifier', 'instance']);
        });

        Schema::connection($connection)->create(config('shopping-cart.database.cart_items_table', 'cart_items'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained(config('shopping-cart.database.carts_table', 'carts'))->onDelete('cascade');
            $table->string('buyable_type'); // Polymorphic relation
            $table->unsignedBigInteger('buyable_id');
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2);
            $table->json('attributes')->nullable(); // Custom attributes (size, color, etc.)
            $table->json('conditions')->nullable(); // Item-specific conditions (discounts, taxes)
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->timestamps();

            $table->index(['buyable_type', 'buyable_id']);
        });
    }

    public function down(): void
    {
        $connection = config('shopping-cart.database.connection');
        
        Schema::connection($connection)->dropIfExists(config('shopping-cart.database.cart_items_table', 'cart_items'));
        Schema::connection($connection)->dropIfExists(config('shopping-cart.database.carts_table', 'carts'));
    }
};
