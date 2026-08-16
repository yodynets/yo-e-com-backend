<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Yeod\CommerceLifecycle\Domain\Order\OrderStatus;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->onDelete('restrict');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->onDelete('restrict');
            $table->string('invoice_no')->unique();
            $table->timestampTz('purchased_at')->nullable();
            $table->decimal('total_amount', 15, 4)->default(0.0);
            $table->decimal('paid_amount', 15, 4)->default(0.0);
            $table->decimal('due_amount', 15, 4)->default(0.0);
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
