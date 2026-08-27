# Algonquian Real Estate Plugin UI Standard

## Purpose
This document establishes the shared UI standard for every Algonquian Real Estate WordPress plugin. Plugin screens must present one consistent executive-grade ARE interface rather than disconnected product-specific themes.

## Applies To
All ARE-owned plugins and modules, including Platform/Core, Command Center, Automation Engine, Buyer Portal, Deal Intake, Deal Marketplace, Digital Products, Digital Store, Document Library, Funding Tracker, MAO Engine, Offer Generator, PDF & Signature, Pipeline CRM, Property Stewardship, WooCommerce Bridge, Navigation, Education Center, and future ARE plugins.

## Canonical shared assets
- `shared-ui/are-admin-ui.css`
- `shared-ui/are-admin-ui.js`

Plugin-specific styles may extend these assets for domain controls, but may not replace the ARE palette or create a competing admin design system.

## Visual Identity
Use the Algonquian Real Estate blue, institutional gold, white, charcoal, deep navy, and restrained teal accent system.

### Design Tokens
- Primary Blue: `#0b3d91`
- Institutional Gold: `#d4af37`
- Charcoal: `#101820`
- Deep Navy: `#0f172a`
- Teal Accent: `#167c80`
- Surface White: `#ffffff`
- Soft Background: `#f4f6f8`
- Muted Text: `#64748b`

## Required Screen Structure
Every plugin admin screen should include:
1. Branded hero/header
2. Executive summary cards or status cards
3. Primary action buttons
4. Settings or configuration panel where applicable
5. Documentation/help link
6. System status or dependency card
7. Consistent footer/status area

## Required Admin UI Components
Use these shared classes wherever the component applies:
`.algq-admin-shell`, `.algq-dashboard`, `.algq-hero`, `.algq-eyebrow`, `.algq-btn`, `.algq-btn--gold`, `.algq-btn--ghost`, `.algq-kpi-grid`, `.algq-kpi-card`, `.algq-panel`, `.algq-grid`, `.algq-tabs`, `.algq-tab`, `.algq-tab-panel`, `.algq-status-pill`, `.algq-status-pass`, `.algq-status-warning`, `.algq-status-fail`, `.algq-table`, `.algq-form-grid`, `.algq-field`, `.algq-plugin-card`.

## Motion and interaction
Motion must make state and hierarchy clearer, not distract from administrative work.

Approved motion includes:
- short card entrance transitions;
- subtle card/button hover elevation;
- restrained decorative hero motion;
- status-indicator pulse;
- tab and focus feedback;
- dismissible-notice transitions.

Do not use continuous motion on tables, forms, financial values, underwriting results, legal records, audit logs, or other data where animation could impair reading or imply a changing value.

Every animation must be disabled under `prefers-reduced-motion: reduce`. Keyboard focus and screen-reader semantics remain mandatory.

## Dark Mode
Every plugin dashboard should support `.algq-dark` on the root wrapper. Dark mode must preserve contrast, readability, status meaning, and institutional styling.

## Dashboard Cards
Cards should show label, current value, status/trend, and optional source note. Cards should use consistent borders, radius, spacing, and hover behavior from the shared stylesheet.

## Plugin Catalog Cards
Every plugin card should include plugin name, version, author line `Algonquian Real Estate, LLC`, short description, status, and appropriate View Details / Getting Started / Documentation actions.

## Tables
Use the branded table pattern for deals, buyers, lenders, documents, audit logs, reports, orders, subscriptions, and other operational records. Table animation must never obscure values.

## Forms
All forms must use nonces, capability checks, sanitized input, escaped output, clear submit buttons, success/error notices, and branded focus states.

## JavaScript Requirements
Shared UI JavaScript provides dark-mode persistence, accessible tab navigation, dismissible notices, and export confirmation. Domain-specific JavaScript may extend these behaviors without breaking accessibility.

## Accessibility
- Buttons and controls must be keyboard reachable.
- Tabs must expose selected state and panel relationships.
- Color must not be the only status indicator.
- Text contrast must remain readable in light and dark mode.
- Respect `prefers-reduced-motion`.

## Production-Hardening Rule
No plugin should ship with placeholder UI, broken routing, inconsistent styling, unescaped output, missing settings pages, nonfunctional dashboard panels, or a plugin-specific visual theme that conflicts with this standard.
