I'll unpack that archive and audit it properly.
Розпакував, прочитав усі 49 файлів. Коротко: **шарування у тебе чисте** (це реально рідкість), але persistence + archive + пакування сирі. Публікувати як `1.0` не варто, як `0.x` після фіксів блокерів: так.

Перевірив автоматично: `src/Domain` не імпортує нічого, крім `DateTimeImmutable` та `InvalidArgumentException`; `src/Application` не має жодного `Illuminate`/`Infrastructure`; жодного `config()`/`app()`/`DB::`/`Carbon` поза `src/Infrastructure`. Онін тримається. Проблеми в іншому.

---

# 🔴 БЛОКЕРИ

### B1. `commerce_fulfillment_lines.id` — глобальний PK замість унікальності в межах агрегату
`database/migrations/2026_01_01_000000_create_commerce_lifecycle_tables.php:22`

```php
$table->string('id')->primary();   // ← глобальний PK
```

А домен (`src/Domain/Fulfillment/Fulfillment.php:43-46`) гарантує унікальність **лише в межах агрегату**. Твій же `docs/database.md:211` це чорним по білому фіксує: «line ids unique within an aggregate → Fulfillment constructor (domain)».

Наслідок: дві різні fulfillment з лінією `line-1` (найочевидніший кейс: `line-1`, `line-2` на кожне замовлення) → `UNIQUE constraint violation` на другому замовленні. Тести це не ловлять тільки тому, що `EloquentFulfillmentRepositoryTest` використовує `l1`/`l2`, а `FulfillmentTest` не торкається БД.

Фікс у міграції:
```php
Schema::create('commerce_fulfillment_lines', static function (Blueprint $table): void {
    $table->string('id');
    $table->string('fulfillment_id');
    $table->string('sku')->index();
    $table->unsignedInteger('ordered_quantity');
    $table->unsignedInteger('fulfilled_quantity')->default(0);

    $table->primary(['fulfillment_id', 'id']);   // ← складений PK
    $table->foreign('fulfillment_id')->references('id')->on('commerce_fulfillments')->cascadeOnDelete();
});
```

І в `src/Infrastructure/Persistence/Eloquent/FulfillmentLineModel.php:22-24` додай, бо складений ключ Eloquent не тягне:
```php
public    $incrementing = false;
public    $timestamps   = false;
protected $keyType      = 'string';
protected $primaryKey   = null;   // Eloquent не вміє складені ключі
```
Це безпечно тільки тому, що `replaceLines()` працює через `where('fulfillment_id')->delete()` + `create()`, а не через `save()` по ключу.

---

### B2. Archive: авторизація стоїть на одному методі з чотирьох
`src/Application/Archive/ArchiveService.php:49-51` перевіряє права. А `restore()` (рядок 90), `findSnapshot()` (100), `isArchived()` (108) — **не перевіряють нічого**.

`findSnapshot()` віддає повний JSON-снапшот записа (тобто в реальному житті: адреси, телефони, суми) будь-кому, хто дістав сервіс із контейнера. `restore()` дає скасувати архівацію без прав. Плюс дефолтний `AllowAllAuthorizer` (`config/commerce-lifecycle.php:11`) робить пакет **fail-open з коробки**: хто не прочитав README, у того авторизації немає взагалі.

Фікс — витягни guard в приватний метод і додай у всі чотири:
```php
private function authorize(string $action, string $type): void
{
    if ($this->authorizer !== null && ! $this->authorizer->can($action, $type)) {
        throw new NotAuthorizedException(
            sprintf('Not authorized to %s %s records.', $action, $type)
        );
    }
}

public function restore(string $type, string $id): void
{
    $this->authorize('restore', $type);
    $this->repository->restore($type, $id);
}

public function findSnapshot(string $type, string $id): ?array
{
    $this->authorize('view', $type);
    return $this->repository->findSnapshot($type, $id);
}

public function isArchived(string $type, string $id): bool
{
    $this->authorize('view', $type);
    return $this->repository->isArchived($type, $id);
}
```

Про fail-open дефолт: `?Authorizer $authorizer = null` в конструкторі (рядок 30) я б **лишив** (домен не мусить знати про політики), але дефолт у config змінив би на `DenyAllAuthorizer::class`, а `AllowAllAuthorizer` лишив як явний opt-in для локальної розробки. Мінімум — блок `## Security` у README з великою плашкою.

---

