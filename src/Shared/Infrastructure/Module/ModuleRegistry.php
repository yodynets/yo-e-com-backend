<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Module;

/**
 * Inventory of the modules booted in the current application.
 *
 * Filament panels ask this registry for their resources, pages and widgets, so a
 * panel provider never has to be edited when a module is added or removed.
 */
final class ModuleRegistry
{
    /**
     * @var array<string, array{resources: list<class-string>, pages: list<class-string>, widgets: list<class-string>}>
     */
    private array $modules = [];

    /**
     * Record a module and its Filament contributions.
     *
     * @param  string  $name  Module key, for example `catalog`.
     * @param  list<class-string>  $resources  Filament resource classes.
     * @param  list<class-string>  $pages  Filament page classes.
     * @param  list<class-string>  $widgets  Filament widget classes.
     */
    public function add(string $name, array $resources = [], array $pages = [], array $widgets = []): void
    {
        $this->modules[$name] = [
            'resources' => $resources,
            'pages' => $pages,
            'widgets' => $widgets,
        ];
    }

    /**
     * Names of every registered module.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Filament resources contributed by all modules.
     *
     * @return list<class-string>
     */
    public function resources(): array
    {
        return $this->flatten('resources');
    }

    /**
     * Filament pages contributed by all modules.
     *
     * @return list<class-string>
     */
    public function pages(): array
    {
        return $this->flatten('pages');
    }

    /**
     * Filament widgets contributed by all modules.
     *
     * @return list<class-string>
     */
    public function widgets(): array
    {
        return $this->flatten('widgets');
    }

    /**
     * Collect one contribution type across every module.
     *
     * @param  'resources'|'pages'|'widgets'  $key  Contribution type.
     * @return list<class-string>
     */
    private function flatten(string $key): array
    {
        $classes = [];

        foreach ($this->modules as $module) {
            foreach ($module[$key] as $class) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
