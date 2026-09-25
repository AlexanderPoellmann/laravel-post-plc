# Changelog

All notable changes to `laravel-post-plc` will be documented in this file.

## Unreleased

- Fix unknown product/feature hydration through Spatie Data and normalize wrapped SOAP response collections.
- Refresh cached capability results on fresh lookups and surface PLC discovery errors without caching them.
- Normalize merchant policy codes, honor available fallback products, and scope policy instances to requests/jobs.
- Supply PDF printer defaults and explicit parcel data for returns; support Business Paketmarke QR requests.
- Complete v2 printer formats and correct insurance and German poste-restante validation against the workbook.
- Expand regression coverage and enforce LF line endings for consistent Pint checks on Windows and Linux.
- Update protocol coverage to Austrian Post PLC API v2.0 (26 February 2025).
- Add `ImportShipmentReturnImage`, `ImportShipmentForce`, `ImportPickupOrderBusiness`, `BuildGroupageShipment`, and `CompleteGroupageShipment` support while retaining the old pickup method for compatibility.
- Add all 22 current PLC product codes and all 49 current additional-service codes; retain four legacy product codes for existing contracts.
- Add forward-compatible `ProductCode` and `FeatureCode` value objects so new server-advertised codes can be used before a package release adds an enum constant.
- Add v2.0 shipment/importer/address/document/groupage fields and exact wire-name mappings such as `OrgUnitGUID` and `SecurePickupLocationTypeID`.
- Add first-class return shipment and return-label helpers, including QR returns through `ImportShipmentAndGenerateBarcode`.
- Expand allowed-service discovery into a typed capability catalog with names, sorting, contract-product metadata, feature names and configurable caching.
- Add merchant service policy for enabled/disabled products/features and per-country preferred products.
- Add named PLC profiles for multi-store, multi-tenant and multiple-contract applications.
- Correct API v2.0 field-length validation for addresses, article names and collo codes.
- Fix the Dependabot auto-merge repository condition and stale issue-template repository links inherited from the package skeleton.
- Add Pest coverage for API v2.0 catalogs, serialization, capabilities, caching, profiles, merchant policy and return workflows.

## 2026-09-25

- Scope client response state to each Laravel request/job and clear stale responses after failed calls.
- Preserve nested collection payloads and list structure during request normalization.
- Share address validation between shipments and pickup orders; clear stale fields when reusing address builders.
- Reject malformed discovery country codes and honor explicitly empty allowed-service results.
- Restrict the CI PHP matrix to supported versions and install SOAP for static analysis.
- Add PLC-aware shipment validation and allowed-services resolution.
- Add transport abstraction and null-only request normalization.
- Fix Post Express Österreich product code serialization and PO Box feature helper.
- Align DTOs, customs articles, printer defaults and request models with the older PLC API description.
- Add publishable package configuration with legacy configuration fallback.
- Add Pest tests, architecture checks, Larastan, Pint and GitHub Actions.