### B3. ServiceProvider інстанціює довільний клас з env без перевірки типу
`src/Infrastructure/Laravel/CommerceLifecycleServiceProvider.php:31-35`

```php
$this->app->singleton(Authorizer::class, static function (): Authorizer {
    $concrete = config('commerce-lifecycle.authorizer', AllowAllAuthorizer::class);
    return app($concrete);   // ← mixed → object, нуль валідації
});
```

`config()` повертає `mixed`, `app($concrete)` — будь-що. Якщо `YEOD_COMMERCE_LIFECYCLE_AUTHORIZER` вказує на неіснуючий або невідповідний клас, ти отримаєш `TypeError`/`BindingResolutionException` десь у глибині стеку під час першої архівації в проді, а не на бутстрапі. PHPStan level 8 має на це кричати (див. B5 — не кричить, бо larastan не підключений).

Фікс:
```php
$this->app->singleton(Authorizer::class, static function ($app): Authorizer {
    $concrete = $app['config']->get('commerce-lifecycle.authorizer', AllowAllAuthorizer::class);

    if (! is_string($concrete) || ! is_a($concrete, Authorizer::class, true)) {
        throw new InvalidArgumentException(sprintf(
            'commerce-lifecycle.authorizer must be a class-string implementing %s, got %s.',
            Authorizer::class,
            get_debug_type($concrete),
        ));
    }

    return $app->make($concrete);
});
```
Заодно прибери глобальні хелпери `config()`/`app()` з `register()` (рядки 32, 34, 39-42) на `$app` — інакше провайдер неможливо тестувати без повного фреймворку, що суперечить усій заяві пакета.

---

### B4. `bumpVersion()` всередині транзакції до останнього запису
`src/Infrastructure/Persistence/Eloquent/EloquentFulfillmentRepository.php:75-83`

```php
if ($updated === 0) {
    throw new StaleAggregateException(...);
}

$fulfillment->bumpVersion();                                   // ← мутація in-memory
$this->replaceLines($fulfillment->id(), $fulfillment->lines()); // ← може кинути
```

Якщо `replaceLines()` впаде (FK, deadlock, будь-що) — БД відкатиться, а `$fulfillment->version()` **уже +1**. Далі агрегат назавжди розсинхронізований: кожен наступний `save()` кидатиме `StaleAggregateException`, хоча ніякої конкурентності немає. Найгірший тип баги: проявляється рідко, діагностується жахливо.

Плюс TOCTOU на рядках 60-64: `exists()` → `insert()`. Дві паралельні транзакції обидві бачать «немає» → друга падає з duplicate key, а не з чимось осмисленим.

Фікс:
```php
public function save(Fulfillment $fulfillment): void
{
    $persistedVersion = DB::transaction(function () use ($fulfillment): int {
        $updated = FulfillmentModel::query()
            ->whereKey($fulfillment->id())
            ->where('version', $fulfillment->version())
            ->update([
                'status'   => $fulfillment->status()->value,
                'metadata' => $fulfillment->metadata(),
                'version'  => $fulfillment->version() + 1,
            ]);

        if ($updated === 1) {
            $this->replaceLines($fulfillment->id(), $fulfillment->lines());
            return $fulfillment->version() + 1;
        }

        // 0 рядків: або записа немає, або версія стара. Розрізняємо явно.
        if (FulfillmentModel::query()->whereKey($fulfillment->id())->lockForUpdate()->exists()) {
            throw new StaleAggregateException(
                sprintf('Fulfillment "%s" was modified concurrently.', $fulfillment->id())
            );
        }

        $this->insert($fulfillment);
        return $fulfillment->version();
    });

    // мутуємо агрегат ТІЛЬКИ після успішного коміту
    while ($fulfillment->version() < $persistedVersion) {
        $fulfillment->bumpVersion();
    }
}
```
Ще: `replaceLines()` (105-118) робить `delete` + N × `create` на кожен save. На 50 лініях це 51 запит і повний churn PK. Перепиши на `upsert()` + `whereNotIn()->delete()`.

---

### B5. Пакет не збереться і не проаналізується у власному репозиторії
Дві незалежні речі, обидві ламають публікацію.

