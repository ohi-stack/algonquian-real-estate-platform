# Algonquian Real Estate Form & PDF Production Standard

**Status:** Canonical source standard  
**Applies to:** Public forms, protected operational forms, PDF generation, document delivery, and related email notifications across the Algonquian Real Estate plugin suite.

## Company operations mailbox

Operational notifications and generated-PDF copies default to:

`algonquianre@gmail.com`

The deployment may override this with the WordPress option `algq_company_notification_email` or the `algq_company_notification_email` filter.

The Platform Mail Gateway exposes `algq_send_mail()` as the shared operational-mail API. Messages sent through that API retain the intended recipient and, by default, also retain an Algonquian Real Estate operations copy. Plugins can explicitly set `company_copy=false` only for messages that must exclusively go to an external recipient.

## Form production requirements

Every form that creates, changes, transmits, approves, or requests an operational record must have the controls applicable to its trust level.

### Public / unauthenticated forms

Required controls:

- WordPress nonce validation.
- Server-side required-field validation.
- Sanitization and normalization before persistence.
- Output escaping on redisplay.
- Honeypot or equivalent automated-submission control.
- Rate limiting.
- Minimum-submit-time protection where practical.
- Versioned consent / terms / privacy evidence where the form establishes contact permission, confidentiality acceptance, account creation, or document-access consent.
- Privacy-preserving IP and user-agent evidence when evidence is operationally required.
- Durable persistence before success is shown.
- Audit event on successful creation and material failure.
- Safe redirects restricted to approved site locations.
- No sensitive field values in URLs.

### Authenticated operational forms

Required controls:

- Authentication.
- Capability authorization.
- Record-level authorization when a form acts on a deal, document, buyer, offer, funding record, or other protected object.
- WordPress nonce validation.
- Server-side validation and allowlists.
- Audit evidence for material state changes.
- Idempotency or throttling where duplicate submissions create financial, legal, or workflow risk.

## Current form matrix

### Algonquian Deal Intake

The production intake workflow includes nonce verification, honeypot handling, minimum-submit-time checks, hourly rate limiting, server-side required-field validation, versioned consent/privacy/terms evidence, duplicate review, durable persistence, audit events, and notification delivery.

Primary form interfaces:

- `[algq_deal_intake_form]`
- `[algq_property_submission]`
- `[deal_intake_form_public]`
- `[deal_intake_form_internal]`
- `[deal_quick_capture]`

Production migration behavior moves the historical empty/default-WordPress-admin notification setting to the verified company operations mailbox while preserving an intentionally customized intake mailbox. Shared Platform mail delivery also retains the company copy.

### Algonquian Buyer Portal

Buyer registration is protected by the Buyer Portal production guard in `class-algq-buyer-portal-production.php`.

Controls include:

- Nonce verification before account creation.
- Honeypot field.
- Minimum-submit-time validation.
- Per-email/IP hourly rate limiting.
- Required name/email/terms validation.
- Versioned consent, terms, and privacy evidence.
- Privacy-preserving request hashes.
- Buyer qualification fields for acquisition strategy, purchase range, and proof-of-funds / financing status.
- Company notification after account creation.
- Audit event after account creation.
- Canonical `/buyer-login/` post-registration routing.

Buyer interest, NDA acceptance, and protected downloads additionally require authenticated buyer access, record-level deal authorization, nonce verification, and current NDA acceptance.

### Algonquian Deal Marketplace

Marketplace offer submission requires authenticated capability access, deal-level authorization, current NDA acceptance when required, nonce verification, amount validation, financing-type allowlisting, terms-length limits, rate limiting, durable storage, audit evidence, and a company notification.

### Algonquian Document Library

Document-access requests use nonce verification, a honeypot, required-field validation, package allowlisting, versioned consent, privacy hashes, rate limiting, durable persistence, audit evidence, and review before entitlement is granted. Its operational notification uses the shared Platform mail API and therefore retains the company operations copy.

