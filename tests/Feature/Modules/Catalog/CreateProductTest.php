<?php

declare(strict_types=1);

namespace Yeod\Tests\Feature\Modules\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Yeod\Modules\Catalog\Application\Command\ChangeProductPrice\ChangeProductPriceCommand;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Application\Query\GetProduct\GetProductQuery;
use Yeod\Modules\Catalog\Contracts\CatalogModule;
use Yeod\Modules\Catalog\Domain\Event\ProductPriceWasChanged;
use Yeod\Modules\Catalog\Domain\Event\ProductWasCreated;
use Yeod\Modules\Catalog\Domain\Exception\SkuAlreadyExists;
use Yeod\Shared\Application\Bus\CommandBus;
use Yeod\Shared\Application\Bus\QueryBus;
use Yeod\Tests\TestCase;

/**
 * End to end check of the module wiring: provider, buses, repository, events.
 */
#[CoversNothing]
final class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_product_through_the_command_bus(): void
    {
        Event::fake([ProductWasCreated::class]);

        $productId = $this->app->make(CommandBus::class)->dispatch(new CreateProductCommand(
            sku: 'tsh-001',
            name: 'T-shirt',
            price: '499.00',
            currency: 'UAH',
        ));

        self::assertIsString($productId);
        $this->assertDatabaseHas('catalog_products', [
            'id' => $productId,
            'sku' => 'TSH-001',
            'price_minor_amount' => 49900,
            'currency' => 'UAH',
        ]);

        Event::assertDispatched(ProductWasCreated::class);
    }

    #[Test]
    public function it_refuses_a_duplicate_sku(): void
    {
        $bus = $this->app->make(CommandBus::class);
        $command = new CreateProductCommand('tsh-001', 'T-shirt', '499.00', 'UAH');

        $bus->dispatch($command);

        $this->expectException(SkuAlreadyExists::class);

        $bus->dispatch($command);
    }

    #[Test]
    public function it_reads_the_product_back_through_the_query_bus(): void
    {
        $productId = $this->app->make(CommandBus::class)->dispatch(
            new CreateProductCommand('tsh-002', 'Hoodie', '1299.50', 'UAH'),
        );

        $product = $this->app->make(QueryBus::class)->ask(new GetProductQuery((string) $productId));

        self::assertInstanceOf(ProductDto::class, $product);
        self::assertSame('1299.50', $product->priceFormatted);
        self::assertTrue($product->active);
    }

    #[Test]
    public function it_changes_the_price_and_publishes_the_event(): void
    {
        Event::fake([ProductPriceWasChanged::class]);

        $bus = $this->app->make(CommandBus::class);
        $productId = (string) $bus->dispatch(new CreateProductCommand('tsh-003', 'Cap', '199.00', 'UAH'));

        $bus->dispatch(new ChangeProductPriceCommand($productId, '249.00', 'UAH'));

        $this->assertDatabaseHas('catalog_products', [
            'id' => $productId,
            'price_minor_amount' => 24900,
        ]);

        Event::assertDispatched(ProductPriceWasChanged::class);
    }

    #[Test]
    public function other_modules_reach_the_catalog_through_its_contract(): void
    {
        $productId = (string) $this->app->make(CommandBus::class)->dispatch(
            new CreateProductCommand('tsh-004', 'Socks', '99.00', 'UAH'),
        );

        $catalog = $this->app->make(CatalogModule::class);

        self::assertTrue($catalog->isSellable($productId));
        self::assertSame('TSH-004', $catalog->findProduct($productId)?->sku);
        self::assertNull($catalog->findProduct('018f0000-0000-4000-8000-000000000000'));
    }
}
