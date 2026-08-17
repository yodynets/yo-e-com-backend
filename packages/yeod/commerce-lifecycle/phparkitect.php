<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    // Onion: Domain is the innermost layer. It depends only on Domain, shared
    // Contracts, and Exceptions — never on Application, Infrastructure, or the
    // Laravel framework (Illuminate).
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Yeod\CommerceLifecycle\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'Yeod\CommerceLifecycle\Application',
            'Yeod\CommerceLifecycle\Infrastructure',
            'Illuminate',
        ]))
        ->because('Onion: the Domain layer stays framework-free and independent of the outer layers.');

    // Application: allowed to see Domain, Contracts, and Exceptions, but must
    // never leak into Infrastructure or the Laravel framework.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Yeod\CommerceLifecycle\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Yeod\CommerceLifecycle\Infrastructure',
            'Illuminate',
        ]))
        ->because('Onion: the Application layer must not depend on Infrastructure or the framework.');

    $config->add($classSet, ...$rules);
};
