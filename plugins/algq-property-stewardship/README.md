# Algonquian Property Stewardship Services

**Version:** 1.0.0

Owner-authorized property observation, visit reporting, vendor coordination, maintenance tracking, and transition support for Algonquian Real Estate LLC.

## Security model

- Stewardship record post types are non-public and excluded from REST exposure.
- Administrative record editing is restricted to `manage_algq_stewardship`.
- Portal queries are scoped to `_algq_steward_owner_user_id = current user`.
- Visit queries require both the authorized client record and matching owner user ID.
- Visit files/photos are represented as protected Document Library identifiers; the plugin does not expose WordPress attachment URLs directly.
- Secure document links are provided only through the `algq_secure_document_url` integration filter.
- Generated pages are idempotent and use valid WPBakery closing syntax.

## Shortcodes

- `[algq_property_stewardship]`
- `[algq_stewardship_portal]`

## Service boundaries

The service is property coordination and stewardship. It does not represent legal, fiduciary, guardianship, caregiving, insurance-adjusting, licensed-inspection, or security authority.

## Production acceptance

Live production use still requires activation, capability, portal record-isolation, document-link authorization, and end-to-end client testing on the target WordPress installation.
