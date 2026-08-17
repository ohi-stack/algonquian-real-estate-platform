# Security Policy

Algonquian Admin Command Center is an administrative and reporting layer. It must not bypass the permissions of authoritative operational plugins.

## Controls

- Direct PHP file access is blocked.
- Administrative screens require custom capabilities.
- Exports require `export_algq_reports` or administrator authority.
- System commands require `run_algq_system_commands` or administrator authority.
- Audit visibility requires `view_algq_audit_logs` or administrator authority.
- State-changing requests require WordPress nonces.
- Output is escaped before rendering.
- CSV output is hardened against spreadsheet formula injection.
- Generated pages are idempotent and do not overwrite administrator content.

## Reporting vulnerabilities

Report security issues privately to the site owner or technology administrator. Do not publish private seller, buyer, lender, transaction, document, signature, or authentication information in a public issue.
