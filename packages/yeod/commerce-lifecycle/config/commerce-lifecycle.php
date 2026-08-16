<?php

declare(strict_types = 1);

use Yeod\CommerceLifecycle\Application\AllowAllAuthorizer;

/**
 * Commerce lifecycle package configuration.
 */
return [
    'authorizer'        => (string) env('YEOD_COMMERCE_LIFECYCLE_AUTHORIZER', AllowAllAuthorizer::class),
    'max_snapshot_size' => (int) env('YEOD_COMMERCE_LIFECYCLE_MAX_SNAPSHOT_SIZE', 512),    // kilobytes
    'max_reason_length' => (int) env('YEOD_COMMERCE_LIFECYCLE_MAX_REASON_LENGTH', 1000),   // bytes
];