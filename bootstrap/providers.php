<?php

declare(strict_types = 1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\DashboardPanelProvider;

return [
    AppServiceProvider::class,
    Yeod\Shared\Infrastructure\Providers\SharedServiceProvider::class,
    // Modules (one line per module).
    Yeod\Modules\Catalog\CatalogServiceProvider::class,
    // Yeod\Modules\Orders\OrdersServiceProvider::class,
    // Yeod\Modules\Customers\CustomersServiceProvider::class,
    // Yeod\Modules\Payments\PaymentsServiceProvider::class,
    // Yeod\Modules\Inventory\InventoryServiceProvider::class,
    // Yeod\Modules\LegacyMigration\LegacyMigrationServiceProvider::class,

    // Filament panel LAST: it collects resources from the modules registered above.
    DashboardPanelProvider::class,
];
