<?php

declare(strict_types = 1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

/**
 * PHPArkitect rules. They duplicate the most critical Deptrac rules on purpose:
 * Deptrac guards the layer graph, PHPArkitect guards naming and per-class shape.
 *
 * Run: vendor/bin/phparkitect check --config=phparkitect.php
 */
return static function (Config $config): void {
    $src = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    // 1. The Domain layer is framework agnostic pure PHP.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Yeod\Shared\Domain', 'Yeod\Modules\*\Domain'))
        ->should(
            new NotDependsOnTheseNamespaces(
                'Illuminate',
                'Laravel',
                'Filament',
                'Symfony',
                'Spatie',
            )
        )
        ->because('the Domain layer is the heart of the Onion and must not know about any framework');

    // 2. The Application layer may not reach into Infrastructure or Presentation.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Yeod\Shared\Application', 'Yeod\Modules\*\Application'))
        ->should(
            new NotDependsOnTheseNamespaces(
                'Yeod\Modules\*\Infrastructure',
                'Yeod\Modules\*\Presentation',
                'Yeod\Shared\Infrastructure',
                'Filament',
            )
        )
        ->because('use cases depend on ports, never on adapters');

    // 3. A module's public API must be free of implementation details.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Yeod\Modules\*\Contracts'))
        ->should(
            new NotDependsOnTheseNamespaces(
                'Yeod\Modules\*\Domain',
                'Yeod\Modules\*\Application',
                'Yeod\Modules\*\Infrastructure',
                'Yeod\Modules\*\Presentation',
                'Illuminate',
                'Filament',
            )
        )
        ->because('Contracts is the stable seam between modules');

    // 4. Eloquent models are an Infrastructure detail only.
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Models'))
        ->should(new ResideInOneOfTheseNamespaces('Yeod\Modules\*\Infrastructure\Persistence\Eloquent\Model'))
        ->because('persistence models must not leak out of Infrastructure');

    // 5. Naming conventions for the CQRS building blocks.
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Command'))
        ->should(new ResideInOneOfTheseNamespaces('Yeod\Modules\*\Application\Command', 'Yeod\Shared\Application\Bus'))
        ->because('commands belong to the Application layer');

    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Handler'))
        ->should(new IsFinal())
        ->because('handlers are use case entry points and must not be extended');

    // 6. Value objects stay self-contained.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Yeod\Shared\Domain\ValueObject'))
        ->should(new NotHaveDependencyOutsideNamespace('Yeod\Shared\Domain'))
        ->because('shared value objects must be reusable in isolation');

    $config->add($src, ...$rules);
};