**(а) PSR-4 порушено.** `composer.json:25-27` мапить `Yeod\CommerceLifecycle\Tests\` → `tests/`, а всі 6 тестових класів (`tests/Unit/*.php`) сидять у **глобальному** namespace. `composer dump-autoload --optimize --strict-psr` (те, що робить CI і Packagist-валідатори) видасть помилку, PHPStan класи не побачить. Плюс `FakeArchiveRepository` (`tests/Unit/ArchiveServiceTest.php:76`) у глобальному неймспейсі — готова колізія в чужому проєкті.

Фікс: у кожен файл `tests/Unit/*.php` після `declare(strict_types=1);`
```php
namespace Yeod\CommerceLifecycle\Tests\Unit;
```
і додати `use PHPUnit\Framework\TestCase;` там, де його ще нема (він уже є в усіх), а `FakeArchiveRepository` вивести у `tests/Doubles/FakeArchiveRepository.php`.

**(б) larastan не підключений.** `phpstan.neon`:
```neon
parameters:
    level: 8
    paths:
        - src
```
`larastan/larastan` є в `require-dev`, але без `includes` його extension не активний. Тобто level 8 бігає по Eloquent-коду **без** розуміння магії моделей: або сипле фальшивими помилками (`$model->order_id`, `$model->lines`), або дає фальшиве відчуття безпеки. Фікс:
```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 8
    paths:
        - src
        - tests
    treatPhpDocTypesAsCertain: false
    checkMissingIterableValueType: true
    tmpDir: var/cache/phpstan
```

---

# 🟠 ВИСОКИЙ ПРІОРИТЕТ

### H1 + H2. `fulfillLine()` ламає атомарність і має guard не в тому місці
`src/Domain/Fulfillment/Fulfillment.php:97-123`

```php
if ($this->status === FulfillmentStatus::Scheduled) {
    $this->changeStatus(FulfillmentStatus::Unfulfilled);   // ← мутація + подія в чергу
}
if ($this->status === FulfillmentStatus::OnHold) {
    throw new InvalidArgumentException('A fulfillment on hold cannot be fulfilled.');
}
$this->lines[$lineId]->fulfill($quantity);   // ← а це може кинути
```

Дві проблеми в семи рядках:
1. Якщо `fulfill()` кине (наприклад, `$quantity` перевищує залишок), агрегат уже перейшов `Scheduled → Unfulfilled` **і** в `$this->domainEvents` висить `FulfillmentStatusChanged`. Наступний `releaseEvents()` віддасть подію про перехід, якого бізнесово не було. Класичний partial-mutation баг.
2. `OnHold` перевіряється **після** переходу — читається як мертвий код (з `OnHold` перший `if` не спрацює), але порядок все одно неправильний і крихкий. І кидає SPL-`InvalidArgumentException` замість `InvalidTransitionException` — це ж рівно те, чим воно є.

Фікс — валідація вся вгору, мутації вниз:
```php
public function fulfillLine(string $lineId, int $quantity): void
{
    if ($this->status === FulfillmentStatus::OnHold) {
        throw InvalidTransitionException::from($this->status, FulfillmentStatus::PartiallyFulfilled);
    }
    if (! isset($this->lines[$lineId])) {
        throw new InvalidArgumentException(sprintf('Unknown fulfillment line "%s".', $lineId));
    }

    $line = $this->lines[$lineId];
    $line->fulfill($quantity);   // кине ДО будь-якої зміни статусу

    if ($this->status === FulfillmentStatus::Scheduled) {
        $this->changeStatus(FulfillmentStatus::Unfulfilled);
    }

    $hasFulfilled = false;
    $allFulfilled = true;
    foreach ($this->lines as $l) {
        $hasFulfilled = $hasFulfilled || $l->fulfilledQuantity() > 0;
        $allFulfilled = $allFulfilled && $l->isFullyFulfilled();
    }

    $target = match (true) {
        $allFulfilled => FulfillmentStatus::Fulfilled,
        $hasFulfilled => FulfillmentStatus::PartiallyFulfilled,
        default       => FulfillmentStatus::Unfulfilled,
    };

    if ($this->status !== $target) {
        $this->changeStatus($target);
    }
}
```
І додай тест, якого немає: `fulfillLine` з завеликим `$quantity` не повинен змінювати ні статус, ні `releaseEvents()`.

---

### H3. Домен кидає SPL-винятки, тобто обіцянка `catch (CommerceLifecycleException)` не працює
`src/Exceptions/CommerceLifecycleException.php:9-11` каже: «Consuming applications may catch this single type to handle all package-level business exceptions uniformly.»

Це неправда. `Fulfillment.php:39, 44, 100, 106` і `FulfillmentLine.php:23, 26, 52` кидають голий `\InvalidArgumentException`. `TransitionFulfillment.php:36` — теж. Хост, що ловить `CommerceLifecycleException`, пропустить половину помилок пакета. Це порушення публічного контракту, тобто після `1.0` фіксити вже боляче.

Фікс — новий клас `src/Exceptions/InvalidArgumentException.php`:
```php
<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Exceptions;

/**
 * Thrown when a domain object receives structurally invalid input.
 */
