# Security Policy

## Supported version

Security fixes are developed for the current `2.x` release line.

## Sensitive data rules

The plugin must not store:

- Signature images or biometric signature data unless a separately reviewed provider adapter requires and protects them.
- Full provider authentication tokens in logs or event payloads.
- Complete document bodies in audit logs.
- Public URLs to private transaction files.
- Full signer email addresses in cross-platform audit events.

## Required controls

- Restrict operational actions through dedicated capabilities.
- Verify nonces for browser-based state changes.
- Require REST permission callbacks.
- Authenticate provider webhooks against the raw request body.
- Reject duplicate provider event IDs.
- Deliver files only through an authorized controller.
- Verify SHA-256 file integrity before download.
- Keep private storage outside direct public access where hosting permits.
- Use encrypted or environment-managed provider credentials.

## Reporting

Report suspected vulnerabilities privately to the repository owner. Do not include live credentials, unredacted contracts, signer information, or transaction records in a public issue.
