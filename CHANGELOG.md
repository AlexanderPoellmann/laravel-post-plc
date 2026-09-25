# Changelog

All notable changes to `laravel-post-plc` will be documented in this file.

## 2026-09-25

- Scope client response state to each Laravel request/job and clear stale responses after failed calls.
- Preserve nested collection payloads and list structure during request normalization.
- Share address validation between shipments and pickup orders; clear stale fields when reusing address builders.
- Reject malformed discovery country codes and honor explicitly empty allowed-service results.
- Restrict the CI PHP matrix to supported versions and install SOAP for static analysis.
- Add PLC-aware shipment validation and allowed-services resolution.
- Add transport abstraction and null-only request normalization.
- Fix Post Express Österreich product code serialization and PO Box feature helper.
- Align DTOs, customs articles, printer defaults and request models with PLC API description v1.20.
- Add publishable package configuration with legacy configuration fallback.
- Add Pest tests, architecture checks, Larastan, Pint and GitHub Actions.
