# Algonquian Real Estate Platform

Enterprise real estate acquisition, underwriting, CRM, buyer registration, document automation, funding, and investor operations platform for Algonquian Real Estate LLC.

## Version

**1.0.0 Release Candidate**

## Release Status

**1.0.0 Release Candidate**

## Version 1.0 Core Modules

1. Seller Intake
2. Deal CRM
3. MAO Calculator
4. Buyer Registration
5. Funding Tracker
6. Admin Dashboard

## Platform Purpose

This repository contains the WordPress plugin source, documentation, roadmap, branding placeholders, database schema, and deployment notes for Algonquian Real Estate LLC, a Connecticut limited liability company organized on February 11, 2026.

The Version 1.0 objective is to convert the website into a working acquisition and operations platform that can capture seller leads, create deal records, underwrite opportunities, register buyers, track capital relationships and deal funding, and give internal users a command dashboard.

## Funding Tracker

The production Funding Tracker source is maintained at:

```text
plugins/algq-funding-tracker/
```

Current release: **1.0.0**

The module provides:

- capital-source relationship records;
- deal-level funding requests and commitments;
- requested, committed, and funded amount tracking;
- funding status and progress controls;
- lender, CDFI, private-capital, equity, joint-venture, seller-financing, grant, and internal-capital classifications;
- capability-controlled administration;
- CSV export;
- permission-gated REST routes;
- centralized audit-event integration;
- WPBakery-compatible generated pages.

The Funding Tracker is an administrative tracking system. It does not transfer funds, originate loans, replace accounting records, or replace executed financing documents.

## Initial Shortcodes

```text
[algq_seller_intake]
[algq_mao_calculator]
[algq_buyer_registration]
[algq_funding_tracker]
[algq_funding_dashboard]
[algq_admin_dashboard]
```

## WPBakery Usage

```text
[vc_column_text]
[algq_funding_dashboard]
[/vc_column_text]
```

## Repository Layout

```text
plugin/      WordPress platform plugin source
plugins/     Companion plugin source directories
assets/      Branding and front-end assets
database/    SQL schema and table notes
docs/        Installation and module documentation
roadmap/     Version roadmap and launch plan
branding/    Brand guidelines and image placeholders
```

## Current Status

Private production repository. Overall platform release status remains 1.0.0 Release Candidate. Individual modules may have production-version source pending staging and integration validation.
