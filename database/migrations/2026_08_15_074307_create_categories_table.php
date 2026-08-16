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
        Schema::create('categories', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->onDelete('restrict');
            $table->uuid('ref')
                ->unique()
                ->nullable()
                ->comment("reference to the original uuid in 1C");
            $table->integer('code')
                ->unique()
                ->nullable()
                ->comment("reference to the original code in 1C");
            $table->integer('code')->unique()->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();
            $table->text("comments")->nullable();
            $table->boolean('is_top')->default(false);
            $table->integer('menu_columns_count')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
//            $table->index(['parent_id'], 'categories_parent_id_index');
        });

//        DB::statement("CREATE INDEX categories_slug_uk_idx ON categories ((slug ->> 'uk'))");
//        DB::statement("CREATE INDEX categories_slug_en_idx ON categories ((slug ->> 'en'))");
//        DB::statement("CREATE INDEX categories_name_uk_ci_idx ON categories (lower(name ->> 'uk'))");
//        DB::statement("CREATE INDEX categories_name_en_ci_idx ON categories (lower(name ->> 'en'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
