@src/src.md

# Модульний моноліт з DDD/Onion для e-commerce з перспективою перевикористання модулів

## Рекомендована структура: `src/Modules`

```
src/
├── Shared/                     # Shared Kernel — мінімальний!
│   ├── Domain/                 # базові VO (Money, Email), інтерфейси подій
│   ├── Application/            # Bus-контракти, базові DTO
│   └── Infrastructure/
└── Modules/
    ├── Catalog/
    ├── Orders/
    ├── Customers/
    ├── Payments/
    ├── Inventory/
    └── LegacyMigration/
```

Кожен модуль — як "пакет без власного composer.json":

```
Modules/Catalog/
├── Domain/            # Entities, ValueObjects, Events, Repository-інтерфейси — БЕЗ Laravel
├── Application/       # UseCases (Commands/Queries + Handlers), DTOs
├── Infrastructure/    # Eloquent-моделі, реалізації репозиторіїв, міграції
├── Presentation/      # Filament Resources, HTTP, Console
├── Contracts/         # публічний API модуля для інших модулів
└── CatalogServiceProvider.php
```

# OS: Windows Server 2019 x64

# PHP: 8.5.8

# Laravel: 13 / Filament (SpatieTranslatablePlugin)

# Composer: `"Yeod\\": "src/Modules/"` →

`Yeod\Catalog\Domain\...` — PSR-4 працює без окремого composer для кожного модуля.

## Ключові правила для перевикористовності

- **Один Service Provider на модуль** — реєструє біндінги, міграції, роути, Filament-ресурси. Перенесення модуля =
  скопіювати папку + зареєструвати провайдер.
- **Domain-шар без залежностей від Laravel** — тільки чистий PHP. Це серце Onion.
- **Міжмодульна комунікація тільки через `Contracts/` + Domain Events** — ніяких прямих Eloquent-зв'язків між модулями
  (жодних `belongsTo` на модель чужого модуля).
