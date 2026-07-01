<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            // ID do "user" na fakestoreapi — chave natural usada no upsert idempotente.
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
