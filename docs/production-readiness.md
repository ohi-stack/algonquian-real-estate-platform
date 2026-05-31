# Production Readiness Plan

## Release Target

Release Candidate: 1.0.0-rc.1

Primary domain: AlgonquianRealEstate.com
Education subdomain: edu.AlgonquianRealEstate.com

## Production Objective

Prepare the Algonquian Real Estate Platform for live deployment as an acquisition, underwriting, buyer registration, document, education, and revenue system operated by Algonquian Real Estate LLC.

## Required Production Controls

Every plugin and platform module must include:

- WordPress plugin bootstrap
- Valid plugin header
- Activation hook
- Automatic page generation
- Shortcodes
- Admin menu
- Capability checks
- Nonces
- Input sanitization
- Output escaping
- README
- Documentation
- Branding assets
- Changelog
- Uninstall cleanup

## Core Pages To Generate

Public:

- /sell-your-property
- /contact
- /about
- /resources

System:

- /dashboard
- /plugins
- /deals
- /buyers
- /lenders
- /documents
- /underwriting
- /funding

Education:

- /courses
- /downloads
- /certifications
- /memberships
- /community
- /resources

## Priority Public Page: /sell-your-property

### Purpose

The /sell-your-property page is the primary seller acquisition page for AlgonquianRealEstate.com. It should convert motivated property owners into structured deal intake records for internal review.

### Recommended Page Title

Sell Your Property

### Recommended URL

/sell-your-property

### Required Shortcode

```text
[algq_seller_intake]
```

### WPBakery Implementation

Use:

```text
[vc_column_text]
[algq_seller_intake]
[/vc_column_text]
```

Never use:

```html
</vc_column_text>
```

### Recommended Page Sections

1. Hero statement for property owners.
2. Short explanation of flexible purchase options.
3. Seller intake form.
4. Privacy and no-obligation review notice.
5. Contact information for Algonquian Real Estate LLC.

### Suggested Public Copy

Algonquian Real Estate reviews residential property opportunities in Connecticut and surrounding markets. Property owners may submit information for review, including address, asking price, condition, and preferred contact method. Submission does not create a binding offer or obligation. A representative may follow up after internal review.

### Production Requirements

- Page must be generated on plugin activation if it does not already exist.
- Page must not duplicate if the slug already exists.
- Page content must use valid shortcode syntax.
- Form submissions must be nonce-protected.
- Seller inputs must be sanitized before storage.
- Rendered seller data must be escaped before output.
- Successful submissions should create or prepare a deal intake record.
- Internal users should be able to review submissions from the admin dashboard or deal intake module.

## Priority System Page: /dashboard

### Purpose

The /dashboard page is the internal executive command center for Algonquian Real Estate operations. It should provide a controlled overview of deal flow, buyer activity, underwriting status, document activity, funding status, automation events, and revenue indicators.

### Recommended Page Title

Dashboard

### Recommended URL

/dashboard

### Required Shortcode

```text
[algq_admin_dashboard]
```

### WPBakery Implementation

Use:

```text
[vc_column_text]
[algq_admin_dashboard]
[/vc_column_text]
```

Never use:

```html
</vc_column_text>
```

### Access Rule

The dashboard must be restricted to authorized internal users. The default production capability should be `manage_options` until a custom Algonquian role and capability matrix is implemented.

### Recommended Dashboard Widgets

1. Active deal count.
2. New seller submissions.
3. Pipeline stage summary.
4. Offers generated.
5. Buyer registrations.
6. Funding status summary.
7. Document generation activity.
8. Automation event log.
9. Digital store revenue summary.
10. System health and plugin dependency checks.

### Production Requirements

- Page must be generated on plugin activation if it does not already exist.
- Page must not duplicate if the slug already exists.
- Page must use valid shortcode syntax.
- Dashboard shortcode must deny access to unauthorized users.
- All widget data must be escaped before output.
- Administrative actions must require capability checks and nonces.
- Dashboard should support future role-based visibility.
- Dashboard should be prepared for CSV export, PDF reporting, audit logging, and saved dashboard layouts.

## Priority System Page: /plugins

### Purpose

The /plugins page is the internal plugin catalog and product library for the Algonquian Real Estate Platform. It should present each production plugin as a product-style module with versioning, documentation, getting-started links, and visual branding.

### Recommended Page Title

Plugin Library

### Recommended URL

/plugins

### Recommended Shortcode

```text
[algq_plugin_library]
```

If the shortcode is not yet implemented, the page should temporarily render static catalog content until the plugin library renderer is available.

### WPBakery Implementation

Use:

```text
[vc_column_text]
[algq_plugin_library]
[/vc_column_text]
```

Never use:

```html
</vc_column_text>
```

### Required Plugin Cards

1. Algonquian Deal Intake
2. Algonquian Pipeline CRM
3. Algonquian MAO Engine
4. Algonquian Offer Generator
5. Algonquian Buyer Portal
6. Algonquian Funding Tracker
7. Algonquian Automation Engine
8. Algonquian PDF & Signature Engine
9. Algonquian Document Library
10. Algonquian Admin Command Center

### Required Card Elements

- Plugin logo
- UI mockup image
- Plugin name
- Version number
- By Onegodian
- Short description
- View Details button
- Getting Started button
- Documentation button

### Production Requirements

- Page must be generated on plugin activation if it does not already exist.
- Page must not duplicate if the slug already exists.
- Page must use valid shortcode syntax.
- Each plugin card must escape text, URLs, and attributes before output.
- Buttons should route to the plugin page map documented in `docs/plugin-page-map.md`.
- Version numbers must follow semantic versioning.
- The page should support future filtering by category: Acquisition, Capital, Automation, Documents, Revenue, and Command Center.

## WPBakery Rule

Use:

```text
[vc_column_text]
[shortcode_here]
[/vc_column_text]
```

Never use:

```html
</vc_column_text>
```

## Minimum Production Test Checklist

1. Activate plugin on a staging WordPress instance.
2. Confirm no fatal errors on activation.
3. Confirm required database tables are created.
4. Confirm automatic pages are created only once.
5. Confirm shortcodes render buffered HTML.
6. Confirm admin menus load without PHP notices.
7. Confirm all admin actions require `manage_options` or the correct custom capability.
8. Confirm nonce validation on all forms.
9. Confirm submitted values are sanitized before storage.
10. Confirm rendered values are escaped before output.
11. Confirm uninstall cleanup removes plugin-owned options and tables only when intended.
12. Confirm README, CHANGELOG, SECURITY, and docs are included in each ZIP.

## Launch Priority

1. Core platform activation
2. Deal intake
3. Pipeline CRM
4. MAO calculator
5. Buyer registration
6. Document library
7. Digital store
8. Education subdomain
9. Command Center
10. Revenue dashboards
