<?php

declare(strict_types=1);

namespace Yeod\Tests\Feature\Modules\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Yeod\Tests\TestCase;

/**
 * Verifies that the module's own route file is loaded by its service provider.
 */
#[CoversNothing]
final class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exposes_the_module_routes(): void
    {
        $created = $this->postJson('/api/catalog/products', [
            'sku' => 'api-001',
            'name' => 'Mug',
            'price' => '149.90',
            'currency' => 'UAH',
        ]);

        $created->assertCreated();
        $productId = $created->json('data.id');

        $this->getJson('/api/catalog/products/'.$productId)
            ->assertOk()
            ->assertJsonPath('data.sku', 'API-001')
            ->assertJsonPath('data.price', '149.90');

        $this->getJson('/api/catalog/products?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_validates_the_payload(): void
    {
        $this->postJson('/api/catalog/products', ['sku' => '', 'name' => '', 'price' => -5, 'currency' => 'XYZ'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'name', 'price', 'currency']);
    }

    #[Test]
    public function it_returns_404_for_an_unknown_product(): void
    {
        $this->getJson('/api/catalog/products/018f0000-0000-4000-8000-000000000000')
            ->assertNotFound();
    }
}
