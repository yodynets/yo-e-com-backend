<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonException;
use Yeod\Modules\Catalog\Domain\ValueObject\TranslatableText;

/**
 * Bridges the `name` / `slug` / `description` jsonb columns to the Domain VO.
 * Illuminate coupling is intentionally confined to this Infrastructure class.
 */
final class TranslatableTextCast implements CastsAttributes
{
    /**
     * @throws JsonException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?TranslatableText
    {
        if ($value === null) {
            return null;
        }

        $decoded = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value;

        return TranslatableText::fromArray($decoded ?? []);
    }

    /**
     * @throws JsonException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof TranslatableText) {
            $value = $value->toArray();
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
