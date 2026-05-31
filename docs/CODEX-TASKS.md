# Codex Tasks — Algonquian Real Estate Platform v1.0

Use these tasks as implementation prompts for Codex. Each task should be completed on its own branch and submitted by pull request.

## Task 1 — Build WordPress Plugin Bootstrap

Create the production plugin bootstrap under `plugin/`.

Deliverables:
- `plugin/algonquian-real-estate-platform.php`
- plugin constants for version, path, URL, database version
- activation and deactivation hooks
- autoload or safe manual includes
- admin menu registration
- public shortcode registration

Acceptance Criteria:
- Plugin activates without fatal errors.
- WordPress admin menu appears as `Algonquian RE`.
- Version constant is `1.0.0`.

## Task 2 — Build Database Installer

Create the database installer for Version 1.0 tables.

Deliverables:
- deals table
- buyers table
- deal notes table
- activity log table
- settings option for database version

Acceptance Criteria:
- Tables are created on activation using `dbDelta`.
- Table names use the WordPress table prefix.
- Installer is idempotent and safe to rerun.

## Task 3 — Seller Intake Module

Build the seller intake public form and handler.

Deliverables:
- shortcode `[algq_seller_intake]`
- nonce validation
- sanitized fields
- deal ID generation
- admin notification email
- confirmation message

Acceptance Criteria:
- Form submission creates a CRM deal record.
- New deals default to `Lead Captured`.
- Submission does not expose raw database errors.

## Task 4 — Deal CRM Admin Module

Build the internal deal management screen.

Deliverables:
- admin deals list
- deal detail view
- status update controls
- notes field
- activity log entries

Acceptance Criteria:
- Admin can view, search, and update deals.
- Status changes are logged.
- Output is escaped and permissions are enforced.

## Task 5 — MAO Calculator Module

Build the MAO calculator.

Deliverables:
- shortcode `[algq_mao_calculator]`
- admin and/or public calculator UI
- ARV, repairs, holding costs, closing costs, profit inputs
- MAO output
- optional save-to-deal capability

Formula:
`MAO = (ARV * 0.70) - repairs - holding_costs - closing_costs - desired_profit`

Acceptance Criteria:
- Calculator works client-side and validates server-side if saved.
- Saved underwriting updates the deal record.

## Task 6 — Buyer Registration Module

Build buyer registration and buyer database workflow.

Deliverables:
- shortcode `[algq_buyer_registration]`
- buyer profile fields
- buyer status: pending, approved, rejected
- admin buyer list

Acceptance Criteria:
- Registration creates a buyer record.
- Admin can approve or reject buyers.
- Fields are sanitized and escaped.

## Task 7 — Admin Dashboard

Build the Version 1.0 command dashboard.

Deliverables:
- KPI cards
- lead count
- buyer count
- pipeline count by status
- recent deals table
- recent activity table

Acceptance Criteria:
- Dashboard loads under WordPress admin.
- KPIs query live plugin tables.
- UI uses Algonquian navy, gold, white, and institutional styling.

## Task 8 — Branding Integration

Add Algonquian branding assets and design tokens.

Deliverables:
- `branding/brand-guidelines.md`
- CSS variables for navy, gold, white, charcoal
- dashboard header branding
- plugin card styling

Acceptance Criteria:
- Admin dashboard and public forms share a consistent brand system.
- CSS is scoped to avoid breaking the WordPress admin.

## Task 9 — Security Hardening

Review the plugin for WordPress security standards.

Deliverables:
- nonce checks
- capability checks
- sanitization
- escaping
- prepared SQL statements
- upload restrictions if file upload is enabled

Acceptance Criteria:
- No direct access to PHP files.
- No unsafe SQL interpolation.
- Public forms cannot modify admin-only data.

## Task 10 — Release Packaging

Create release packaging for installable ZIP.

Deliverables:
- build script or documented ZIP process
- `CHANGELOG.md`
- install instructions
- version bump checklist

Acceptance Criteria:
- ZIP installs through WordPress plugin upload.
- Plugin activates cleanly on a staging WordPress site.