final class InvalidArgumentException extends CommerceLifecycleException
{
}
```
Далі в `Fulfillment.php` та `FulfillmentLine.php` заміни `use InvalidArgumentException;` на `use Yeod\CommerceLifecycle\Exceptions\InvalidArgumentException;`. Тіла `throw new InvalidArgumentException(...)` не змінюються — тільки імпорт. Оскільки твій клас **не** наслідує SPL-версію, це BC-break, і саме тому це треба зробити **до** публікації, а не після.

---

### H4. Агрегат віддає мутабельні лінії назовні
`src/Domain/Fulfillment/Fulfillment.php:191`

```php
public function lines(): array { return array_values($this->lines); }
```

`FulfillmentLine::fulfill()` (`FulfillmentLine.php:49`) публічний. Тобто:
```php
$fulfillment->lines()[0]->fulfill(5);   // статус агрегату НЕ перерахувався
```
Інваріант «статус похідний від кількостей» (заявлений у docblock `Fulfillment.php:14-15` і в `docs/architecture.md:7`) тихо ламається зовні. Це і є те саме «просочування» — тільки не між шарами, а з-під агрегату.

Два варіанти, обидва нормальні:
```php
// A) віддавати незмінні знімки (простіше, ламає API lines()[0]->sku())
/** @return list<array{id: string, sku: string, ordered_quantity: int, fulfilled_quantity: int}> */
public function lines(): array
{
    return array_map(static fn(FulfillmentLine $l): array => $l->toArray(), array_values($this->lines));
}

// B) лишити типи, але закрити мутатор (мій вибір)
// FulfillmentLine.php:49 → зробити fulfill() @internal і викликати
// лише з Fulfillment; або зробити лінію справді immutable:
public function withFulfilled(int $quantity): self
{
    if ($quantity < 1 || $this->fulfilledQuantity + $quantity > $this->orderedQuantity) {
        throw new InvalidArgumentException('Fulfilled quantity exceeds the ordered quantity.');
    }
    return new self($this->id, $this->sku, $this->orderedQuantity, $this->fulfilledQuantity + $quantity);
}
```
З варіантом B `Fulfillment::fulfillLine()` робить `$this->lines[$lineId] = $line->withFulfilled($quantity);` і H1 закривається сам собою: помилка кидається до присвоєння.

Але зваж: репозиторій (`EloquentFulfillmentRepository.php:82`) споживає `$fulfillment->lines()` як `list<FulfillmentLine>`, а `Fulfillment::toArray():167` — теж. Варіант A вимагає правок в обох.

---

### H5. `reconstitute()` публічний → граф переходів обходиться в один рядок
`src/Domain/Fulfillment/Fulfillment.php:70-80`

```php
Fulfillment::reconstitute('ful-1', 'ord-1', FulfillmentStatus::Fulfilled, $lines);
```
Будь-хто ліпить агрегат у будь-якому статусі, повністю минаючи `canTransitionTo()`. Технічно це потрібно репозиторію, але зараз це просто публічний бекдор в інваріанти.

Мінімум — позначити явно (`Fulfillment.php:64-69`):
```php
/**
 * Reconstitute an aggregate from persistence without emitting events.
 *
 * @internal Persistence adapters only. Application code must use create().
 */
