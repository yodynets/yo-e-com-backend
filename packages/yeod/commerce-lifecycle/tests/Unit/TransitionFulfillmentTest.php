<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Application\AllowAllAuthorizer;
use Yeod\CommerceLifecycle\Application\DenyAllAuthorizer;
use Yeod\CommerceLifecycle\Application\Fulfillment\TransitionFulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatusChanged;
use Yeod\CommerceLifecycle\Exceptions\NotAuthorizedException;
use Yeod\CommerceLifecycle\Tests\Doubles\FakeEventDispatcher;
use Yeod\CommerceLifecycle\Tests\Doubles\FakeFulfillmentRepository;

/**
 * Verifies that the transition use case is guarded through the authorizer port,
 * mirroring the same fail-closed posture as ArchiveService.
 */
final class TransitionFulfillmentTest extends TestCase
{
    public function test_denied_transition_throws_not_authorized(): void
    {
        $fulfillment = Fulfillment::create(
            'ful-1',
            'ord-1',
            [new FulfillmentLine('line-1', 'sku-1', 1)],
        );
        $repository = new FakeFulfillmentRepository($fulfillment);
        $useCase = new TransitionFulfillment(
            $repository,
            new FakeEventDispatcher,
            new DenyAllAuthorizer,
        );

        $this->expectException(NotAuthorizedException::class);

        $useCase->execute('ful-1', FulfillmentStatus::OnHold);
    }

    public function test_denied_transition_does_not_mutate_aggregate(): void
    {
        $fulfillment = Fulfillment::create(
            'ful-1',
            'ord-1',
            [new FulfillmentLine('line-1', 'sku-1', 1)],
        );
        $repository = new FakeFulfillmentRepository($fulfillment);
        $useCase = new TransitionFulfillment(
            $repository,
            new FakeEventDispatcher,
            new DenyAllAuthorizer,
        );

        try {
            $useCase->execute('ful-1', FulfillmentStatus::OnHold);
            self::fail('Expected NotAuthorizedException was not thrown.');
        } catch (NotAuthorizedException) {
            // expected
        }

        self::assertSame(FulfillmentStatus::Scheduled, $fulfillment->status());
        self::assertSame([], $fulfillment->releaseEvents());
    }

    public function test_granted_transition_persists_and_dispatches_event(): void
    {
        $fulfillment = Fulfillment::create(
            'ful-1',
            'ord-1',
            [new FulfillmentLine('line-1', 'sku-1', 1)],
        );
        $repository = new FakeFulfillmentRepository($fulfillment);
        $events = new FakeEventDispatcher;
        $useCase = new TransitionFulfillment($repository, $events, new AllowAllAuthorizer);

        $useCase->execute('ful-1', FulfillmentStatus::OnHold);

        self::assertSame(FulfillmentStatus::OnHold, $fulfillment->status());
        self::assertCount(1, $events->dispatched);
        self::assertSame(FulfillmentStatusChanged::class, $events->dispatched[0]::class);
    }
}
