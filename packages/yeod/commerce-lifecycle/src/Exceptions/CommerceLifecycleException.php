<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Exceptions;

/**
 * Base exception for every domain (business rule) failure raised by the package.
 *
 * Consuming applications may catch this single type to handle all package-level
 * business exceptions uniformly.
 */
abstract class CommerceLifecycleException extends \Exception
{
}