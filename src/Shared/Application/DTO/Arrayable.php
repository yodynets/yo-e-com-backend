<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\DTO;

/**
 * Contract for Application DTOs that cross a transport boundary.
 *
 * DTOs are immutable, contain primitives only and are what the Presentation layer
 * receives. They are the anti corruption layer around the Domain model.
 */
interface Arrayable
{
    /**
     * Primitive representation of the DTO.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
