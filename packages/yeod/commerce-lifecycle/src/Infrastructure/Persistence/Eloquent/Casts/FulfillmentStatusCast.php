<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\InvalidArgumentException;

/**
 * Casts the persisted status string to the domain enum, raising a package
 * exception (instead of a bare {@see \ValueError}) when a stored value no
 * longer matches any enum case.
 *
 * @implements CastsAttributes<FulfillmentStatus, mixed>
 */
final class FulfillmentStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): FulfillmentStatus
    {
        $stringValue = is_scalar($value) ? (string) $value : '';
        $status = FulfillmentStatus::tryFrom($stringValue);

        if ($status === null) {
            throw new InvalidArgumentException(sprintf(
                'Unknown fulfillment status "%s" stored in the database.',
                $stringValue,
            ));
        }

        return $status;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof FulfillmentStatus) {
            return $value->value;
        }

        if (is_string($value)) {
            $status = FulfillmentStatus::tryFrom($value);
            if ($status !== null) {
                return $status->value;
            }
        }

        throw new InvalidArgumentException(
            'Fulfillment status must be a FulfillmentStatus value or a valid status string.'
        );
    }
}
