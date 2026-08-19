# Algonquian Deal Intake

**Version:** 2.1.0  
**Status:** Production candidate  
**Author:** Onegodian | Algonquian Real Estate Technology Division

Algonquian Deal Intake is the authoritative entry point for seller leads and property submissions entering the Algonquian Real Estate Platform. It records seller and property information, versioned consent evidence, lead source, lead score, duplicate review, protected submission artifacts, and a controlled handoff request to Pipeline CRM.

## Authority boundary

Deal Intake owns intake submissions and their submission-time evidence. It does **not** own pipeline stages, tasks, underwriting, offers, funding, or closing status. After an intake is accepted, Pipeline CRM must return the canonical deal ID.

## Requirements

- WordPress 6.8+
- PHP 8.2+
- Algonquian Real Estate Platform Plugin
- Pipeline CRM for canonical deal creation after acceptance
- HTTPS on the deployed site
- A production mail transport for reliable archive delivery
- An explicit web-server deny rule for `/wp-content/uploads/algq-private/` when the server does not honor Apache `.htaccess` files

## Current shortcodes

```text
[algq_deal_intake_form]
[algq_property_submission]
[deal_intake_form_public]
[deal_intake_form_internal]
[deal_quick_capture]
[algq_homeowner_options]
[algq_seller_portal]
```

During the public seller-funnel migration, the retired `[algq_seller_intake_entry]` tag is also registered as a compatibility alias to the canonical public intake form. New pages should use `[algq_deal_intake_form]` or `[algq_property_submission]`.

WPBakery placement:

```text
[vc_column_text]
[algq_deal_intake_form]
[/vc_column_text]
```

Do not use the malformed HTML-style closing token `</vc_column_text>`.

## Public seller funnel

The 2.1.0 production candidate reconciles the live public conversion routes without overwriting administrator-authored page copy:

- `/submit-a-property/`
- `/sell-your-property/`

The older generated `/submit-property/` route redirects to `/submit-a-property/` after reconciliation. Known placeholder text and the retired seller-intake shortcode are repaired in place; otherwise one canonical WPBakery form block is appended once.

## Submission workflow

```text
Public or Internal Form
        ↓
Nonce / permission checks
        ↓
Honeypot + minimum submit time + rate limit
        ↓
Optional Cloudflare Turnstile verification
        ↓
Server-side validation and normalization
        ↓
Seller + property + submission + consent records
        ↓
Duplicate detection and lead scoring
        ↓
Supporting files moved to protected storage
        ↓
PDF submission record generated
        ↓
Private Media Library attachment registered
        ↓
Archive PDF emailed
        ↓
Administrative review
        ↓
Accepted submission → Pipeline CRM canonical deal ID
```

## Supporting file uploads

Public, internal, and quick-capture Deal Intake forms support optional multipart uploads.

Default accepted formats:

- PDF
- JPEG
- PNG
- WEBP
- DOCX

Default limits:

- 8 files per submission
- 10 MB per file

Both limits and the MIME allowlist are filterable. Files are verified server-side with WordPress file-type inspection, renamed with UUID-based storage names, hashed with SHA-256, and stored under the protected Deal Intake directory rather than exposed through an ordinary public media URL.

Default protected storage root:

```text
wp-content/uploads/algq-private/deal-intake/
```

A deployment can replace that root with the `algq_di_private_storage_dir` filter.

## PDF submission archive

After a successful database commit, Deal Intake creates a PDF record containing the submission reference, seller/contact information, property information, asking price, timeline, source, lead score, duplicate status, condition/situation notes, motivation, supporting-file count, and the non-contractual intake notice.

By default:

- PDF creation is enabled.
- The PDF is stored under protected Deal Intake storage.
- The PDF is registered in the WordPress Media Library as a private Deal Intake attachment.
- The PDF receives SHA-256 integrity metadata.
- Authorized users receive a capability-gated WordPress download URL rather than a direct public file URL.
- A PDF archive copy is emailed to `algonquianre@gmail.com` unless the `algq_di_archive_email` setting or filter changes the destination.

