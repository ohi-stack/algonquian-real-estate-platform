# Algonquian Deal Marketplace

**Version:** 2.0.0 Production  
**Author:** Onegodian | Algonquian Real Estate  
**Platform:** Algonquian Real Estate Technology Division

## Purpose

Algonquian Deal Marketplace is the controlled buyer-distribution layer of the Algonquian Real Estate Platform. It publishes curated opportunity summaries while enforcing buyer capability checks, deal-level authorization, current NDA acceptance, controlled package delivery, and validated buyer-offer submission.

## Version 2.0.0 production upgrade

- Adds the complete Marketplace source package to the canonical repository.
- Stops creating or controlling the canonical `/buyer-dashboard/` page.
- Uses `/marketplace/` plus a Marketplace child inside an existing Buyer Dashboard when available.
- Adds buyer capabilities additively so Buyer Portal and Marketplace permissions coexist.
- Stores versioned NDA acceptance evidence in an append-only Marketplace table.
- Verifies access to the exact deal before NDA, package, offer, or REST actions.
- Replaces raw package URLs with attachment IDs and an authorized download controller.
- Adds explicit access grants and shared Stripe-entitlement event hooks.
- Removes the user-editable deal ID from the buyer offer workflow.
- Adds rate limiting, status workflows, audit events, notifications, REST routes, health checks, and conservative uninstall behavior.
- Preserves legacy shortcodes for backward compatibility.

## Shortcodes

- `[algq_deal_marketplace]`
- `[algq_buyer_marketplace_dashboard]`
- `[algq_buyer_nda_gate]`
- `[algq_buyer_offer_form]`
- `[algq_deal_marketplace_plugin_card]`

## Canonical routes

- `/marketplace/`
- `/buyer-dashboard/marketplace/` when Buyer Portal owns `/buyer-dashboard/`
- `/buyer-dashboard/nda/`
- `/buyer-dashboard/submit-offer/`
- `/plugin/deal-marketplace/`
- `/plugin/deal-marketplace/start/`
- `/plugin/deal-marketplace/docs/`

If Buyer Portal has not created `/buyer-dashboard/`, the plugin creates isolated fallback routes prefixed with `/buyer-marketplace/` rather than claiming the Buyer Portal route.

## Shared buyer role

The Platform Plugin should remain the authoritative owner of the `algq_buyer` role. Marketplace activation performs an additive migration so the role includes both Buyer Portal and Marketplace capabilities. It never deletes the shared role.

## Package security

Deal records store a package attachment ID, not a public URL. Downloads are served through a nonce-protected, record-authorized controller. Production deployment should connect `algq_dm_package_file_path` to the Platform private-file service or Document Library. A normal public Media Library file is considered degraded storage because its original URL may remain directly reachable.

## Stripe integration boundary

Marketplace does not implement a second payment stack. It listens for shared Platform events:

- `algq_stripe_entitlement_created`
- `algq_stripe_entitlement_revoked`

The Platform Plugin remains responsible for Stripe authentication, checkout, subscriptions, webhook verification, refunds, and customer records.

## REST routes

- `GET /wp-json/algq/v1/marketplace/deals`
- `GET /wp-json/algq/v1/marketplace/deals/{id}`
- `POST /wp-json/algq/v1/marketplace/offers`

All routes require authenticated Marketplace permissions and enforce deal-level authorization.

## Upgrade notes from 1.x

- Existing `algq_dm_nda_accepted=yes` user metadata is migrated to a `legacy-1.0` acceptance record.
- Existing Marketplace posts remain compatible.
- Raw `_algq_dm_download_url` values are no longer rendered. Assign a controlled package attachment ID before release.
- Existing shortcodes remain registered.
- The old top-level `/buyer-dashboard/` page is not deleted or overwritten.

## Documentation

See `docs/PRODUCTION-ACCEPTANCE.md` for the release gate and end-to-end validation sequence.
