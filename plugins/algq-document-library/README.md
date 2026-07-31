# Algonquian Document Library

**Version:** 2.0.0  
**Release status:** Production upgrade candidate  
**Author:** Algonquian Real Estate Technology Division

## Purpose

Algonquian Document Library is the authoritative document-record and controlled-delivery module for the Algonquian Real Estate platform. It centralizes institutional forms, source files, templates, access classifications, version metadata, request review, protected downloads, and document-package assembly.

## Material 2.0.0 Upgrade

Version 2.0.0 replaces the 1.0.0 release-candidate architecture with:

- Granular platform capabilities instead of broad `manage_options` authorization.
- Private file storage with direct web-access blocking.
- Validated uploads, MIME restrictions, size limits, SHA-256 file hashes, and protected streaming.
- Durable access-request and download-audit tables instead of one unbounded WordPress option array.
- Request UUIDs, status workflow, consent versioning, privacy-preserving IP/user-agent hashes, rate limiting, and a honeypot.
- Document taxonomy, confidentiality, access, retention, expiration, legal hold, related-deal, and template controls.
- WordPress revisions plus file-version metadata history.
- Document package records and package membership controls.
- Least-privilege REST metadata access under `algq/v1/documents` without exposing file paths.
- Platform registry, health-check, centralized mail, and structured audit integration points.
- Idempotent WPBakery-compatible page generation that never overwrites administrator content.
- Conservative uninstall behavior.

## Shortcodes

- `[algq_document_library]`
- `[algq_document_request]`
- `[algq_document_packages]`

## Generated Pages

- `/documents/`
- `/documents/packages/`
- `/documents/request-access/`
- `/plugin/document-library/`
- `/plugin/document-library/start/`
- `/plugin/document-library/docs/`

## Document Categories

- Entity & Corporate Documents
- Lender & Financing Documents
- Acquisition & Due Diligence Forms
- Financial Controls & Reporting
- Risk Management & Compliance
- Property Management Forms (Connecticut)

## Security Model

Raw private paths are never returned by shortcodes or the REST API. Files are delivered only through the nonce-protected download controller after document-level authorization. Access decisions can be extended through `algq_document_user_can_access`.

Public access requests do not create automatic entitlement. A reviewer must approve the request and the receiving user must still satisfy document-level authorization.

## Production Validation Still Required

Before deployment to a production website, validate:

1. Clean installation and upgrade from 1.0.0.
2. Private-storage blocking on the actual web server and hosting stack.
3. Upload, replacement, download, expiration, and access-denial flows.
4. Capability behavior for every platform role.
5. Request review, centralized email delivery, and audit ingestion.
6. Page creation and WPBakery rendering.
7. REST permission and pagination behavior.
8. Backup, retention, legal-hold, and disaster-recovery procedures.