```
Правильніше — окремий namespaced-порт `Domain\Fulfillment\FulfillmentFactory` і перевірка узгодженості при відновленні: якщо `$status === Fulfilled`, а не всі лінії `isFullyFulfilled()` — кидати. Зараз пошкоджений рядок у БД відновлюється в неконсистентний агрегат молча.

---

### H6. `ProductAvailabilityStatus::isFinal()` суперечить власному графу
`src/Domain/Catalog/ProductAvailabilityStatus.php:38` і `:44-47`

```php
self::Discontinued => $target === self::Archived,   // перехід ДОЗВОЛЕНО
...
public function isFinal(): bool { return $this === self::Discontinued; }   // але "термінальний"
```

Docblock прямо каже «terminal and cannot transition further». Discontinued може перейти в Archived. Хост, що робить `if ($status->isFinal()) { /* нічого більше не робимо */ }`, зламає архівацію знятих з продажу товарів.

І що гірше — `tests/Unit/StatusGraphTest.php:168` та `:194` **закріплюють обидві суперечливі поведінки** одночасно. Тест зелений, логіка неузгоджена.

Фікс, узгоджений з рештою пакета (в інших enum `isFinal()` = «немає вихідних переходів»):
```php
public function isFinal(): bool
{
    return false;   // Archived → Draft і Discontinued → Archived: жоден стан не термінальний
}
```
Якщо ж семантика «знято з продажу назавжди» справді потрібна — назви її честнiше і не мішай з `isFinal()`:
```php
/** Determine whether the product left the assortment permanently. */
public function isRetired(): bool
{
    return in_array($this, [self::Discontinued, self::Archived], true);
}
```
Плюс додай узагальнюючий тест, який ловитиме такі розбіжності системно, для всіх шести enum:
```php
public function test_is_final_implies_no_outgoing_transitions(): void
{
    foreach ([OrderStatus::class, PaymentStatus::class, FulfillmentStatus::class,
              ShipmentStatus::class, ReturnStatus::class, ProductAvailabilityStatus::class] as $enum) {
        foreach ($enum::cases() as $from) {
            if (! $from->isFinal()) {
                continue;
            }
            foreach ($enum::cases() as $to) {
                self::assertFalse(
                    $from->canTransitionTo($to),
                    sprintf('%s::%s is final but allows -> %s', $enum, $from->name, $to->name),
                );
            }
        }
    }
}
```
Цей тест зараз падає рівно на `ProductAvailabilityStatus`. Ось для чого він і потрібен.

---

### H7. `empty()` на ідентифікаторах
`src/Application/Archive/ArchiveService.php:53, 57`

```php
if (empty($type) || strlen($type) > 255) { ... }
if (empty($id) || strlen($id) > 255) { ... }
```
`empty('0') === true`. Запис із id `"0"` (а це цілком легальний host-id, особливо з 1С/legacy, про які ти сам пишеш у `docs/positioning.md:31`) неможливо заархівувати. Параметри вже типізовані як `string`, тому:
```php
if ($type === '' || mb_strlen($type) > 255) { ... }
if ($id === '' || mb_strlen($id) > 255) { ... }
```
Те саме на рядку 67: `if (empty($snapshot))` → `if ($snapshot === [])`.

---

### H8. `findSnapshot()` не фільтрує відновлені записи
`src/Infrastructure/Persistence/Eloquent/EloquentArchiveRepository.php:57-63` vs `:65-72`

`isArchived()` має `->whereNull('restored_at')`, `findSnapshot()` — ні. При цьому контракт `src/Domain/Archive/ArchiveRepository.php:34` каже: «or null when it is not archived». Після `restore()` `isArchived()` віддає `false`, а `findSnapshot()` все ще віддає снапшот. Дві функції розходяться в тому, що означає «архівовано».

Вирішити треба **семантично**, і я б обрав перше:
```php
// Варіант 1: узгодити з контрактом
public function findSnapshot(string $type, string $id): ?array
{
    return ArchiveRecordModel::query()
        ->where('archivable_type', $type)
        ->where('archivable_id', $id)
        ->whereNull('restored_at')
        ->value('snapshot');
}
```
Варіант 2 — лишити код і переписати docblock в `ArchiveRepository.php:34` на «Return the stored snapshot regardless of restore state». Але тоді `ArchiveService::findSnapshot()` стає інструментом читання відновлених даних, і B2 (авторизація на read) стає ще критичнішим.

Дрібне поруч: у `ArchiveRepository.php:34` написано «deepest snapshot», у `:29` — «latest snapshot». Ні того, ні іншого немає: unique `(type, id)` + `updateOrCreate` дають рівно один рядок. Приведи формулювання до реальності.

---

### H9. «Immutable archive», який тихо перезаписує сам себе
`src/Domain/Archive/ArchiveRepository.php:8` обіцяє «immutable operational archive snapshots». `EloquentArchiveRepository.php:29` робить `updateOrCreate` по unique `(archivable_type, archivable_id)`. Друга архівація того ж записа **затирає** попередній снапшот без слідів. Для сховища, яке продається як audit + analytics, це втрата audit trail: ти не можеш відповісти «як цей запис виглядав під час першої архівації».

`docs/database.md:178-179` це усвідомлює («not a full history, history is …»), але тоді слово immutable у контракті домену треба забрати, бо воно тут просто неправда.

Або, як на мене, краще: зроби архів справді append-only.
```php
// міграція: замінити unique(...) на індекс + версію снапшота
$table->unsignedInteger('snapshot_version')->default(1);
$table->index(['archivable_type', 'archivable_id', 'archived_at']);
$table->unique(['archivable_type', 'archivable_id', 'snapshot_version']);

