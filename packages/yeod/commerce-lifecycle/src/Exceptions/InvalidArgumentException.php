<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Exceptions;

/**
 * Thrown when a domain object receives structurally invalid input.
 *
 * Extends the package exception hierarchy so a consuming application can catch
 * this single type instead of a bare SPL {@see \InvalidArgumentException}.
 */
final class InvalidArgumentException extends CommerceLifecycleException {}
