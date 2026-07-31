# Algonquian Deal Intake

**Version:** 2.0.0  
**Status:** Production candidate  
**Author:** Onegodian | Algonquian Real Estate Technology Division

Algonquian Deal Intake is the authoritative entry point for seller leads and property submissions entering the Algonquian Real Estate Platform. It records seller and property information, versioned consent evidence, lead source, lead score, duplicate review, and a controlled handoff request to Pipeline CRM.

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
[deal_intake_form_public]
[deal_intake_form_internal]
[deal_quick_capture]
[algq_homeowner_options]
[algq_seller_portal]
```

WPBakery placement:

```text
[vc_column_text]
[algq_deal_intake_form]
[/vc_column_text]
```

## Generated pages

The activation routine creates missing pages only and never overwrites administrator-edited content:

- `/submit-property/`
- `/sell-your-property/`
- `/homeowner-options/`
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

Deal Intake attempts the function below when available:

```php
algq_pipeline_create_deal( $payload );
```

It also applies this compatibility filter:

```php
apply_filters( 'algq_pipeline_create_deal', $deal_id, $payload, $submission_id );
```

Pipeline CRM must return a stable integer deal ID. If it does not, the intake remains in `awaiting_pipeline` rather than creating a second authoritative deal record.

## Production gate

This source has been PHP-linted, but deployment still requires WordPress integration testing, database migration testing, capability testing, duplicate-resolution testing, Platform Mail Gateway testing, and an authenticated end-to-end handoff to the deployed Pipeline CRM.
