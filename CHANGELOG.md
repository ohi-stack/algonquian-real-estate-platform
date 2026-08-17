# Changelog

All notable changes to the Algonquian Real Estate Platform repository are documented here.

The project uses semantic versioning. Release labels must reflect actual validation status.

## Unreleased

### Added

- Canonical plugin manifest covering the protected foundation and optional platform modules.
- WordPress installation-readiness standard.
- Static package validator for plugin headers, required files, PHP syntax, direct-access guards, malformed WPBakery tags, and possible embedded credentials.
- GitHub Actions release gate for package layout and static validation.
- Repository security policy.

### Changed

- Expanded the documented architecture beyond the original five-module release-candidate scope.
- Established unpacked, version-controlled plugin source as the canonical source of record.
- Defined production as an evidence-based release classification rather than a filename or version-header claim.

### Required Before Production

- Reconcile every plugin source directory with `config/plugin-manifest.json`.
- Add missing package-level README, changelog, security, and uninstall files.
- Run activation, deactivation, upgrade, permissions, security, database, generated-page, and end-to-end workflow tests in disposable WordPress environments.
- Build release ZIPs from the tagged canonical source directories.

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
