<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // ID do "cart" na fakestoreapi — chave natural do upsert idempotente.
            $table->unsignedBigInteger('external_id')->unique();
            $table->unsignedBigInteger('affiliate_id');
            // enum de status do pedido (máquina de estados no domínio).
            $table->enum('status', ['pending', 'approved', 'cancelled', 'refunded'])->default('pending');
            // valor total persistido (soma de quantity * unit_price dos itens) — evita
            // recalcular a cada leitura numa base que pode chegar a 500k pedidos.
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('affiliate_id')->references('id')->on('affiliates')->cascadeOnDelete();

            // Índice composto para o padrão de busca previsto:
            // filtragem por affiliate_id + status + created_at (created_at = data do pedido).
            $table->index(['affiliate_id', 'status', 'created_at'], 'orders_affiliate_status_created_idx');
            // Índice de apoio para métricas/cohort e filtros globais por status/data.
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
