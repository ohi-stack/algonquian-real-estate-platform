# Property Legacy Planning™

Property Legacy Planning™ is a homeowner guidance and property-transition service for people asking:

- What will happen to my house after I am gone?
- I do not have children.
- I do not know who will take care of this place.

Algonquian Real Estate helps homeowners discuss future property options, organize relevant information, coordinate with the homeowner's attorney or other licensed professionals when requested, compare practical transition paths, and create a written Property Transition Plan.

## Professional boundaries

The service is property-focused and does not provide legal, tax, investment, fiduciary, probate, guardianship, conservatorship, or estate-planning advice. It does not draft wills, trusts, deeds, powers of attorney, or other legal instruments.

## Public routes

- `/property-legacy-planning/`
- `/property-legacy-planning/consultation/`
- `/property-legacy-planning/questionnaire/`
- `/property-legacy-planning/transition-plan/`
- `/property-legacy-planning/resources/`
- `/property-legacy-planning/faq/`

The main page is public. Questionnaire and transition-plan workflows should be protected by appropriate roles and capabilities once implemented.

## Platform shortcodes

- `[algq_property_legacy_planning]`
- `[algq_property_legacy_consultation]`
- `[algq_property_legacy_questionnaire]`
- `[algq_property_transition_plan]`
- `[algq_property_legacy_resources]`
- `[algq_property_legacy_faq]`

## Required data and workflow functions

A production module should support:

1. Homeowner profile and preferred contact information.
2. Property profile and occupancy status.
3. Guided legacy-conversation questionnaire.
4. Homeowner-stated goals and concerns.
5. Option comparison: retain, rent, transfer, donate, prepare for sale, sell traditionally, or sell directly.
6. Trusted-contact records.
7. Attorney and professional-advisor coordination records.
8. Property-document checklist.
9. Maintenance and preservation priorities.
10. Written Property Transition Plan generation.
11. Versioning, approvals, secure storage, and PDF generation.
12. Follow-up tasks and reminder dates.
13. Consent, authorization, disclaimer, and audit history.
14. Referral tracking without presenting ARE as the legal or financial decision-maker.

## Recommended tables

```text
wp_algq_legacy_clients
wp_algq_legacy_properties
wp_algq_legacy_questionnaires
wp_algq_legacy_goals
wp_algq_legacy_options
wp_algq_legacy_contacts
wp_algq_legacy_advisors
wp_algq_transition_plans
wp_algq_transition_plan_versions
wp_algq_legacy_consents
wp_algq_legacy_activity
```

## Recommended capabilities

```text
view_algq_legacy_clients
manage_algq_legacy_clients
conduct_algq_legacy_conversations
manage_algq_transition_plans
approve_algq_transition_plans
view_algq_legacy_documents
manage_algq_legacy_documents
coordinate_algq_legacy_advisors
view_algq_legacy_audit
```

## Integrations

- Pipeline CRM for client and property relationship tracking.
- Document Library for records, checklists, and plan versions.
- PDF & Signature Engine for approved plan exports and acknowledgments.
- Automation Engine for consultation confirmations, reminders, review dates, and professional-coordination tasks.
- Platform Mail Gateway for all email delivery.
- Admin Command Center for pending consultations, plans awaiting review, follow-ups, and service metrics.

## WPBakery standard

All generated content must use valid WPBakery shortcode pairs:

```text
[vc_column_text]
Content
[/vc_column_text]
```

Never use an HTML-style `</vc_column_text>` closing tag.