- **Міграції БД всередині модуля** (`Infrastructure/Database/Migrations`), завантажуються через провайдер.
- **Enforce boundaries інструментом**: [Deptrac](https://github.com/deptrac/deptrac) або `phparkitect` у CI — інакше
  межі модулів "розповзуться" за 2-3 місяці.
- **Filament** — це чисто `Presentation`-шар. Filament Resources викликають Application UseCases, а не Eloquent напряму
  (інакше втратите Onion).

## Модуль LegacyMigration

Правильно виділяти його як окремий модуль. Рекомендована внутрішня структура:

- **Sources/** — адаптери: `MysqlLegacySource`, `SqlDumpFileSource`, `CsvDumpSource` (спільний інтерфейс
  `LegacySourceInterface`)
- **Mapping/** — декларативні мапінги полів + таблиця відповідності старих/нових ID (remap table у БД — критично для
  злиття кількох джерел)
- **Pipeline/** — Extract → Transform/Remap → Validate → Load, з chunk-обробкою
- **Console/** — artisan-команди: `legacy:import {source}`, `legacy:validate`, `legacy:merge`, обов'язково з `--dry-run`
  та звітом помилок
- Ідемпотентність: повторний запуск не дублює дані (upsert по remap-таблиці)

# Мета № 1

Закласти фундамент: скелет проекту, спільне ядро (`src/Shared/`) та шаблон модуля.

## Обсяг робіт

- Ініціалізація проекту Laravel 13 / PHP 8.5, PSR-4: `"Yeod\\": "src/"`
- `src/Shared/Domain` — базові Value Objects (Money, Email, Phone), інтерфейси Domain Events, базові виключення
- `src/Shared/Application` — контракти Command/Query Bus, базові DTO
- Шаблон модуля: `Domain / Application / Infrastructure / Presentation / Contracts` + Service Provider
- Конвенція реєстрації модулів (провайдери, міграції, роути з модуля)
- Приклад-заготовка модуля як еталон для копіювання

## Правила

- Shared Kernel — мінімальний. Усе, що специфічне для домену — у модулі
- Domain-шар без залежностей від Laravel

## Definition of Done

- Новий модуль створюється копіюванням шаблону за ≤15 хвилин
- Тести на базові VO та контракти

##Епік 1.1: Ініціалізація проекту: Laravel 13 / PHP 8.5, PSR-4

### Задачі

- [ ] Створити проект Laravel 13 на PHP 8.5
- [ ] Додати в `composer.json` autoload PSR-4: `"Yeod\\": "src/"`
- [ ] Створити структуру `src/Shared/` та `src/Modules/`
- [ ] `.gitignore`, `.env.example`, базовий README з описом архітектури
- [ ] Мінімізувати `app/` (залишити тільки bootstrap-обв'язку Laravel)

### Acceptance Criteria

- `composer dump-autoload` без помилок, клас з `Yeod\Shared\...` резолвиться
- README пояснює структуру модуля та правила шарів

##Епік 2.1: Shared/Domain: базові VO та контракти Domain Events

## Задачі

- [ ] VO: `Money`, `Email`, `Phone` (immutable, self-validating, без Laravel)
- [ ] Інтерфейси: `DomainEvent`, `AggregateRoot` (з recordEvent/releaseEvents)
- [ ] Базові доменні виключення
- [ ] `Shared/Application`: контракти `Command`, `Query`, `CommandBus`, `QueryBus`
- [ ] Unit-тести на всі VO

### Acceptance Criteria

- Жодного `use Illuminate\...` у `Shared/Domain`
- 100% покриття VO unit-тестами

##Епік 3.1: Шаблон модуля та конвенція Service Provider

### Задачі

- [ ] Еталонна структура модуля: `Domain / Application / Infrastructure / Presentation / Contracts`
- [ ] Базовий `ModuleServiceProvider`: реєстрація біндінгів, міграцій (`loadMigrationsFrom`), роутів, Filament Resources
  з модуля
- [ ] Конвенція реєстрації модулів у `bootstrap/providers.php`
- [ ] Документація "Як створити новий модуль" (README у `src/Modules/`)

### Acceptance Criteria

- Новий порожній модуль підключається копіюванням шаблону + 1 рядок реєстрації провайдера
- Міграції модуля виконуються через стандартний `php artisan migrate`

# Мета 2

Гарантувати, що межі модулів і шарів Onion не «розповзуться» з часом — перевірка у CI.

## Обсяг робіт

- GitLab CI pipeline: lint (Pint), статичний аналіз (PHPStan/Larastan), тести (Pest/PHPUnit)
- **Deptrac** (або phparkitect): правила
    - Domain не залежить від Application/Infrastructure/Presentation/Laravel
    - Application не залежить від Infrastructure/Presentation
    - Модуль A звертається до модуля B тільки через `Contracts`
- Структура тестів по модулях (Unit для Domain, Feature для Application/Presentation)
- Pipeline падає при порушенні архітектурних правил

## Definition of Done

- CI зелений на чистому скелеті; свідоме порушення правила (тестове) валить pipeline

## Епік 2.1: GitLab CI: Pint, PHPStan, Pest

### Задачі

- [ ] `.gitlab-ci.yml`: стадії lint → analyse → test
- [ ] Laravel Pint (code style)
- [ ] PHPStan/Larastan (рівень ≥6, з підняттям з часом)
- [ ] Pest/PHPUnit з розбивкою тестів по модулях
- [ ] Кешування composer у CI

### Acceptance Criteria

- Pipeline зелений на чистому скелеті
- Падає при порушенні стилю/аналізу/тестів

## Епік 2.2: Deptrac: правила шарів Onion та меж модулів

### Задачі

- [ ] Встановити Deptrac, описати шари: Domain, Application, Infrastructure, Presentation, Contracts
- [ ] Правила: Domain ← нічого; Application ← Domain; Infrastructure ← Domain+Application; Presentation ← Application
- [ ] Правило міжмодульне: модуль A → модуль B тільки через `Contracts`
- [ ] Заборона `Illuminate\*` у Domain
- [ ] Додати крок Deptrac у CI (падіння pipeline при порушенні)

### Acceptance Criteria

- Тестове свідоме порушення (Domain → Eloquent) валить pipeline

