# Algonquian PDF & Signature Engine

**Version:** 2.0.0  
**Status:** Production candidate  
**Author:** Onegodian | Algonquian Real Estate Technology Division

## Purpose

The plugin is the authoritative PDF-version and signature-workflow service for the Algonquian Real Estate platform. It generates protected PDF files, stores document hashes and versions, coordinates signature requests, reconciles provider events, and preserves append-only execution evidence.

It does not replace legal review, determine whether a document is enforceable, or certify that a signature method is sufficient for a particular transaction.

## Core controls

- Granular capabilities for viewing, generating, managing, sending, and auditing.
- Four versioned tables for documents, requests, signers, and events.
- Protected storage under `wp-content/uploads/algq-private/pdf-signature`.
- Secure, capability-checked downloads with SHA-256 integrity verification.
- Built-in text PDF renderer plus `algq_pdf_render_document` for rich renderers.
- Manual signature-status tracking plus provider adapters through documented filters.
- Verified, normalized, replay-protected webhook contract.
- Idempotent WPBakery-compatible page generation.
- Conservative uninstall behavior.

## Shortcodes

- `[algq_pdf_engine]`
- `[algq_pdf_engine view="start"]`
- `[algq_pdf_engine view="docs"]`
- `[algq_signature_archive]`

## Generated pages

- `/plugin/pdf-signature-engine/`
- `/plugin/pdf-signature-engine/start/`
- `/plugin/pdf-signature-engine/docs/`
- `/documents/signatures/`

Generated content uses:

```text
[vc_column_text]
[algq_pdf_engine]
[/vc_column_text]
```

Existing pages and administrator-edited content are not overwritten.

## REST API

- `GET /wp-json/algq/v1/pdf-documents`
- `POST /wp-json/algq/v1/pdf-documents`
- `POST /wp-json/algq/v1/signature-webhook/{provider}`

Every internal document route has a permission callback. Public provider webhooks are rejected unless a provider adapter authenticates the raw request through `algq_signature_webhook_authorized`.

## Integration hooks

- `algq_pdf_render_document`
- `algq_signature_providers`
- `algq_signature_send_request`
- `algq_signature_webhook_authorized`
- `algq_signature_normalize_webhook`
- `algq_pdf_document_generated`
- `algq_signature_request_created`
- `algq_signature_status_changed`
- `algq_pdf_signature_health_checks`

## Production gate

Before deployment, complete the matrix in `docs/TESTING.md`, configure a production-grade private-storage layer where the host cannot enforce directory denial rules, and validate each external signature provider in a sandbox before enabling live requests.
