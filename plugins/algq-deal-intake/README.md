# Algonquian Deal Intake

**Version:** 2.1.0  
**Status:** Production candidate  
**Author:** Algonquian Real Estate, LLC  
**Technology Division:** https://algonquianrealestate.com/technology/  
**Plugin Page:** https://algonquianrealestate.com/algonquian-deal-intake/

Algonquian Deal Intake is the authoritative entry point for seller leads, property-owner reviews, and property submissions entering the Algonquian Real Estate Platform. It records seller and property information, versioned consent evidence, source information, lead score, duplicate review, protected submission artifacts, and a controlled handoff request to Pipeline CRM.

## Authority boundary

Deal Intake owns intake submissions and their submission-time evidence. It does **not** own pipeline stages, tasks, underwriting, offers, funding, or closing status. After an intake is accepted, Pipeline CRM must return the canonical deal ID. MAO Engine remains the underwriting authority, Offer Generator remains the offer/proposal authority, and the controlled document/signature plugins retain their own record authority.

## Requirements

- WordPress 6.8+
- PHP 8.2+
- Algonquian Real Estate Platform Plugin
- Pipeline CRM for canonical deal creation after acceptance
- HTTPS on the deployed site
- A production mail transport for reliable archive delivery
- A web-server deny rule for `/wp-content/uploads/algq-private/` when the server does not honor Apache `.htaccess` files

## Current page-facing interfaces

```text
[algq_deal_intake_form]
[algq_property_submission]
[deal_intake_form_public]
[deal_intake_form_internal]
[deal_quick_capture]
[algq_homeowner_options]
[algq_property_review]
[algq_seller_portal]
[algq_deal_intake_about]
```

During seller-funnel migration, `[algq_seller_intake_entry]` is maintained as a compatibility alias. New public pages should use the `algq_*` interfaces.

WPBakery placement:

```text
[vc_column_text]
[algq_property_review]
[/vc_column_text]
```

Do not use the malformed HTML-style closing token `</vc_column_text>`.

## Property-owner decision path

```text
What Are My Options?
→ Request a Property Review
→ Submit available property information
→ Review / qualification
→ Appropriate next workflow
```

`[algq_homeowner_options]` supports traditional sale, direct/as-is review, repair-before-sale, retaining the property, seller-financing discussion, Property Stewardship, inherited/transition property, and development/redevelopment review.

`[algq_property_review]` explains the review path, preserves the no-commitment boundary, and includes the secure public intake form. A property review is informational intake and is not a certified inspection, appraisal, legal or tax opinion, brokerage engagement, or commitment to purchase.

## Public seller funnel

Version 2.1.0 reconciles the public conversion routes without overwriting administrator-authored page copy:

- `/submit-a-property/`
- `/sell-your-property/`

The older generated `/submit-property/` route redirects to `/submit-a-property/`. Known placeholder text and the retired seller-intake shortcode are repaired in place; otherwise one canonical WPBakery form block is appended once.

## Submission workflow

```text
Public / Property Review / Internal / Quick Capture
        ↓
Nonce / capability checks
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
PDF intake record generated
        ↓
Private Media Library attachment registered
        ↓
Archive PDF email attempted
        ↓
Administrative review
        ↓
Accepted submission → exactly one Pipeline CRM canonical deal ID
```

## Supporting file uploads

Public, internal, and quick-capture forms support optional multipart uploads.

Default accepted formats:

- PDF
- JPEG
- PNG
- WEBP
- DOCX

Default limits:

- 8 files per submission
- 10 MB per file

The file-count limit, file-size limit, and MIME allowlist are filterable. Files are verified server-side with WordPress file-type inspection, renamed with UUID-based storage names, hashed with SHA-256, and stored under protected Deal Intake storage instead of an ordinary public media location.

Default protected storage root:

```text
wp-content/uploads/algq-private/deal-intake/
```

Deployments may replace that root with the `algq_di_private_storage_dir` filter.

## PDF submission archive

After the canonical database transaction commits, Deal Intake creates a PDF record containing the submission reference, seller/contact information, property information, asking price, timeline, source, lead score, duplicate status, condition/situation notes, motivation, supporting-file count, and a non-contractual intake notice.

By default:

- PDF creation is enabled.
- The PDF remains in protected Deal Intake storage.
- The PDF is registered in the WordPress Media Library as a private Deal Intake attachment.
- The PDF receives SHA-256 integrity metadata.
- Authorized users receive a capability-gated WordPress download URL instead of a direct public file URL.
- An archival copy is emailed to `algonquianre@gmail.com` unless configuration changes the destination.
- PDFs larger than the default 15 MB email-attachment ceiling remain archived and indexed but are not attached to the email, preventing mail-size failure from invalidating the authoritative archive.

Relevant options:

```text
algq_di_archive_email
algq_di_archive_pdf_enabled
algq_di_pdf_media_library_enabled
algq_di_max_upload_bytes
algq_di_max_upload_files
algq_di_max_email_attachment_bytes
```

The ordinary new-submission operational notification remains separate from the PDF archival email.

## Turnstile support

The existing honeypot, minimum-form-time check, and rate limiting remain active. Cloudflare Turnstile can be enabled for public forms.

`wp-config.php` constants:

```php
define( 'ALGQ_DI_TURNSTILE_SITE_KEY', '...' );
define( 'ALGQ_DI_TURNSTILE_SECRET_KEY', '...' );
```

The equivalent WordPress options or `algq_di_turnstile_site_key` / `algq_di_turnstile_secret_key` filters may also provide credentials. Turnstile is enforced only when a secret key is configured.

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

Apache deny rules are created in the protected storage root as defense in depth. Nginx and other servers must be configured separately to deny direct requests to `/wp-content/uploads/algq-private/`.

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
- Existing Pipeline CRM deal ID returned on repeat acceptance to prevent duplicate canonical deal creation

## Pipeline CRM contract

Deal Intake attempts:

```php
algq_pipeline_create_deal( $payload );
```

It also applies:

```php
apply_filters( 'algq_pipeline_create_deal', $deal_id, $payload, $submission_id );
```

Pipeline CRM must return a stable integer deal ID. If it does not, the intake remains in `awaiting_pipeline`. If a canonical deal ID is already associated with the intake, the existing ID is returned rather than creating a second deal.

## Production acceptance gate

Repository source is not equivalent to a certified live WordPress deployment. Before classifying Deal Intake 2.1.0 as live-production-certified, verify on staging or production-equivalent infrastructure:

1. `/submit-a-property/`, `/sell-your-property/`, `/request-property-review/`, homeowner options, internal intake, quick capture, seller portal, and About interfaces render correctly with no raw shortcode or placeholder text.
2. Desktop, tablet, and mobile layouts are usable and accessible.
3. Public nonce, minimum-time, rate-limit, consent, and Turnstile controls behave correctly.
4. Valid supporting files are accepted; disallowed MIME types and oversize files are rejected.
5. Uploaded files are not directly reachable from the web server.
6. The PDF opens successfully and matches the submitted record.
7. The PDF appears in the Media Library when enabled and attachment URLs remain protected.
8. Authorized and unauthorized private-PDF download tests behave correctly.
9. The archive PDF email is delivered to the configured business mailbox; oversize-PDF behavior is also tested.
10. Platform Mail Gateway / SMTP delivery and mail logging are verified.
11. Duplicate review, CSV export, capabilities, and audit events are tested.
12. Acceptance creates exactly one canonical Pipeline CRM deal and repeat acceptance remains idempotent.
13. Existing seller/property/consent records survive upgrade and uninstall behavior remains conservative.
