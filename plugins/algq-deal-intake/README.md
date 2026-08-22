# Algonquian Deal Intake

**Version:** 2.0.0  
**Status:** Production candidate  
**Author:** Onegodian | Algonquian Real Estate Technology Division

Algonquian Deal Intake is the authoritative entry point for seller leads, property-owner reviews, and property submissions entering the Algonquian Real Estate Platform. It records seller and property information, versioned consent evidence, lead source, lead score, duplicate review, and a controlled handoff request to Pipeline CRM.

## Authority boundary

Deal Intake owns intake submissions. It does **not** own pipeline stages, tasks, underwriting, offers, documents, funding, or closing status. After an intake is accepted, Pipeline CRM must return the canonical deal ID.

## Requirements

- WordPress 6.8+
- PHP 8.2+
- Algonquian Real Estate Platform Plugin
- Pipeline CRM for canonical deal creation after acceptance

## Core shortcodes

```text
[algq_deal_intake_form]
[algq_property_submission]
[deal_intake_form_public]
[deal_intake_form_internal]
[deal_quick_capture]
[algq_homeowner_options]
[algq_property_review]
[algq_seller_portal]
```

Preferred public interfaces are `[algq_deal_intake_form]`, `[algq_property_submission]`, `[algq_homeowner_options]`, `[algq_property_review]`, and `[algq_seller_portal]`. The `deal_*` names remain compatibility interfaces.

WPBakery placement:

```text
[vc_column_text]
[algq_property_review]
[/vc_column_text]
```

## Property-owner decision path

```text
What Are My Options?
→ Request a Property Review
→ Submit available property information
→ Review / qualification
→ Appropriate next workflow
```

`[algq_property_review]` renders the Property Owners / Property Review interface, explains what is reviewed, preserves the no-commitment boundary, and includes the secure public intake form. A property review is informational intake and is not a certified inspection, appraisal, legal or tax opinion, brokerage engagement, or commitment to purchase.

## About Plugin pages

The plugin includes a WordPress administrator About Plugin interface and generated public plugin documentation pages containing version, authorship, authority-boundary, security, shortcode, dependency, and integration-health information.

## Generated pages

The activation routine creates missing pages only and never overwrites administrator-edited content:

- `/submit-property/`
- `/sell-your-property/`
- `/homeowner-options/`
- `/request-property-review/`
- `/seller-portal/`
- `/property-submission-received/`
- `/plugin/deal-intake/`
- `/plugin/deal-intake/start/`
- `/plugin/deal-intake/docs/`

## Security controls

- Dedicated capabilities instead of blanket `manage_options`
- Nonce verification for state-changing browser requests
- Server-side validation and sanitization
- Public honeypot and minimum-form-time checks
- Configurable per-origin hourly rate limiting
- Versioned consent, privacy, and terms evidence
- Prepared SQL for variable database queries
- CSV formula-injection protection
- Append-only integration hooks for the shared audit service
- No public REST endpoint for anonymous submission creation
- No seller records exposed without record-level authorization

## Pipeline CRM contract

Deal Intake attempts:

```php
algq_pipeline_create_deal( $payload );
```

It also applies:

```php
apply_filters( 'algq_pipeline_create_deal', $deal_id, $payload, $submission_id );
```

Pipeline CRM must return a stable integer deal ID. If it does not, the intake remains in `awaiting_pipeline` rather than creating a second authoritative deal record.

## Production gate

Repository source changes are not equivalent to live-site certification. Deployment still requires WordPress integration testing, page/shortcode rendering verification, database migration testing, capability testing, duplicate-resolution testing, Platform Mail Gateway testing, and an authenticated end-to-end handoff to the deployed Pipeline CRM.
