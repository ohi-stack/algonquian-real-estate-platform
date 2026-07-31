# Algonquian Real Estate Platform

Enterprise real estate acquisition, underwriting, CRM, buyer registration, document automation, investor operations, digital commerce, and shared infrastructure platform for Algonquian Real Estate LLC.

## Version

**1.0.0 Release Candidate**

## Release Status

**1.0.0 Release Candidate**

## Version 1.0 Core Modules

1. Seller Intake
2. Deal CRM
3. MAO Calculator
4. Buyer Registration
5. Admin Dashboard
6. Digital Store
7. Buyer Portal
8. Funding Tracker
9. Document Library
10. Automation Engine
11. Shared Stripe Integration

## Platform Purpose

This repository contains the WordPress plugin source, documentation, roadmap, branding placeholders, database schema, deployment notes, and shared service contracts for Algonquian Real Estate LLC.

The Version 1.0 objective is to convert the website into a working acquisition and operations platform that can capture seller leads, create deal records, underwrite opportunities, register buyers, manage documents, automate workflows, sell digital products, and provide internal reporting.

## Shared Stripe Integration

Stripe is implemented as a centralized Platform Plugin service. Dependent plugins must consume the shared service rather than storing separate credentials or registering independent webhook endpoints.

Initial consumers:

- Digital Store
- WooCommerce Bridge
- Property Stewardship
- Automation Engine
- Admin Command Center
- Buyer Portal
- Investor Network
- Document Library
- Deal Marketplace
- Funding Tracker
- PDF & Signature Engine

The core Deal Intake workflow remains free. Paid consultations may be implemented only as optional add-ons.

Documentation:

- [`docs/STRIPE_INTEGRATION.md`](docs/STRIPE_INTEGRATION.md)
- [`docs/STRIPE_RELEASE_CHECKLIST.md`](docs/STRIPE_RELEASE_CHECKLIST.md)
- [`docs/stripe-plugin-integrations.json`](docs/stripe-plugin-integrations.json)

Credentials must be provided through environment configuration or `wp-config.php` constants and must never be committed to source control.

## Initial Shortcodes

```text
[algq_seller_intake]
[algq_mao_calculator]
[algq_buyer_registration]
[algq_admin_dashboard]
[algq_digital_store]
[algq_buyer_portal]
[algq_funding_tracker]
[algq_document_library]
[algq_automation_engine]
```

## WPBakery Usage

```text
[vc_column_text]
[algq_seller_intake]
[/vc_column_text]
```

## Repository Layout

```text
plugin/      WordPress plugin source and shared integrations
assets/      Branding and front-end assets
database/    SQL schema and table notes
docs/        Architecture, installation, module, and release documentation
roadmap/     Version roadmap and launch plan
branding/    Brand guidelines and image placeholders
```

## Stripe Production Gate

The shared Stripe class is an integration scaffold until it is loaded from the Platform Plugin bootstrap and passes webhook, entitlement, refund, subscription, security, and live-mode validation. See the release checklist before enabling live payments.

## Current Status

Private production repository. Release Status: 1.0.0 Release Candidate.
