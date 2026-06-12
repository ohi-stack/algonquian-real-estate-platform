# Algonquian Real Estate Plugin UI Standard

## Purpose
This document establishes the shared UI standard for every Algonquian Real Estate WordPress plugin. The platform is now in production-hardening, so plugin screens must use a consistent executive-grade interface instead of disconnected MVP screens.

## Applies To
- algq-command-center
- algq-automation-engine
- algq-buyer-portal
- algq-deal-intake
- algq-deal-marketplace
- algq-digital-products
- algq-digital-store
- algq-document-library
- algq-funding-tracker
- algq-mao-engine
- algq-offer-generator
- algq-pdf-signature
- algq-pipeline-crm
- algq-platform
- algq-woocommerce-bridge

## Visual Identity
Use the Algonquian Real Estate blue, institutional gold, white, charcoal, and deep navy interface system.

### Design Tokens
- Primary Blue: `#0b3d91`
- Institutional Gold: `#d4af37`
- Charcoal: `#101820`
- Deep Navy: `#0f172a`
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
Each plugin should use or reproduce these classes:

- `.algq-admin-shell`
- `.algq-dashboard`
- `.algq-hero`
- `.algq-eyebrow`
- `.algq-btn`
- `.algq-btn--gold`
- `.algq-btn--ghost`
- `.algq-kpi-grid`
- `.algq-kpi-card`
- `.algq-panel`
- `.algq-grid`
- `.algq-tabs`
- `.algq-tab`
- `.algq-tab-panel`
- `.algq-status-pill`
- `.algq-status-pass`
- `.algq-status-warning`
- `.algq-status-fail`
- `.algq-table`
- `.algq-form-grid`
- `.algq-field`
- `.algq-plugin-card`

## Dark Mode
Every plugin dashboard should support `.algq-dark` on the root wrapper. Dark mode must preserve contrast, readability, and institutional styling.

## Dashboard Cards
Cards should show:

- Label
- Current value
- Status or trend
- Optional source note

## Plugin Catalog Cards
Every plugin card should include:

- Plugin name
- Version
- Author line: `By Onegodian | Algonquian Real Estate`
- Short description
- Status
- Buttons: View Details, Getting Started, Documentation

## Tables
Use a branded table pattern for:

- Deals
- Buyers
- Lenders
- Documents
- Audit logs
- Reports
- Orders
- Subscriptions

## Forms
All forms must use:

- Nonces
- Capability checks
- Sanitized input
- Escaped output
- Clear submit buttons
- Success/error notices

## JavaScript Requirements
Shared UI JavaScript should provide:

- Dark mode toggle
- Tab navigation
- Dismissible notices
- Drag-and-drop dashboard cards where appropriate
- Layout persistence
- Export action confirmation

## Accessibility
- Buttons must be keyboard reachable.
- Tabs must use proper labels where practical.
- Color must not be the only status indicator.
- Text contrast must remain readable in light and dark mode.

## Production-Hardening Rule
No plugin should ship with placeholder UI, broken routing, inconsistent styling, unescaped output, missing settings pages, or nonfunctional dashboard panels.