// EloquentArchiveRepository::archive()
ArchiveRecordModel::query()->create([
    'archivable_type'  => $type,
    'archivable_id'    => $id,
    'snapshot_version' => $this->nextVersion($type, $id),
    'reason'           => $reason,
    'archived_by'      => $archivedBy,
    'storage_location' => $storageLocation,
    'snapshot'         => $snapshot,
    'archived_at'      => Carbon::now(),
    'restored_at'      => null,
]);
```
Тоді `findSnapshot()` бере `->latest('snapshot_version')->value('snapshot')`, а `restore()` — тільки останній рядок. Якщо не хочеш такої зміни — просто прибери «immutable» з docblock і додай у `docs/database.md` явне попередження, що повторна архівація деструктивна.

---

# 🟡 СЕРЕДНІЙ ПРІОРИТЕТ

**M1. Асиметрія авторизації.** `ArchiveService` пускає через `Authorizer`, `src/Application/Fulfillment/TransitionFulfillment.php` — ні. Зміна статусу відвантаження бізнесово чутливіша за архівацію. `docs/architecture.md:38` навіть згадує «Add application policies for who may transition a status» як extension point — але це має бути в пакеті, бо use case уже тут. Додай `?Authorizer` в конструктор (рядки 21-24) і `$this->authorize('transition', 'fulfillment')` перед `changeStatus()`.

**M2. Події втрачаються.** `TransitionFulfillment.php:40-44`: save, потім dispatch. Якщо процес умре між ними, статус у БД змінився, а подія не пішла. At-most-once. Docblock (рядки 16-18) це подає як фічу, але для commerce-lifecycle це серйозно. Мінімум — секція `## Event delivery guarantees` у README з чесним «at-most-once, для at-least-once винесіть outbox». Краще — порт `Domain\Shared\EventStore` і запис подій у ту саму транзакцію.

**M3. `metadata` без ліміту.** Снапшот обмежений (`ArchiveService.php:77-82`), а `Fulfillment::$metadata` (`Fulfillment.php:34`) летить у JSON-колонку будь-якого розміру. На MySQL це тихий обрив на 64KB для `TEXT`-подібних типів. Або валідуй у конструкторі агрегату, або задокументуй, що це відповідальність хоста.

**M4. `strlen` там, де сказано «characters».** `ArchiveService.php:61, 78` — байти, а повідомлення на рядку 63 каже «characters». Коментар у `config/commerce-lifecycle.php:13` каже «bytes». Три різні твердження. Обери `mb_strlen` для `reason` (це людський текст) і лиши `strlen` для `$encoded` (там байти доречні), а повідомлення поправ.

