# Security Policy

Report suspected vulnerabilities privately to the Algonquian Real Estate Technology Division.

Version 2.0.0 applies:

- Granular capabilities for viewing, creating, editing, assigning, transitioning, archiving, managing, and exporting deals.
- REST permission callbacks on every route.
- WordPress nonces for administrative state changes.
- Server-side transition validation.
- Prepared SQL and whitelisted query controls.
- Sanitized stored values and escaped rendered values.
- Optimistic locking to prevent silent concurrent overwrite.
- Append-only transition and activity records through ordinary interfaces.
- Data retention by default on deactivation and uninstall.

Deal, seller, buyer, lender, funding, and closing information must be treated as confidential operational data. Do not expose Pipeline CRM pages to untrusted logged-in roles.
