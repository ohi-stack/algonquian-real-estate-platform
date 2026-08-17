# WordPress Installation Readiness Standard

## Status

This document defines the mandatory release gate for every Algonquian Real Estate WordPress plugin package. A ZIP filename, semantic version, or successful static scan does not by itself establish production readiness.

## Canonical Source Rule

Every plugin must be maintained as unpacked source in version control. Generated ZIP archives are release artifacts only and must not be the sole source of record.

Recommended repository layout:

```text
plugins/
  algonquian-real-estate-platform/
  algq-deal-intake/
  algq-pipeline-crm/
  algq-mao-engine/
  algq-offer-generator/
  algq-document-library/
  algq-pdf-signature/
  algq-automation-engine/
  algq-command-center/
  algq-buyer-portal/
  algq-deal-marketplace/
  algq-digital-store/
  algq-woocommerce-bridge/
  algq-property-stewardship/
```

## Required Package Contents

Each independently installable plugin directory must include:

- A single, unambiguous plugin entry file at the package root.
- Valid WordPress plugin headers.
- `README.md`.
- `CHANGELOG.md`.
- `SECURITY.md`.
- `uninstall.php` with conservative, documented cleanup behavior.
- Namespaced or `algq`-prefixed PHP symbols.
- Activation and deactivation handlers.
- Dependency validation that fails safely.
- Capability and nonce checks for state-changing actions.
- Input validation, sanitization, and output escaping.
- No plaintext production credentials.
- No debug output.

## Plugin Header Standard

Every plugin must declare at least:

```text
Plugin Name:
Description:
Version:
Author:
Text Domain:
Requires at least:
Requires PHP:
License:
```

Production releases must not retain `alpha`, `beta`, `rc`, or `release candidate` labels unless intentionally distributed as prereleases.

## Dependency and Authority Rules

The Platform Plugin owns shared infrastructure: registry, capabilities, security, mail transport, audit logging, private file services, health monitoring, generated pages, and common UI.

Operational plugins must not silently duplicate another plugin's authoritative records. The canonical authority model is maintained in `config/plugin-manifest.json`.

A missing dependency must result in a controlled administrative notice and disabled functionality—not a fatal site error.

## WPBakery Page Rule

Generated WPBakery content must use:

```text
[vc_column_text]
Content or plugin shortcode
[/vc_column_text]
```

The malformed HTML-style closing tag `</vc_column_text>` is prohibited.

Page generation must be idempotent. It must not create duplicates, and it must preserve administrator-edited page content when the required plugin shortcode or generation metadata remains present.

## Activation Testing

Every package must be tested on a fresh WordPress installation using the supported minimum and current WordPress/PHP combinations.

Minimum activation matrix:

1. Install ZIP through **Plugins → Add New → Upload Plugin**.
2. Confirm WordPress accepts the package structure.
3. Activate with only required dependencies enabled.
4. Confirm missing optional integrations do not cause a fatal error.
5. Confirm required database tables and options are created once.
6. Deactivate and reactivate without data loss or duplicate schema creation.
7. Upgrade from the prior supported version.
8. Confirm generated pages are created once and use valid shortcodes.
9. Confirm uninstall behavior matches documentation and does not erase business records without explicit authorization.

## Security and Permissions Testing

Test at minimum:

- Administrator.
- Platform manager.
- Acquisition user.
- Buyer or registered external user, where applicable.
- Logged-out visitor.

Verify:

- Administrative screens require the intended capability.
- REST routes use permission callbacks.
- AJAX actions use capability and nonce checks.
- Record-level authorization is enforced.
- Private documents and photographs cannot be accessed by direct URL.
- Public forms have server-side validation, rate limiting, anti-spam controls, consent capture, upload restrictions, and safe error messages.
- Logs do not contain secrets, full financial account data, signatures, or confidential message bodies.

## Functional Smoke Tests

### Platform Plugin

- Registry loads.
- Dependency status is accurate.
- capabilities are assigned without removing unrelated permissions.
- Mail test records success or failure.
- Audit writes are append-only through ordinary interfaces.
- Private file test passes.
- Health checks fail gracefully.

### Deal Intake

- Seller submission creates one normalized intake.
- Duplicate review works without discarding legitimate submissions.
- Consent is recorded.
- Attachments are private.
- Pipeline CRM receives one canonical deal creation request.

### Pipeline CRM

- Canonical deal ID remains stable.
- Kanban changes are validated server-side.
- Notes, tasks, assignments, activity, and stage history persist.
- Invalid stage transitions are rejected.
- Closed, lost, withdrawn, and archived states are auditable.

### MAO Engine

- Verified formula fixtures return expected results.
- Formula and assumption versions are recorded.
- Manual overrides require permission and a reason.
- Approved scenarios cannot be silently modified.

### Offer Generator

- Offer values resolve from the intended deal and approved scenario.
- Missing merge fields block finalization.
- Revisions create new versions.
- Delivery uses the Platform Mail Gateway.

### Document Library

- Direct URLs do not bypass access checks.
- Version hashes remain stable.
- Retention and legal-hold controls require permission.
- Executed documents cannot be overwritten.

### PDF & Signature Engine

- Generated PDFs match approved source data.
- Repeated provider callbacks are idempotent.
- Completed files are locked.
- Signature audit evidence can be exported.

### Automation Engine

- Jobs are queued outside public request execution.
- Duplicate events do not run twice.
- Retry limits and dead-letter handling work.
- Circular rules are blocked.
- Emergency pause works.

### Admin Command Center

- KPI formulas reconcile with source records.
- A degraded plugin does not break the full dashboard.
- Reports enforce data permissions.
- Administrative commands create audit records.

### Optional Modules

Buyer Portal, Marketplace, Digital Store, WooCommerce Bridge, and Property Stewardship must each enforce their own record-level authorization, secure downloads, dependency controls, and service-specific boundaries.

## End-to-End Workflow

The protected foundation is not release-ready until a complete test transaction succeeds:

```text
Public seller submission
→ canonical deal creation
→ pipeline assignment
→ underwriting
→ offer generation
→ PDF generation
→ document storage
→ signature request
→ signature completion
→ pipeline update
→ automation actions
→ dashboard reporting
→ audit-log verification
```

## Release Artifact Construction

Release ZIPs must be built from canonical source directories. The ZIP must contain exactly one top-level plugin directory and no repository-only material such as `.git`, test caches, local environment files, secrets, source ZIPs, or unrelated packages.

Before publication:

- Run `php scripts/validate-wordpress-plugins.php`.
- Run the GitHub Actions release gate.
- Install the generated ZIP into a disposable WordPress site.
- Record WordPress version, PHP version, database version, active dependencies, tester, date, and result.
- Update the changelog.
- Tag the exact source commit used to build the artifact.

## Release Classification

Use these classifications accurately:

- **Development** — incomplete or untested implementation.
- **Alpha** — functional areas exist but major behavior may change.
- **Beta** — feature-complete for the release scope; testing remains.
- **Release Candidate** — no known release blockers; full acceptance testing in progress.
- **Production** — all applicable release-gate evidence has passed.

No package may be called production-ready solely because its filename or plugin header contains `1.0.0`.
