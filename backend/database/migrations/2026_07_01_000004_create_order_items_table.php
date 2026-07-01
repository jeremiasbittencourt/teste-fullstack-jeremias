<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity')->default(1);
            // preço unitário congelado no momento do pedido (snapshot do preço do produto).
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            // Um produto aparece no máximo uma vez por pedido → chave natural do upsert.
            $table->unique(['order_id', 'product_id'], 'order_items_order_product_unique');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
