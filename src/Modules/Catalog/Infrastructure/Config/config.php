<?php

declare(strict_types=1);

/**
 * Catalog module configuration, merged under the `catalog` key.
 *
 * Access it with `config('catalog.default_currency')`. Module config never lives in
 * the application's `config/` directory, otherwise the module stops being portable.
 */
return [
    /*
    |---------------------------------------------------------------------------
    | Default currency
    |---------------------------------------------------------------------------
    |
    | Currency pre-selected in the admin panel and assumed by importers when the
    | source data carries no currency of its own.
    |
    */
    'default_currency' => env('CATALOG_DEFAULT_CURRENCY', 'UAH'),

    /*
    |---------------------------------------------------------------------------
    | Filament navigation
    |---------------------------------------------------------------------------
    */
    'navigation' => [
        'group' => 'Catalog',
        'sort' => 10,
    ],
];
