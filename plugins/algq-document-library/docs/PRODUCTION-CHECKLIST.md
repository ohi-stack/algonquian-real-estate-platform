# Production Acceptance Checklist

## Installation and Upgrade

- [ ] Installs on a clean WordPress 6.8+ site using PHP 8.2+.
- [ ] Upgrades from 1.0.0 without losing document records.
- [ ] Creates or upgrades both custom tables idempotently.
- [ ] Creates generated pages once and preserves administrator edits.
- [ ] Deactivation and reactivation preserve records and files.

## Security

- [ ] Direct HTTP requests to private files are denied by the production server.
- [ ] File traversal attempts fail.
- [ ] Disallowed MIME types and oversized files are rejected.
- [ ] Unauthorized users cannot download internal, lender, buyer, or approved-request files.
- [ ] Expired files are denied except to authorized administrators.
- [ ] Nonces, capabilities, REST permissions, and request rate limiting are verified.
- [ ] No private path appears in HTML, JSON, logs, or error responses.

## Workflow

- [ ] Request form validates all required fields and consent.
- [ ] Duplicate rapid requests are throttled.
- [ ] Request status changes are capability-protected and audited.
- [ ] Centralized mail delivery is used when the Platform Mail Gateway is active.
- [ ] Document package relationships remain valid after document changes.
- [ ] File replacement records the prior file metadata and integrity hash.

## Integration

- [ ] Platform registry reports version, schema, capabilities, and health.
- [ ] Command Center can consume the health callback.
- [ ] Central audit service receives structured events.
- [ ] Deal, lender, buyer, and signature modules can extend access through documented filters.
- [ ] WPBakery pages render with valid `[vc_column_text]...[/vc_column_text]` syntax.

## Operations

- [ ] Backup and recovery include private storage and custom tables.
- [ ] Retention classes and legal holds match approved company policies.
- [ ] Uninstall cleanup is disabled by default.
- [ ] Incident-response and access-review procedures are documented.
