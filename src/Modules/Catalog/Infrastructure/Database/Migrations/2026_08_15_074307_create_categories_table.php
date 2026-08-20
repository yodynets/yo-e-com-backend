<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->uuid('ref')->unique()->nullable()
                ->comment('reference to the original uuid in 1C');
            $table->integer('code')->unique()->nullable()
                ->comment('reference to the original code in 1C');

            $table->boolean('is_active')->default(true);
            $table->jsonb('name');
            $table->jsonb('slug');
            $table->jsonb('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->jsonb('image')->nullable();
            $table->char('comment', 1024)->nullable();
            $table->boolean('is_top')->default(false);
            $table->integer('menu_columns_count')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index('parent_id', 'categories_parent_id_index');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("create index categories_slug_uk_idx on categories using btree (((slug ->> 'uk'::text)))");
        DB::statement("create index categories_slug_en_idx on categories using btree (((slug ->> 'en'::text)))");
        DB::statement(
            "create index categories_name_uk_ci_idx on categories using btree ((lower((name ->> 'uk'::text))))"
        );
        DB::statement(
            "create index categories_name_en_ci_idx on categories using btree ((lower((name ->> 'en'::text))))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
