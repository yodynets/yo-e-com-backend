<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', static function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict'); // products are archived, never hard-deleted

            $table->string('sku')->index();

            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('unit_price', 15, 4)->default(0.0);
            $table->decimal('discount', 15, 4)->default(0.0);
            $table->decimal('total', 15, 4)->default(0.0);

            $table->uuid('ref')
                ->unique()
                ->nullable()
                ->comment('reference to the original uuid in 1C');

            $table->integer('code')
                ->unique()
                ->nullable()
                ->comment('reference to the original code in 1C');

            $table->timestampsTz();

            $table->index(['order_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};