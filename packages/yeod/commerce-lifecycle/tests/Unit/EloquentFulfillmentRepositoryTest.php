<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\CommerceLifecycleException;
use Yeod\CommerceLifecycle\Exceptions\InvalidArgumentException;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;
use Yeod\CommerceLifecycle\Exceptions\StaleAggregateException;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentFulfillmentRepository;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\FulfillmentLineModel;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\FulfillmentModel;

/**
 * Integration tests for the Eloquent fulfillment repository against an
 * in-memory SQLite database (requires the pdo_sqlite extension).
 *
 * These tests prove the optimistic concurrency control behavior: a save only
 * succeeds when the stored version matches the version the aggregate was
 * loaded with.
 */
final class EloquentFulfillmentRepositoryTest extends TestCase
{
    private static ?EloquentFulfillmentRepository $repository = null;

    /**
     * @throws Throwable
     */
    public function test_round_trip_persists_and_reloads_aggregate(): void
    {
        $fulfillment = $this->fulfillment();
        $this->repository()->save($fulfillment);

        $loaded = $this->repository()->find('ful-1');
        self::assertNotNull($loaded);
        self::assertSame('ord-1', $loaded->orderId());
        self::assertSame(FulfillmentStatus::Scheduled, $loaded->status());
        self::assertSame(1, $loaded->version());
        self::assertCount(2, $loaded->lines());
        self::assertSame('sku-1', $loaded->lines()[0]->sku());
        self::assertSame(3, $loaded->lines()[0]->orderedQuantity());
    }

    private function fulfillment(): Fulfillment
    {
        return Fulfillment::create('ful-1', 'ord-1', [
            new FulfillmentLine('l1', 'sku-1', 3),
            new FulfillmentLine('l2', 'sku-2', 2),
        ]);
    }

    private function repository(): EloquentFulfillmentRepository
    {
        return self::$repository ?? throw new \LogicException('Repository was not booted.');
    }

    /**
     * @throws Throwable
     */
    public function test_resaving_same_instance_without_reload_is_allowed(): void
    {
        $fulfillment = $this->fulfillment();
        $this->repository()->save($fulfillment);
        $this->repository()->save($fulfillment);

        $loaded = $this->repository()->find('ful-1');
        self::assertNotNull($loaded);
        self::assertSame(2, $loaded->version());
    }

    /**
     * @throws Throwable
     * @throws InvalidTransitionException
     */
    public function test_reloaded_aggregate_increments_version_on_save(): void
    {
        $fulfillment = $this->fulfillment();
        $this->repository()->save($fulfillment);

        $loaded = $this->repository()->find('ful-1');
        self::assertNotNull($loaded);
        self::assertSame(1, $loaded->version());

        $loaded->changeStatus(FulfillmentStatus::Unfulfilled);
        $this->repository()->save($loaded);

        $reloaded = $this->repository()->find('ful-1');
        self::assertNotNull($reloaded);
        self::assertSame(2, $reloaded->version());
        self::assertSame(FulfillmentStatus::Unfulfilled, $reloaded->status());
    }

    /**
     * @throws Throwable
     * @throws InvalidTransitionException
     */
    public function test_concurrent_update_is_rejected(): void
    {
        $fulfillment = $this->fulfillment();
        $this->repository()->save($fulfillment);

        $first = $this->repository()->find('ful-1');
        $second = $this->repository()->find('ful-1');
        self::assertNotNull($first);
        self::assertNotNull($second);

        $first->changeStatus(FulfillmentStatus::Unfulfilled);
        $this->repository()->save($first);

        $second->changeStatus(FulfillmentStatus::OnHold);

        $this->expectException(StaleAggregateException::class);
        $this->repository()->save($second);
    }

