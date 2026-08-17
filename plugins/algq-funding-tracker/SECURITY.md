# Security Policy

## Supported version

Security fixes are applied to the current production release of Algonquian Funding Tracker.

## Security controls

- Every administrative write requires `edit_algq_funding` and a WordPress nonce.
- Every protected view requires `view_algq_funding`.
- REST routes use explicit permission callbacks.
- Database reads use prepared statements where parameters are present.
- Stored values are sanitized and rendered values are escaped.
- Activity records redact notes, conditions, email addresses, and telephone numbers from audit payloads.
- The plugin does not store bank credentials, payment-card data, tax identification numbers, account numbers, routing numbers, authentication tokens, or executed signatures.
- Deactivation and ordinary uninstall do not remove operational records.

## Reporting a vulnerability

Do not disclose vulnerabilities publicly before remediation. Provide the affected version, reproduction steps, expected result, actual result, and any available logs to the authorized Algonquian Real Estate technology administrator.
