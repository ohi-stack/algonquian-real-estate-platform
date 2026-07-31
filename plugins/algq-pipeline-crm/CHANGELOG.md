# Changelog

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
