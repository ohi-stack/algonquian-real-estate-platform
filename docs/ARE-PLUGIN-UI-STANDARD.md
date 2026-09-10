# Algonquian Real Estate Plugin UI Standard

## Purpose
This document establishes the shared UI standard for every Algonquian Real Estate WordPress plugin. Plugin admin screens must present one coherent ARE operating platform rather than disconnected plugin interfaces.

## Scope
The standard applies to every Algonquian/ARE-owned plugin in the canonical repository, including new plugins added after this document. Shared presentation assets are owned by `algq-core` and loaded only on ARE/Algonquian WordPress admin screens.

## Visual Identity
Use the Algonquian Real Estate deep navy, blue, institutional gold, teal, white, and neutral interface system.

### Canonical Design Tokens
- Deep Navy: `#071422`
- Navy Surface: `#0b1f33`
- Primary Blue: `#0b3d91`
- ARE Teal: `#167c80`
- Institutional Gold: `#d1a54a`
- Gold Highlight: `#e8c86d`
- Surface White: `#ffffff`
- Soft Background: `#f4f6f8`
- Muted Text: `#65737f`
- Border: `#dce3e8`

## Required Screen Structure
Every substantive plugin admin screen should include, where applicable:
1. Branded hero/header.
2. Executive KPI/status cards.
3. Primary action controls.
4. Operational workspace or settings panels.
5. Documentation/help access.
6. Dependency/system-health status.
7. Consistent footer/status area.

## Shared Components
Use these shared classes instead of introducing plugin-specific visual systems:
- `.algq-admin-shell`
- `.algq-dashboard`
- `.algq-hero`
- `.algq-eyebrow`
- `.algq-btn`, `.algq-btn--gold`, `.algq-btn--ghost`
- `.algq-kpi-grid`, `.algq-kpi-card`, `.algq-kpi-label`, `.algq-kpi-value`
- `.algq-panel`, `.algq-grid`
- `.algq-tabs`, `.algq-tab`, `.algq-tab-panel`
- `.algq-status-pill`, `.algq-status-pass`, `.algq-status-warning`, `.algq-status-fail`
- `.algq-table`
- `.algq-form-grid`, `.algq-field`
- `.algq-plugin-card`
- `.algq-admin-footer`

## Motion Standard
Motion should communicate hierarchy and state, not distract from administrative work.

Permitted shared effects:
- Short fade/translate entrance on dashboard shells, cards, and panels.
- Staggered KPI-card entrance.
- Subtle card lift and shadow on hover.
- Gold/teal ambient hero motion.
- Smooth tab and notice transitions.
- Status emphasis that remains understandable without animation.

Do not use continuous bouncing, flashing, rapid pulsing, autoplay audio, or motion that interferes with forms/tables. All animation must respect `prefers-reduced-motion: reduce`.

## Widgets and Dashboard Cards
Widgets should use the same card geometry, typography, border treatment, status vocabulary, spacing, and interaction model. Cards should contain a label, current value or operational state, trend/status where relevant, and optional source note. Charts may use ARE blue, teal, gold, navy, and accessible neutral tones.

## Dark Mode
Every plugin dashboard should support `.algq-dark` on the root wrapper. The shared UI script persists the administrator's preference locally. Contrast and status meaning must remain accessible.

## Tables
Use the branded table pattern for deals, buyers, lenders, documents, audit logs, reports, orders, subscriptions, funding records, and operational queues. Dense operational tables must remain horizontally usable on smaller screens.

## Forms
All forms must retain production security controls independent of presentation: nonces, capability checks, server-side validation/sanitization, escaped output, explicit success/error notices, and record-level authorization where applicable.

## Shared JavaScript
The shared admin UI provides:
- Motion initialization.
- Tab navigation.
- Dismissible notices.
- Dark-mode toggle and persistence.
- Confirmation hooks through `data-algq-confirm`.

Plugin-specific JavaScript remains responsible for business logic. Shared UI JavaScript must never alter transaction authority, validation, permissions, or stored operational data.

## Accessibility
- Keyboard-reachable controls.
- Visible focus states.
- Semantic tab labels where practical.
- Color is never the only status indicator.
- Responsive tables and forms.
- Reduced-motion support.
- Readable light/dark contrast.

## Production Rule
No ARE plugin should ship with placeholder UI, broken routing, an isolated color system, inconsistent widget styling, unescaped output, missing settings pages, nonfunctional panels, or motion that compromises accessibility or administrative efficiency.
