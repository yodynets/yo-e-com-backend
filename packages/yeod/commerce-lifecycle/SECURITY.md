# Security Policy

## Reporting a vulnerability

Please do **not** open a public issue for a security problem. Instead, report it
privately by emailing the maintainer named in [composer.json](composer.json)
(`authors[0].name`).

You should receive a response within a few days; if you do not, please follow up
to make sure your report was received.

## Supported versions

Only the latest release in the `0.x` series receives security patches until
`1.0` is released. After `1.0`, the most recent `1.x` minor receives security
fixes; older minor versions are supported for one release cycle.

## Security posture

- **Authorization is fail-closed by default.** The package ships
  `DenyAllAuthorizer` as the default for `ArchiveService` and
  `TransitionFulfillment`. An operator who has not configured an authorizer
  gets every operation **denied**, not allowed. `AllowAllAuthorizer` exists for
  local development only and must never be the production default.
- **The domain is framework-free.** Business rules (transitions, quantities,
  snapshots) are enforced in the domain, not in controllers or Eloquent
  observers.
- **Archive snapshots hold sensitive data.** `findSnapshot()` returns the full
  stored JSON (addresses, amounts) and is itself guarded by the `view` action of
  the `Authorizer` port. Keep snapshots encrypted at rest and retain them only
  as long as analytics or audit require.