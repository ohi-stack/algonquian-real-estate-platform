# Production Validation Matrix

## Installation and upgrade

- Install on a clean WordPress 6.8+ site under PHP 8.1 and 8.2.
- Activate with `WP_DEBUG` enabled and confirm no warnings or notices.
- Upgrade from the 1.0.0 table and confirm existing document records remain readable.
- Deactivate and reactivate without duplicate tables, pages, capabilities, or data.

## Access control

- Confirm each capability independently controls its intended screen and action.
- Confirm unauthenticated users cannot list, generate, request, update, or download documents.
- Confirm a logged-in user without `view_algq_documents` cannot use the shortcodes or REST list route.
- Confirm download nonces, document IDs, and hash mismatches fail safely.

## PDF and storage

- Generate one-page and multi-page PDFs.
- Verify PDF MIME type, file name, UUID, version, size, and SHA-256 hash.
- Verify direct browser access to the storage directory is blocked.
- Verify missing, modified, or moved files are not delivered.
- Test the rich renderer filter with the selected production PDF library.

## Signature workflow

- Create a manual request and test every allowed status.
- Confirm request, signer, and event rows remain linked.
- Confirm no ordinary UI can edit or delete audit events.
- Test provider send failure and verify `failed` state and evidence.
- Test authenticated, invalid, duplicate, out-of-order, and unknown-request webhook events.
- Verify provider secrets and complete payloads are absent from logs.

## Integration

- Generate from an approved Offer Generator record.
- Link the output to a Pipeline CRM deal.
- Send notifications through the Platform Mail Gateway.
- Trigger Automation Engine actions on request creation, completion, failure, decline, and expiration.
- Confirm Command Center and Platform Health display accurate status.

## Retention and uninstall

- Verify uninstall preserves data by default.
- Verify deletion occurs only after the explicit setting is enabled.
- Confirm legal hold and retention requirements are resolved before destructive removal.
