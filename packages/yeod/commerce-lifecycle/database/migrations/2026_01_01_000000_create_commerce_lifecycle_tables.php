<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commerce_fulfillments', static function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('order_id')->index();
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('commerce_fulfillment_lines', static function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('fulfillment_id')->index();
            $table->string('sku')->index();
            $table->unsignedInteger('ordered_quantity');
            $table->unsignedInteger('fulfilled_quantity')->default(0);
            $table->foreign('fulfillment_id')->references('id')->on('commerce_fulfillments')->cascadeOnDelete();
        });

        Schema::create('commerce_archives', static function (Blueprint $table): void {
            $table->id();
            $table->string('archivable_type')->index();
            $table->string('archivable_id')->index();
            $table->string('reason')->nullable();
            $table->string('archived_by')->nullable();
            $table->string('storage_location')->nullable();
            $table->json('snapshot');
            $table->timestamp('archived_at');
            $table->timestamp('restored_at')->nullable();
            $table->unique(['archivable_type', 'archivable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_archives');
        Schema::dropIfExists('commerce_fulfillment_lines');
        Schema::dropIfExists('commerce_fulfillments');
    }
};
