<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle;

use Illuminate\Support\ServiceProvider;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentArchiveRepository;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentFulfillmentRepository;

/**
 * Laravel integration boundary for the commerce lifecycle package.
 */
final class CommerceLifecycleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/commerce-lifecycle.php', 'commerce-lifecycle');
        $this->app->bind(FulfillmentRepository::class, EloquentFulfillmentRepository::class);
        $this->app->bind(ArchiveRepository::class, EloquentArchiveRepository::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/commerce-lifecycle.php' => config_path('commerce-lifecycle.php'),
        ], 'commerce-lifecycle-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'commerce-lifecycle-migrations');
    }
}
