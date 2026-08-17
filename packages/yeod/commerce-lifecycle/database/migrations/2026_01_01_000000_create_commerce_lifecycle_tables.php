<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_fulfillments', static function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('order_id')->index();
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('commerce_fulfillment_lines', static function (Blueprint $table): void {
            $table->string('id');
            $table->string('fulfillment_id');
            $table->string('sku')->index();
            $table->unsignedInteger('ordered_quantity');
            $table->unsignedInteger('fulfilled_quantity')->default(0);

            // Line ids are unique only within an aggregate (see Fulfillment domain
            // constructor), so the primary key must be composite.
            $table->primary(['fulfillment_id', 'id']);
            $table->foreign('fulfillment_id')->references('id')->on('commerce_fulfillments')->cascadeOnDelete();
        });

        // DB-level guard that fulfilled_quantity never exceeds ordered_quantity.
        // Laravel's schema builder has no cross-driver CHECK support, so it is
        // added per-driver; SQLite cannot ALTER-add a CHECK (only at CREATE time)
        // and already relies on the domain invariant enforced by FulfillmentLine.
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlsrv'], true)) {
            DB::statement(
                'ALTER TABLE commerce_fulfillment_lines'
                .' ADD CONSTRAINT commerce_fulfillment_lines_fulfilled_le_ordered'
                .' CHECK (fulfilled_quantity <= ordered_quantity)'
            );
        }

        Schema::create('commerce_archives', static function (Blueprint $table): void {
            $table->id();
            $table->string('archivable_type')->index();
            $table->string('archivable_id')->index();
            $table->unsignedInteger('snapshot_version')->default(1);
            $table->string('reason')->nullable();
            $table->string('archived_by')->nullable();
            $table->string('storage_location')->nullable();
            $table->json('snapshot');
            $table->timestamp('archived_at');
            $table->timestamp('restored_at')->nullable();
            // Append-only history: each archive() call creates a new versioned row
            // rather than overwriting the previous snapshot.
            $table->index(['archivable_type', 'archivable_id', 'archived_at']);
            $table->unique(['archivable_type', 'archivable_id', 'snapshot_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_archives');
        Schema::dropIfExists('commerce_fulfillment_lines');
        Schema::dropIfExists('commerce_fulfillments');
    }
};
