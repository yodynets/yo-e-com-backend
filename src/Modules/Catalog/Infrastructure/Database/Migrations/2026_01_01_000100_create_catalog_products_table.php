<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the Catalog module's product table.
 *
 * The migration lives inside the module and is loaded by `CatalogServiceProvider`,
 * so `php artisan migrate` picks it up without any central registration.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_products', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->bigInteger('price_minor_amount');
            $table->char('currency', 3);
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
