# FIX VERIFICATION — Commerce Lifecycle

Таск-лист перевірки після виконання фіксів за `AUDIT.md`.
Стан: `[x]` = виконано і перевірено, `[ ]` = ще ні.

## Конвенція комітів

- **Один коміт = один пункт** аудиту (одна галочка), з Conventional-стилем:
  `fix(B1): composite PK for fulfillment lines`, `tests(H1): no mutation on failed fulfillLine`, `chore(M9): prepare composer for Packagist`.
- **Етап вважається пройденим** (`[x] ✅ Ворота етапу`) лише коли зелені всі базові ворота:
  ```
  composer dump-autoload --optimize --strict-psr
  composer analyse      # phpstan level 8, incl. larastan + tests
  composer test         # phpunit, failOnWarning/Risky/Deprecation
  ```
- Після проходження воріт етапу — окремий `chore(ci): stage N gates green` коміт (якщо потрібен), або просто на наступний пункт.
- Не комітити «наполовину»: пункт закривається тільки разом з його тестом/валідацією.

---

## Етап 0 — Рішення зафіксовані
- [x] D1: дефолт авторайзера → `DenyAllAuthorizer` (AllowAll = opt-in)
- [ ] D2: лінії → immutable (`withFulfilled()`, H4-варіант B)
- [ ] D3: `findSnapshot` → `whereNull('restored_at')` (H8-варіант 1)
- [ ] D4: архів → append-only з `snapshot_version` (H9)
- [ ] D5: Laravel зужено до `^12.0`
- [x] ✅ **Ворота етапу пройдено**

---

## 🔴 Етап 1 — Блокери (B1–B5) · порядок B2→B1→B5→B4→B3
- [x] **B2** `authorize()` витягнуто в приватний метод, guard у ВСІХ 4 методах ArchiveService
- [x] **B2** дефолт → DenyAll; `## Security` блок у README з fail-open плашкою
- [x] **B1** міграція: `commerce_fulfillment_lines` → складений PK `['fulfillment_id','id']` + FK
- [x] **B1** `FulfillmentLineModel`: `$incrementing=false`, `$timestamps=false`, `$keyType='string'`, `$primaryKey=null`
- [x] **B1** тест: 2 fulfillment з однаковими `line-1`/`line-2` зберігаються без UNIQUE-помилки
- [x] **B5a** усі 6 тест-класів → `namespace Yeod\CommerceLifecycle\Tests\Unit;`
- [x] **B5a** `FakeArchiveRepository` → `tests/Doubles/FakeArchiveRepository.php`
- [x] **B5b** `phpstan.neon`: `includes: vendor/larastan/larastan/extension.neon`, paths += `tests`
- [x] **B4** `bumpVersion`-мутація лише після коміту; TOCTOU `exists→insert` розвʼязано
- [x] **B4** `replaceLines()` → `upsert()` + `whereNotIn()->delete()`
- [x] **B4** тест: збій `replaceLines()` не розсинхронізує версію агрегату (немає хибного StaleAggregate)
- [x] **B3** провайдер: валідація class-string + `is_a(Authorizer)`, без глобальних `config()/app()`
- [x] ✅ **Ворота етапу пройдено**

---

## 🟠 Етап 2 — Високий (H1–H9) · спочатку звʼязка H1+H2+H4
- [ ] **H1+H2+H4** `fulfillLine()`: валідація вгору, мутації вниз; OnHold → `InvalidTransitionException`
- [ ] **H1+H2+H4** `$line->withFulfilled()` (immutable), `lines()` узгоджено з репо/`toArray()`
- [ ] **H1+H2+H4** тест: завеликий `$quantity` не змінює статус і не дає `releaseEvents()`
- [ ] **H3** `Exceptions\InvalidArgumentException extends CommerceLifecycleException` + заміна імпортів (Fulfillment, FulfillmentLine, TransitionFulfillment)
- [ ] **H5** `reconstitute()` → `@internal` / `FulfillmentFactory` + перевірка узгодженості на restore
- [ ] **H6** `isFinal()` узгоджено (продукти: `false` / `isRetired()`); тест `is_final_implies_no_outgoing_transitions` (падає на продуктах → тепер зелений)
- [ ] **H7** `empty()` → `=== ''` / `=== []`; `mb_strlen` для лімітів
- [ ] **H8** `findSnapshot()` + `whereNull('restored_at')`; docblock «deepest/latest» виправлено
- [ ] **H9** append-only: міграція + `archive()` з версією + `findSnapshot()` `latest()` + `restore()` останнього рядка
- [x] ✅ **Ворота етапу пройдено**

---

## 🟡 Етап 3 — Середній (M1–M9) · у 0.10
- [ ] **M1** `?Authorizer` у `TransitionFulfillment` + `authorize('transition','fulfillment')`
- [ ] **M2** «Event delivery guarantees» (at-most-once) у README / `EventStore` port
- [ ] **M3** ліміт `metadata` (валідація або документація)
- [ ] **M4** `mb_strlen`(reason) / `strlen`($encoded), повідомлення узгоджені
- [ ] **M5** серіалізацію винесено з домену (`FulfillmentSnapshot`) або свідомо прийнято
- [ ] **M6** `status`: CHECK або guarded cast → `CommerceLifecycleException`
- [ ] **M7** контракти зведено в `Domain/`; `src/Contracts` прибрано
- [ ] **M8** БД CHECK `fulfilled <= ordered`
- [ ] **M9** composer Packagist-ready: нема `version`, testbench ^10|^11, laravel ^12 (D5), authors/keywords/support, `.gitattributes`
- [x] ✅ **Ворота етапу пройдено**

---

## ⚪️ Етап 4 — Дрібниці (L) + обвʼязка (M10–M13)
- [ ] **L1** `@package fila` прибрано (2 файли)
- [ ] **L2** self-namespace import прибрано (Fulfillment.php)
- [ ] **L3** коментарі-виправдання в `EloquentArchiveRepository` прибрано
- [ ] **L4** LICENSE: реальне імʼя/організація
- [ ] **L5** чесний `description` у composer.json
- [ ] **L6** phpstan: `tests` в paths, `parallel`, baseline
- [ ] **M10** CI `tests.yml`: composer порядок, constreйн на 3 illuminate, fail-fast:false, prefer-lowest, cache
- [ ] **M11** README: «Laravel 13» прибрано, host-path нотатки → CONTRIBUTING, Locad+URL
- [ ] **M12** тест-ізоляція (`tearDownAfterClass`, без global facade), data-provider → 6 тестів; додано Application-тести (ArchiveService-валідація, NotAuthorizedException, transition з неіснуючим id, fulfilled>ordered); `phpunit.xml.dist` failOn* + `<source>`
- [ ] **M13** `CHANGELOG.md`, `SECURITY.md`, `CONTRIBUTING.md`, `.editorconfig`, шаблони issue
- [x] ✅ **Ворота етапу пройдено**

---

## 🚀 Етап 5 — Реліз
- [ ] `0.9.0` з «API may change before 1.0», `^12.0`
- [ ] `1.0` ТІЛЬКИ після M1, M2, M7, M9 (BC-breaks)
