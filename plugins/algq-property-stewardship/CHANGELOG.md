# Changelog

## Production-observed 1.2.0 — source reconciliation pending — 2026-09-09

- The live AlgonquianRealEstate.com WordPress Plugins inventory reports Algonquian Property Stewardship Services version 1.2.0.
- Canonical repository source still declares version 1.0.0.
- No exact 1.2.0 source package, commit, tag, or branch has been identified in this repository.
- Version 1.2.0 is therefore recorded as the production downgrade floor, not as reconstructed canonical source.
- Do not package or deploy the repository's 1.0.0 Stewardship source over the live 1.2.0 installation.
- Recover and compare the deployed 1.2.0 files before adding a formal 1.1.0/1.2.0 source changelog or promoting the repository source version.
- Recovery and validation requirements are documented in `docs/reconciliation/PROPERTY-STEWARDSHIP-1.2.0.md`.

## 1.0.0 — 2026-08-17

- Promoted from release-candidate module to canonical plugin source.
- Raised requirements to WordPress 6.8 and PHP 8.2.
- Added registered private meta schemas and sanitizers.
- Added owner-scoped portal record authorization.
- Added owner-and-client-scoped visit authorization.
- Replaced direct photo/file exposure with protected Document Library identifiers and secure-link integration.
- Added granular management and portal capabilities.
- Added idempotent WPBakery page generation and conservative data handling.
