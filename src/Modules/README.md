# Modules

Every module is a package without its own `composer.json`. PSR-4 (`"Yeod\\": "src/"`)
resolves `Yeod\Modules\Catalog\Domain\Entity\Product` to
`src/Modules/Catalog/Domain/Entity/Product.php`, so no extra autoload entry is ever
needed when a module is added.

## Anatomy

```
Modules/<Name>/
├── Domain/          # Entities, VOs, Domain Events, repository interfaces. Pure PHP.
├── Application/     # UseCases: Command/Query + Handlers, DTOs, read-model ports.
├── Infrastructure/  # Eloquent models, repository implementations, migrations, config.
├── Presentation/    # Filament resources, HTTP controllers, routes, console, lang.
├── Contracts/       # Public API for other modules. Primitives only.
└── <Name>ServiceProvider.php
```

## Dependency rules (enforced by Deptrac and PHPArkitect in CI)

| Layer          | May depend on                                  |
|----------------|------------------------------------------------|
| Domain         | nothing (not even Laravel)                     |
| Application    | Domain, other modules' `Contracts`             |
| Infrastructure | Domain, Application, Contracts, Laravel        |
| Presentation   | Application, Contracts, Laravel, Filament      |
| Contracts      | nothing                                        |

Hard rules:

- No `use Illuminate\...` inside `Domain/`.
- No Eloquent relation pointing at another module's table (`belongsTo(OrderModel::class)` is banned).
- Cross module traffic goes through `Contracts/` interfaces or Domain Events. Nothing else.
- Filament and controllers dispatch commands/queries. They never call Eloquent to write.

## How to create a new module (target: under 15 minutes)

1. Copy the template:

   ```bash
   cp -r src/Modules/ModuleTemplate src/Modules/Orders
   ```

2. Rename the provider file and class:
   `OrdersServiceProvider` in `src/Modules/Orders/OrdersServiceProvider.php`,
   namespace `Yeod\Modules\Orders`, and return `'orders'` from `name()`.

3. Register it with one line in `bootstrap/providers.php`
   (keep it above `AdminPanelProvider`):

   ```php
   Yeod\Modules\Orders\OrdersServiceProvider::class,
   ```

4. Write the Domain first: entity, value objects, events, repository interface.
5. Add the use cases in `Application/` and map them in `commandHandlers()` / `queryHandlers()`.
6. Implement the ports in `Infrastructure/` and map them in `bindings()`.
7. Put migrations in `Infrastructure/Database/Migrations` (prefix tables with the
   module name, e.g. `orders_orders`). `php artisan migrate` picks them up.
8. Add Presentation: `Presentation/Routes/api.php`, Filament resources listed in
   `filamentResources()`.
9. Run `composer ci`. Green pipeline means the boundaries hold.

## Convention checklist

- `declare(strict_types=1);` in every file.
- Aggregate ids are typed value objects extending `Yeod\Shared\Domain\ValueObject\Uuid`.
- Money is always `Money` (integer minor units), never a float column.
- Time comes from the injected `Clock`, never from `now()`.
- Events are named in the past tense: `OrderWasPlaced`, event name `orders.order.placed`.
- Commands are imperative: `PlaceOrderCommand`. Queries describe the result: `GetOrderQuery`.
- Handlers are `final readonly` and end in `Handler`.