    /**
     * @throws Throwable
     */
    public function test_same_line_ids_across_aggregates_is_allowed(): void
    {
        $first = Fulfillment::create('ful-1', 'ord-1', [
            new FulfillmentLine('line-1', 'sku-1', 3),
            new FulfillmentLine('line-2', 'sku-2', 2),
        ]);
        $this->repository()->save($first);

        $second = Fulfillment::create('ful-2', 'ord-2', [
            new FulfillmentLine('line-1', 'sku-3', 1),
            new FulfillmentLine('line-2', 'sku-4', 4),
        ]);
        $this->repository()->save($second);

        $firstLoaded = $this->repository()->find('ful-1');
        $secondLoaded = $this->repository()->find('ful-2');
        self::assertNotNull($firstLoaded);
        self::assertNotNull($secondLoaded);
        self::assertCount(2, $firstLoaded->lines());
        self::assertCount(2, $secondLoaded->lines());
        self::assertSame('sku-1', $firstLoaded->lines()[0]->sku());
        self::assertSame('sku-3', $secondLoaded->lines()[0]->sku());
    }

    /**
     * A failed write must not advance the aggregate's in-memory version, so the
     * aggregate stays coherent with the persisted row (no bogus StaleAggregate
     * on the caller's next save).
     *
     * @throws Throwable
     * @throws InvalidTransitionException
     */
    public function test_failed_save_does_not_desynchronize_in_memory_version(): void
    {
        $fulfillment = $this->fulfillment();
        $this->repository()->save($fulfillment);

        $first = $this->repository()->find('ful-1');
        $second = $this->repository()->find('ful-1');
        self::assertNotNull($first);
        self::assertNotNull($second);

        $first->changeStatus(FulfillmentStatus::Unfulfilled);
        $this->repository()->save($first);

        // second is now stale (still loaded at the pre-update version).
        $versionBefore = $second->version();

        try {
            $second->changeStatus(FulfillmentStatus::OnHold);
            $this->repository()->save($second);
            self::fail('Expected StaleAggregateException was not thrown.');
        } catch (StaleAggregateException) {
            // expected
        }

        self::assertSame($versionBefore, $second->version(), 'A failed save must not bump the in-memory version.');
    }

    public function test_corrupt_stored_status_raises_package_exception(): void
    {
        $this->repository()->save($this->fulfillment());

        // Bypass the model cast so the corrupt value actually reaches storage.
        DB::table('commerce_fulfillments')->where('id', 'ful-1')->update(['status' => 'not_a_real_status']);

        try {
            $this->repository()->find('ful-1');
            self::fail('Expected CommerceLifecycleException was not thrown.');
        } catch (CommerceLifecycleException $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }

    public function test_oversized_metadata_is_rejected_on_save(): void
    {
        $fulfillment = Fulfillment::create(
            'ful-1',
            'ord-1',
            [new FulfillmentLine('l1', 'sku-1', 1)],
            metadata: ['blob' => str_repeat('x', 65536)],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->repository()->save($fulfillment);
    }

    protected function tearDown(): void
    {
        FulfillmentLineModel::query()->delete();
        FulfillmentModel::query()->delete();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$repository === null) {
            self::bootDatabase();
            self::$repository = new EloquentFulfillmentRepository;
        }

        FulfillmentLineModel::query()->delete();
        FulfillmentModel::query()->delete();
    }

    /**
     * Reset the global facade application after all tests so the shared Capsule
     * container does not leak into other test classes in the same process.
     */
    public static function tearDownAfterClass(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        self::$repository = null;

        parent::tearDownAfterClass();
    }

    private static function bootDatabase(): void
    {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // The Capsule's container is used as the facade application so the `Schema::`
        // and `DB::` facades used by the migration and the repository resolve from
        // it. At runtime the method accepts any ArrayAccess container; the `@var`
        // just satisfies the facade's documented (?Application) contract.
        /** @var Application $container */
        $container = $capsule->getContainer();
        Facade::setFacadeApplication($container);
        $container->instance('db', $capsule->getDatabaseManager());
        $container->bind('db.schema', static function () use ($capsule) {
            return $capsule->getDatabaseManager()->connection()->getSchemaBuilder();
        });

        /** @var object{up(): void} $migration */
        $migration =
            require __DIR__.'/../../database/migrations/2026_01_01_000000_create_commerce_lifecycle_tables.php';
        $migration->up();
    }
}
