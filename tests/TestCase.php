<?php

declare(strict_types=1);

namespace Yeod\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base class for tests that need a booted application (Feature suite).
 *
 * Unit tests must NOT extend this class: pure domain tests run without the
 * framework, which is what keeps the suite fast.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