### MAO Engine / Offer Generator / PDF & Signature Engine / Command Center

These operational forms are protected interfaces. They must remain inaccessible to unauthorized public users and require their documented capabilities and WordPress nonces before state-changing actions are accepted.

Offer Generator PDF requests are bridged into the authoritative PDF & Signature Engine. The bridge returns a protected PDF record rather than HTML masquerading as a PDF, so generated offers receive the same hashing, Media Library registration, protected download, and company-copy treatment.

### Digital Store / WooCommerce Bridge

Checkout, billing, and order forms remain authoritative in WooCommerce. Algonquian plugins must consume server-confirmed WooCommerce order/payment state and must not trust browser-submitted price or entitlement values as authoritative.

## PDF generation and archival policy

The PDF & Signature Engine remains the authority for generated transaction PDFs.

### Authoritative copy

Every generated PDF remains in the engine's protected storage directory:

`wp-content/uploads/algq-private/pdf-signature/`

The directory is protected from direct web access by server rules created by the engine. The database record stores the version, file name, SHA-256 hash, source linkage, deal linkage, status, and audit evidence.

### Media Library registration

Generated PDFs are registered in the WordPress Media Library by default through `ALGQ_PDF_Delivery`.

Important security rule:

- The Media Library record references the existing protected file.
- The file is **not copied** into an ordinary public uploads location.
- `_wp_attached_file` is linked to the protected file so WordPress Media Library can manage the attachment record correctly.
- The attachment is marked as an Algonquian protected attachment.
- Attachment URLs are replaced with the PDF Engine's nonce-protected download controller.
- Anonymous users receive no direct attachment URL.
- Deleting the Media Library entry is blocked so Media Library operations cannot delete the authoritative PDF file.

Media Library registration can be disabled with:

`algq_pdf_register_media_library = no`

or through the `algq_pdf_register_media_library` filter.

### Company email delivery

A generated PDF is emailed as an attachment to the company operations mailbox by default.

Default recipient:

`algonquianre@gmail.com`

Email delivery can be disabled with:

`algq_pdf_company_email_enabled = no`

or through the `algq_pdf_company_email_enabled` filter.

The default attachment limit is 15 MB and may be changed with `algq_pdf_company_email_max_bytes`. A PDF above the limit remains safely archived in protected storage and Media Library even if email attachment delivery is skipped.

Email delivery failure does not delete or invalidate the authoritative protected PDF. A delivery audit event records success, failure, or size-based skip.

## Production deployment gates

Canonical source is not the same as a production-certified installation. Before classifying the live WordPress site as production-ready, run at least these acceptance checks:

1. Submit a fresh public Deal Intake form and confirm persistence, consent evidence, duplicate evaluation, notification email, and thank-you redirect.
2. Submit invalid, bot-like, and rate-limited Deal Intake requests and confirm they fail safely.
3. Create a fresh buyer account and confirm the Buyer Portal production guard, consent evidence, qualification metadata, notification email, canonical login redirect, authorized deal access, NDA acceptance, interest submission, and protected download.
4. Submit a Marketplace offer from an authorized buyer and confirm an unauthorized buyer cannot submit the same offer.
5. Submit a Document Library access request and confirm no document entitlement is granted before review.
6. Generate a PDF directly and through Offer Generator and confirm:
   - database record created;
   - SHA-256 integrity check passes;
   - protected file exists;
   - protected Media Library attachment exists and references the protected file;
   - direct uploads URL is not usable for anonymous access;
   - authorized PDF download works;
   - company email receives the PDF attachment when within the configured size limit;
   - mail success/failure is visible in platform mail/audit evidence.
7. Run the repository WordPress plugin release workflow and require all PHP/static validation gates to pass.
8. Repeat the key workflows on the actual production hosting stack after deployment.

No plugin or live workflow should be described as production-certified until these deployment gates are recorded as passed.
