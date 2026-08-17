# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `src.md` — package-local context and rules file (source of truth for agents).
- Application-level tests: `ArchiveService` validation (empty type/id/snapshot,
  oversized snapshot), fail-closed `NotAuthorizedException` for `archive` and
  `restore`, transition of an unknown fulfillment, and rejection of a line with
  `fulfilled_quantity` above `ordered_quantity`.
- `CHANGELOG.md`, `CONTRIBUTING.md`, `SECURITY.md`, `.editorconfig`, and GitHub
  issue templates.

### Changed

- `LICENSE` copyright holder to the real author name.
- `composer.json` description to an honest summary of the actual scope.
- CI (`tests.yml`): constrains all three `illuminate/*` packages, adds a
  `prefer-lowest` matrix dimension, `fail-fast: false`, and Composer caching.
- `phpunit.xml.dist`: `failOnWarning`, `failOnRisky`, `failOnDeprecation`, and a
  `<source>` include.
- Test isolation: global facade application is reset in `tearDownAfterClass`.
- Status isolation tests collapsed into a data provider.
- README: title and support note no longer claim a specific Laravel version;
  version-agnostic; Locad source is linked; host-path test notes moved to
  `CONTRIBUTING.md`.
- Removed the orphaned `src/Contracts/` directory (M7 cleanup).

## [0.1.0] - 2026-08

### Added

- Initial domain: isolated status transition graphs for order, payment,
  fulfillment, shipment, return, and product-availability lifecycles.
- Guarded `Fulfillment` aggregate with immutable lines and derived status.
- Optimistic-concurrency persistence and a fail-closed deep-archive mechanism.
- Package standards (Pint, PHPStan level max, PHPArkitect) and documentation.