Relevant options:

```text
algq_di_archive_email
algq_di_archive_pdf_enabled
algq_di_pdf_media_library_enabled
algq_di_max_upload_bytes
algq_di_max_upload_files
```

The ordinary new-submission operational notification remains separate from the PDF archival email.

## Turnstile support

The existing honeypot, minimum-form-time check, and rate limiting remain active. For production public forms, configure Cloudflare Turnstile or an equivalent edge challenge.

Built-in Turnstile configuration accepts constants in `wp-config.php`:

```php
define( 'ALGQ_DI_TURNSTILE_SITE_KEY', '...' );
define( 'ALGQ_DI_TURNSTILE_SECRET_KEY', '...' );
```

The equivalent WordPress options or `algq_di_turnstile_site_key` / `algq_di_turnstile_secret_key` filters may also provide the credentials. Turnstile is enforced only when a secret key is configured.

## Private Media Library behavior

Generated Deal Intake PDFs can appear in the Media Library for operational discoverability, but they are not ordinary public media assets.

Private PDF attachments are marked with:

```text
_algq_di_private_attachment
_algq_di_submission_id
_algq_di_sha256
_algq_document_class = deal-intake-pdf
```

Their attachment URL is replaced with a signed, capability-gated `admin-post.php` download route requiring `view_algq_intake_private`.

Apache deny rules are created in the protected storage root as defense in depth. Nginx and other servers must be configured separately to deny direct requests to `/wp-content/uploads/algq-private/`; application-level download authorization must not be bypassed by a directly reachable storage URL.

## Security controls

- Dedicated capabilities instead of blanket `manage_options`
- Nonce verification for state-changing browser requests
- Server-side validation and sanitization
- Public honeypot and minimum-form-time checks
- Configurable per-origin hourly rate limiting
- Optional server-verified Cloudflare Turnstile
- Versioned consent, privacy, terms, IP, user-agent, and acceptance-time evidence
- Prepared SQL for variable database queries
- Strict supporting-file MIME, size, and count validation
- UUID storage names and SHA-256 artifact hashes
- Protected storage and capability-gated private PDF delivery
- CSV formula-injection protection
- Append-only integration hooks for the shared audit service
- No public REST endpoint for anonymous submission creation
- No seller records exposed without authorization

## Pipeline CRM contract

Deal Intake attempts the function below when available:

```php
algq_pipeline_create_deal( $payload );
```

It also applies this compatibility filter:

```php
apply_filters( 'algq_pipeline_create_deal', $deal_id, $payload, $submission_id );
```

Pipeline CRM must return a stable integer deal ID. If it does not, the intake remains in `awaiting_pipeline` rather than creating a second authoritative deal record.

## Production acceptance gate

Repository source is not equivalent to a certified live WordPress deployment. Before classifying Deal Intake 2.1.0 as production-ready, verify on staging or production-equivalent infrastructure:

1. `/submit-a-property/` and `/sell-your-property/` visibly render the canonical form with no placeholder or raw shortcode.
2. Public nonce, minimum-time, rate-limit, consent, and Turnstile controls behave correctly.
3. Valid supporting files are accepted; disallowed MIME types and oversize files are rejected.
4. Uploaded files are not directly reachable from the web server.
5. The PDF opens successfully and matches the submitted record.
6. The PDF appears in the Media Library when enabled and direct attachment URLs remain protected.
7. Authorized and unauthorized private-PDF download tests behave correctly.
8. The archive PDF email is delivered to the configured business mailbox and the attachment is readable.
9. Platform Mail Gateway / SMTP delivery and mail logging are verified for the deployed mail transport.
10. Duplicate review, CSV export, capabilities, and audit events are tested.
11. Acceptance creates exactly one canonical Pipeline CRM deal ID and remains idempotent.
12. Existing seller/property/consent records survive upgrade and uninstall behavior remains conservative.
