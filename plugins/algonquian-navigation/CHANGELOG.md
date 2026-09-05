# Changelog

## Unreleased — 2026-09-05

### Fixed

- Separated the desktop account links from the six primary navigation sections so Buyer Login and Client Portal no longer crowd Technology and Company.
- Anchored Buyer Login and Client Portal in an independent far-right desktop rail with a divider and reserved horizontal space.
- Suppressed Search, Contact, and Submit a Property inside the white-header plugin utility rail on desktop so the header remains consistent with the approved ARE layout.
- Added a constrained-desktop fallback that hides the account rail before allowing the six primary menu sections to overflow.
- Preserved the existing mobile/tablet drawer behavior unchanged.

## 0.2.0 — 2026-08-22

### Added

- Canonical source package for the Algonquian Navigation plugin.
- Approved six-section enterprise navigation: Property Owners, Acquisitions, Investors & Capital, Services, Technology, Company.
- Four-column responsive footer.
- Utility navigation for Search, Buyer Login, Client Portal, Contact, and Submit a Property.
- Accessible SVG utility icons without an external icon dependency.
- Responsive mobile/tablet drawer with first-tap visibility of all six primary sections.
- Independent accordion controls for each primary section.
- Escape-key handling, backdrop dismissal, mobile focus containment, and body scroll lock.
- Desktop/laptop utility-label collapse to prevent header overflow.
- WordPress nav locations and extension filters for route/schema customization.
- `[algq_mega_menu]` and `[algq_footer_links]` shortcodes plus `algq_render_mega_menu()` theme integration.

### Changed

- Mobile navigation no longer requires an intermediate generic Menu action before the primary navigation is visible.
- Tablet behavior switches to the mobile drawer at 1024px and below to preserve readable primary labels and eliminate horizontal overflow.

## 0.1.0

- Production-site baseline version recorded for the previously installed Algonquian Navigation package. Canonical source for that package was not present in this repository.
