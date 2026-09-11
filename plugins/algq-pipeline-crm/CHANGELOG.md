# Changelog

## 2.2.1 — Platform 3.1 service-compatibility release

- Promoted the 2.2 relationship architecture onto the current release line.
- Registered Pipeline CRM as an authoritative object-backed provider through the Platform 3.1 service registry.
- Standardized plugin ownership metadata to Algonquian Real Estate, LLC.
- Preserved Pipeline CRM as the canonical Deal owner while exposing controlled deal, task, activity, contact, organization and relationship operations to companion systems.
- Retained schema version 2.2.0; this release changes integration/runtime compatibility rather than the CRM table schema.

## 2.2.0 — Shared ARE relationship CRM foundation

- Added shared CRM contacts and organizations without changing Pipeline CRM's canonical Deal authority.
- Added relationship types for sellers, owners, buyers, capital sources, lenders, equity/JV partners, professionals, vendors, referral sources and stewardship clients.
- Added owner, status, priority, source, relationship-strength, tag, last-activity and next-action fields.
- Added controlled links to canonical Deals and authoritative external plugin records.
- Added relationship activity history and non-Deal follow-up tasks.
- Prevented relationship tasks from duplicating canonical Deal tasks.
- Added idempotent source identities for companion-plugin integrations.
- Added CRM service functions and integration hooks.
- Added repository-wide and plugin-level CRM authority documentation.
- Raised the declared WordPress/PHP minimums to the current platform standard: WordPress 6.8 and PHP 8.2.

## 2.0.0 — Production architecture upgrade

- Replaced custom-post-type deal storage with versioned canonical tables.
- Added UUIDs, controlled deal numbers, source identity, assignments, lifecycle status, closing fields, and record versions.
- Added granular capabilities and an Acquisition Manager role.
- Added controlled transitions, prerequisite validation, loss-reason enforcement, and closing-data enforcement.
- Added optimistic locking for concurrent updates and Kanban movement.
- Added stage history and activity records.
- Added legacy 1.0 custom-post-type migration without deleting the legacy records.
- Standardized REST routes under `algq/v1`.
- Added canonical service functions and cross-plugin hooks.
- Added shared audit-service integration with a degraded fallback event.
- Added responsive standardized admin and front-end UI.
- Added idempotent WPBakery page generation with valid closing shortcodes.
- Added conservative uninstall cleanup.

## 1.0.0

- Initial production MVP using custom post types, basic Kanban movement, dashboard widgets, settings, and activity logging.
