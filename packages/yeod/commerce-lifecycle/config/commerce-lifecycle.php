<?php

declare(strict_types=1);

use Yeod\CommerceLifecycle\Application\DenyAllAuthorizer;

/**
 * Commerce lifecycle package configuration.
 */
return [
    'authorizer' => (string) env('YEOD_COMMERCE_LIFECYCLE_AUTHORIZER', DenyAllAuthorizer::class),
    'max_snapshot_size' => (int) env('YEOD_COMMERCE_LIFECYCLE_MAX_SNAPSHOT_SIZE', 512),    // kilobytes
    'max_reason_length' => (int) env('YEOD_COMMERCE_LIFECYCLE_MAX_REASON_LENGTH', 1000),   // characters
    'max_metadata_size' => (int) env('YEOD_COMMERCE_LIFECYCLE_MAX_METADATA_SIZE', 65535),  // bytes
];
