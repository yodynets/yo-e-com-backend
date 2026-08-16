<?php

use Yeod\CommerceLifecycle\CommerceLifecycleServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\DashboardPanelProvider::class,
    CommerceLifecycleServiceProvider::class,
];
