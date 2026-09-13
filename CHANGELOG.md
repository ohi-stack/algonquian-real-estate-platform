# Changelog

All notable changes to the Algonquian Real Estate Platform repository are documented here.

The project uses semantic versioning. Release labels must reflect actual validation status.

## Unreleased

### Added

- Current production-readiness status with ten-layer acceptance, evidence-ledger, release-sequence, stop-condition, and rollback requirements.
- Live canonical public-page audit covering all 17 plugin overview routes and duplicate-page reconciliation.
- Canonical plugin manifest covering the protected foundation and optional platform modules.
- WordPress installation-readiness standard.
- Static package validator for plugin headers, required files, PHP syntax, direct-access guards, malformed WPBakery tags, and possible embedded credentials.
- GitHub Actions release gate for package layout and static validation.
- Repository security policy.

### Changed

- Corrected the platform classification from production candidate to development/production hardening while known blockers remain.
- Expanded the documented architecture beyond the original five-module release-candidate scope.
- Established unpacked, version-controlled plugin source as the canonical source of record.
- Defined production as an evidence-based release classification rather than a filename or version-header claim.

### Required Before Production

- Reconcile every plugin source directory with `config/plugin-manifest.json` and consolidate active implementation pull requests in dependency order.
- Reconcile all 17 canonical public routes; complete thin/empty pages and stop producing numbered or nested duplicate pages.
- Add missing package-level README, changelog, security, data-lifecycle, compliance, rollback, and uninstall documentation.
- Verify public, dashboard, admin, API/Bridge, data, security, UI/UX, documentation, compliance, and deployment layers for every included package.
- Run clean installation, activation, deactivation/reactivation, prior-version upgrade, permissions, security, database, generated-page, shortcode, responsive, and end-to-end workflow tests in disposable WordPress environments.
- Build reproducible release ZIPs from tagged canonical source directories, inspect package contents, generate SHA-256 checksums, and verify clean activation.
- Record database, files, page-content, plugin-package, migration, and route rollback evidence before production deployment.

## 1.0.0-rc.1

### Added

- Core platform plugin bootstrap.
- Seller intake shortcode.
- MAO calculator shortcode.
- Buyer registration shortcode.
- Admin dashboard shortcode.
- Activation hook.
- Core database tables.
- Admin settings screen.
- Initial nonce-protected administrative forms.
- Initial capability checks, sanitization, and escaping standards.
- Production-readiness plan, plugin page map, and revenue-system documentation.

### Status

Release candidate for controlled staging. This version is not classified as production-ready without the current installation-readiness evidence.
