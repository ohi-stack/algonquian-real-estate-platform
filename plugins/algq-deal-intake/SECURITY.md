# Security Policy

## Supported version

Security fixes are maintained for the current `2.x` release line.

## Sensitive data

Deal Intake may hold seller contact information, property information, consent evidence, intake notes, supporting documents, and generated PDF submission records. Access must be limited to authorized personnel. Logs must not contain message bodies, full documents, authentication tokens, financial account numbers, or other secrets.

## Required deployment controls

- HTTPS must be enabled.
- WordPress salts must be unique.
- Administrator accounts must use strong authentication and least privilege.
- The Platform Plugin must control shared mail, audit, and platform-wide security services.
- Database backups and retention must be documented.
- Public submission endpoints should use the included Cloudflare Turnstile integration or an equivalent production edge challenge in addition to the built-in honeypot, minimum-form-time check, and rate limit.
- Consent language and retention periods should be reviewed by qualified counsel for the deployed use case.
- Archive email must use an authenticated business-mail transport and a documented retention policy because generated submission PDFs contain seller and property information.

## Protected supporting files

Deal Intake 2.1 stores accepted supporting files under the protected Deal Intake storage root, which defaults to:

```text
wp-content/uploads/algq-private/deal-intake/
```

Controls include:

- Non-executable MIME allowlist.
- Server-side file-type inspection with WordPress APIs.
- Configurable count and size limits.
- UUID-based storage filenames.
- SHA-256 integrity hashes.
- Attachment metadata stored separately from public URLs.
- Capability-gated delivery for generated private PDF attachments.

The generated `.htaccess` deny rules are defense in depth for Apache-compatible deployments. They are **not** a portable authorization boundary. Nginx, IIS, reverse proxies, CDNs, object storage, and managed hosts must be configured so direct requests to `/wp-content/uploads/algq-private/` cannot bypass the WordPress authorization controller.

Recommended Nginx policy concept:

```nginx
location ^~ /wp-content/uploads/algq-private/ {
    deny all;
    return 403;
}
```

Adapt that rule to the actual deployment architecture and test it before production certification.

## Private Media Library PDFs

Generated Deal Intake PDFs may be registered in the WordPress Media Library for authorized operational discovery. Registration does not make them public documents.

The plugin marks those attachments as private Deal Intake artifacts, replaces their ordinary attachment URL with a signed `admin-post.php` route, and requires the `view_algq_intake_private` capability before file delivery. Deployment testing must confirm that the underlying storage path is also inaccessible directly.

The PDF archive remains authoritative even when email delivery fails or the file exceeds the configured email-attachment ceiling.

## Cloudflare Turnstile

Turnstile is enforced only when a secret key is configured. Production deployments using the built-in integration should provide credentials through protected configuration such as `wp-config.php` constants rather than publicly exposed page content:

```php
define( 'ALGQ_DI_TURNSTILE_SITE_KEY', '...' );
define( 'ALGQ_DI_TURNSTILE_SECRET_KEY', '...' );
```

The server verifies the challenge response with Cloudflare before the public submission handler proceeds. If no secret is configured, the existing honeypot, minimum-submit-time, and rate-limit protections remain active, but the deployment should not be considered fully hardened against automated abuse solely on that basis.

## Production verification

Before release certification, test at minimum:

- Allowed and disallowed upload formats.
- Oversize and excessive-file-count handling.
- Direct web requests to the protected storage path.
- Authorized and unauthorized private PDF downloads.
- Turnstile success, failure, timeout, and missing-token behavior.
- Archive-email delivery through the deployed mail transport.
- Oversize PDF behavior when the archive exceeds the email-attachment ceiling.
- Duplicate review and repeat-acceptance idempotency.
- Audit logging without disclosure of document bodies or secrets.
- Backup and retention handling for protected files and attachment metadata.

## Reporting

Report suspected vulnerabilities privately to the repository owner. Do not disclose seller, property, consent, supporting-document, or PDF archive data in a public issue.
