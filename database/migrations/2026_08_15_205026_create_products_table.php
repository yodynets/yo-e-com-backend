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
        Schema::create('products', static function (Blueprint $table) {
            $table->id();
            $table->uuid('ref')
                ->unique()
                ->nullable()
                ->comment("reference to the original uuid in 1C");
            $table->integer('code')
                ->unique()
                ->nullable()
                ->comment("reference to the original code in 1C");
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->string('sku')->unique()->nullable();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->decimal('cost_price', 15, 4)->default(0.0);
            $table->decimal('selling_price', 15, 4)->default(0.0);
            $table->text('description')->nullable();
            $table->text("comments")->nullable();
            $table->string('image')->nullable();

            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
