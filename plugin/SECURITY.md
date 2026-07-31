# Security Policy

## Supported version

Security fixes are maintained for the current `2.x` release line.

## Security controls

- Direct file access is blocked.
- Administrative actions require granular capabilities and nonces.
- REST health output requires `view_algq_system_health`.
- SMTP passwords are not stored in WordPress options.
- Mail logs store a recipient hash and domain, not message bodies.
- Audit payloads redact passwords, secrets, tokens, signatures, authorization values, API keys, and account numbers.
- Private files are stored below a guarded upload subdirectory and delivered through short-lived, one-time tokens.
- Generated pages are created only when missing and existing administrator content is preserved.
- Companion plugins retain record-level authorization responsibility for deals, buyers, documents, signatures, funding, and other operational records.

## Reporting

Report suspected vulnerabilities privately to the repository owner. Do not include production credentials, private documents, personal data, access tokens, or exploit payloads in public issues.

## Production deployment requirements

Before release:

- Run PHP syntax checks and WordPress coding standards.
- Test activation, deactivation, upgrade, and conservative uninstall behavior.
- Verify role and capability reconciliation.
- Test SMTP success and failure logging using non-production recipients.
- Confirm private storage cannot be fetched directly from the web server.
- Confirm tokenized downloads expire and cannot be reused.
- Verify page generation does not overwrite existing pages.
- Validate health checks and the REST permission callback.
- Test companion-plugin registration and legacy shortcode handoff.
