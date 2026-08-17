<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Infrastructure\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;
use Yeod\CommerceLifecycle\Application\Authorizer;
use Yeod\CommerceLifecycle\Application\DenyAllAuthorizer;
use Yeod\CommerceLifecycle\Application\Fulfillment\TransitionFulfillment;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;
use Yeod\CommerceLifecycle\Domain\Events\DomainEventDispatcher;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Infrastructure\Events\LaravelDomainEventDispatcher;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentArchiveRepository;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentFulfillmentRepository;

/**
 * Laravel integration boundary for the commerce lifecycle package.
 */
final class CommerceLifecycleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/commerce-lifecycle.php', 'commerce-lifecycle');

        $this->app->bind(FulfillmentRepository::class, static function (Application $app): EloquentFulfillmentRepository {
            return new EloquentFulfillmentRepository(
                $app->make('config')->integer('commerce-lifecycle.max_metadata_size', 65535),
            );
        });
        $this->app->bind(ArchiveRepository::class, EloquentArchiveRepository::class);
        $this->app->bind(DomainEventDispatcher::class, LaravelDomainEventDispatcher::class);

        $this->app->singleton(Authorizer::class, static function (Application $app): Authorizer {
            $concrete = $app->make('config')->string(
                'commerce-lifecycle.authorizer',
                DenyAllAuthorizer::class,
            );

            if (! is_a($concrete, Authorizer::class, true)) {
                throw new InvalidArgumentException(sprintf(
                    'commerce-lifecycle.authorizer must be a class-string implementing %s, got %s.',
                    Authorizer::class,
                    get_debug_type($concrete),
                ));
            }

            /** @var Authorizer $authorizer */
            $authorizer = $app->make($concrete);

            return $authorizer;
        });

        $this->app->bind(ArchiveService::class, static function (Application $app): ArchiveService {
            return new ArchiveService(
                $app->make(ArchiveRepository::class),
                $app->make('config')->integer('commerce-lifecycle.max_snapshot_size', 512),
                $app->make('config')->integer('commerce-lifecycle.max_reason_length', 1000),
                $app->make(Authorizer::class),
            );
        });

        $this->app->bind(TransitionFulfillment::class, static function (Application $app): TransitionFulfillment {
            return new TransitionFulfillment(
                $app->make(FulfillmentRepository::class),
                $app->make(DomainEventDispatcher::class),
                $app->make(Authorizer::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../../config/commerce-lifecycle.php' => config_path('commerce-lifecycle.php'),
        ], 'commerce-lifecycle-config');

        $this->publishesMigrations(
            [__DIR__.'/../../../database/migrations'],
            ['commerce-lifecycle-migrations'],
        );
    }
}