**M5. Серіалізація в домені.** `Fulfillment::toArray()` (`Fulfillment.php:159-169`) віддає snake_case ключі `order_id`, `created_at`, `ordered_quantity`. Це форма персистенції/транспорту в чистому домені. Не смертельно (це найм'якше «просочування», яке я знайшов), але якщо хочеш ідеальний онін — виведи `Application\Fulfillment\FulfillmentSnapshot` і мапи там, а в домені лиши типізовані геттери.

**M6. `status` — вільний string у БД.** Міграція, рядок 15: `$table->string('status')->index();`. Будь-який ручний UPDATE або старий рядок після ренейму enum-кейса → `ValueError` з Eloquent-каста (`FulfillmentModel.php:53`) прямо в `find()` (`EloquentFulfillmentRepository.php:34`), без жодного package-винятку. Або додай CHECK-констрейнт, або оберни каст і кидай `CommerceLifecycleException` з осмисленим текстом.

**M7. Два дома для контрактів.** `src/Contracts/` (DomainEvent, DomainEventDispatcher) і `src/Domain/Shared/` (TransitionableStatus) + порти в `src/Domain/*/`. Три різні конвенції для однієї сутності. Обери одну (я б звів усе в `Domain/`, а `src/Contracts` прибрав) — після публікації це вже BC-break.

**M8. Немає БД-гарантії `fulfilled <= ordered`.** Домен це тримає (`FulfillmentLine.php:25, 51`), БД — ні. Для інваріанта, на якому побудований весь статус агрегату, я б додав CHECK.

**M9. `composer.json` не готовий до Packagist.**
- `"version": "0.0.0"` (рядок 5) — Packagist явно просить **не** вказувати version у бібліотеках, теги вирішують. Прибери.
- `"orchestra/testbench": "^11.0"` (рядок 16) — testbench мапиться як 9→L11, 10→L12, 11→L13. При `illuminate/* ^12.0|^13.0` матриця для L12 не зійдеться. Або `^10.0|^11.0`, або чесно тільки L13.
- `illuminate/* ^13.0` — Laravel 13 ще не вийшов. Ти публікуєш пакет під ненаписаний фреймворк. Або звужуй до `^12.0`, або тримай `^12.0|^13.0` і не пиши в README «Laravel 13 supported».
- Немає `authors`, `keywords`, `support.issues`, `config.sort-packages`, `config.allow-plugins` (larastan вимагає phpstan-extension-installer allow).
- Немає `.gitattributes` — у `composer require` користувачу поїдуть `tests/`, `docs/`, `.github/`. Додай:
```
/tests          export-ignore
/docs           export-ignore
/.github        export-ignore
/phpunit.xml.dist export-ignore
/phpstan.neon   export-ignore
/.gitignore     export-ignore
```

**M10. CI не тестує те, що обіцяє.** `.github/workflows/tests.yml`:
- Рядки 14-15: `composer require --dev` **до** `composer install`. `require` уже встановлює, тому `install` після нього просто зайвий крок (а якщо lock-файл не зайде — впаде).
- Рядок 14 констрейнить лише `illuminate/contracts`. `illuminate/database` та `illuminate/support` вирішуються вільно, тобто матриця `^12.0` / `^13.0` **фактично не розводить версії Laravel**. Обидві колонки ставлять одне й те саме.
- Немає `fail-fast: false`, `--prefer-lowest` прогону, кешу composer, і `continue-on-error` для L13-колонки (яка об'єктивно нестабільна).

Робочий варіант:
```yaml
    strategy:
      fail-fast: false
      matrix:
        php: [ '8.3', '8.4' ]
        laravel: [ '^12.0', '^13.0' ]
        deps: [ 'highest', 'lowest' ]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: "${{ matrix.php }}", extensions: pdo_sqlite, coverage: none }
      - run: |
          composer require --no-update \
            "illuminate/contracts:${{ matrix.laravel }}" \
            "illuminate/database:${{ matrix.laravel }}" \
            "illuminate/support:${{ matrix.laravel }}"
      - uses: ramsey/composer-install@v3
        with: { dependency-versions: "${{ matrix.deps }}" }
      - run: composer test
      - run: composer analyse
```

**M11. README суперечить composer.json.** Заголовок «Commerce Lifecycle for **Laravel 13**» (рядок 1) і «PHP 8.3+ and Laravel 13 are supported» (рядок 120) при `^12.0|^13.0`. Плюс рядки 122-132 інструктують запускати тести через host-app path-repository — це нотатки з твоєї локальної розробки, у публічному README вони виглядають як «пакет ще не витягнутий з монорепо». Прибери секцію або перенеси в `CONTRIBUTING.md`. Ще: рядок 7 «the useful distinction described by Locad» — посилання на джерело нема, або дай URL, або прибери ім'я.

**M12. Тести: покриття є, ізоляції немає.**
- `EloquentFulfillmentRepositoryTest.php:28, 120-127`: `static ?EloquentFulfillmentRepository`, Capsule як `setAsGlobal()`, `Facade::setFacadeApplication()` і **жодного `tearDownAfterClass`**. Глобальний стан фасадів протікає в решту сюїту; порядок тестів починає впливати на результат.
- Рядки 34-46: тест зветься round-trip, але залежить від того, що `setUp` уже почистив таблиці іншим тестом. Крихко.
- `StatusIsolationTest.php:34-54`: closures у data provider. Працює, але ламається під `--process-isolation` і не піддається `--filter` по імені кейса нормально. Ці шість кейсів чистіше зробити шістьма звичайними тестами.
- **Немає жодного тесту на те, що найлегше зламати:** `ArchiveService` валідація (порожній snapshot, перевищення `max_snapshot_size`, задовгий `reason`), `NotAuthorizedException` при відмові авторайзера, `TransitionFulfillment` з неіснуючим id, `FulfillmentLine` з `fulfilledQuantity > orderedQuantity`. Тобто вся Application-логіка перевірена тільки на happy path через `FakeArchiveRepository`.
- `phpunit.xml.dist`: немає `failOnWarning`, `failOnRisky`, `failOnDeprecation`, `<source>` для coverage. Для пакета, що продає «quality gates» (README:113), це слабко:
```xml
<phpunit ... bootstrap="vendor/autoload.php" colors="true"
         failOnWarning="true" failOnRisky="true" failOnDeprecation="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Commerce Lifecycle">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include><directory>src</directory></include>
    </source>
</phpunit>
```

**M13. Немає обов'язкової для публічного пакета обвʼязки:** `CHANGELOG.md`, `SECURITY.md`, `CONTRIBUTING.md`, `.editorconfig`, шаблонів issue. Для пакета, який пропонують ставити в комерційний проєкт, `SECURITY.md` — не формальність.

---

# ⚪️ ДРІБНИЦІ, ЯКІ ВИДНО ОДРАЗУ

**L1. Чужий `@package fila`** у двох файлах: `src/Contracts/DomainEventDispatcher.php:4` і `src/Infrastructure/Events/LaravelDomainEventDispatcher.php:4`. Залишок від іншого проєкту. Перше, що помітить рецензент.

**L2. Зайвий self-namespace import.** `src/Domain/Fulfillment/Fulfillment.php:10` імпортує `FulfillmentLine` з того ж неймспейсу. Видали рядок.

**L3. Коментарі-виправдання в `EloquentArchiveRepository.php`.** Рядки 13-14 («Security: All database parameters are bound…»), 47 («All user inputs are bound as parameters, not concatenated»), 49-50 («Explicit operator for clarity» ×2). Це нормальна поведінка Eloquent, яку ніхто не ставив під сумнів. Такі коментарі читаються як «тут щось було не так і це пофіксили», тобто привертають увагу рівно туди, куди не треба. Прибери всі чотири.

**L4. `LICENSE`: «Copyright (c) 2026 Yeod yt».** «Yeod yt» — схоже на випадковий нік. Постав реальне ім'я або назву організації.

**L5. `composer.json` description обіцяє більше, ніж є.** «domain module for order, payment, fulfillment, shipment, returns, catalog availability and archival lifecycles» — але агрегат, репозиторій, персистенція і use case є **тільки** у Fulfillment. Order/Payment/Shipment/Return/Catalog — це шість enum і нічого більше. `docs/positioning.md` це чесно пояснює, а `composer.json` і Packagist-сніпет — ні. Перепиши на щось типу: «Status transition graphs for commerce lifecycles plus a guarded Fulfillment aggregate and deep-archive mechanism for Laravel.»

**L6. `phpstan.neon` не аналізує `tests`** (див. B5б). Плюс варто додати `parallel` і baseline, щоб level 8 не блокував ітерації.

---

# Що я думаю насправді

Сильні сторони, і вони справжні: **ізоляція осей статусів через `canTransitionTo(self)` — це елегантно і реально працює**, компілятор ловить змішування контекстів, і `StatusIsolationTest` це чесно доводить. Шарування витримане без єдиного порушення напрямку залежностей, що я перевірив грепом, а не на віру. Документація на 1133 рядки для 49 файлів — краще, ніж у 90% пакетів на Packagist. Оптимістичне блокування взагалі закладене, і це зріле рішення.

Слабке місце одне і воно системне: **домен вилизаний, а все, що торкається БД і публікації, писалося поспіхом**. B1 (PK ліній), B4 (bumpVersion у транзакції) і B5 (PSR-4 + larastan) — це не стилістика, це «поставив у прод і воно зламалося на другому замовленні». B2 (fail-open авторизація + три незахищені методи) — те, за що пакет рознесуть у першому ж security-review.

Мій план: закрий **B1-B5**, тоді **H1-H9**, публікуй як **`0.9.0`** з чесним «API may change before 1.0». Це десь день роботи. M-блок тягни в `0.10`, `1.0` став тільки після M1, M2, M7, M9, бо це все BC-breaks, які після `1.0` доведеться тягнути роками.

І одразу: **звузь до `^12.0`**. Публікувати пакет під Laravel 13, якого ще немає, і писати в README «Laravel 13 supported» — це обіцянка, яку ти не можеш перевірити навіть у CI.

Скажи, якщо хочеш, щоб я оформив це задачами в ClickUp: розкладу по блокерах/high/medium окремими тасками з цими фрагментами в описах.