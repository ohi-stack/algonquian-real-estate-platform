# Algonquian Real Estate Platform

Enterprise real estate acquisition, underwriting, CRM, buyer registration, document automation, marketplace, and investor operations platform for Algonquian Real Estate LLC.

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

## Production Plugin Sources

Canonical unpacked source packages are maintained under `plugins/`. The Deal Marketplace source has been upgraded to **2.0.0 Production** at:

```text
plugins/algq-deal-marketplace/
```

The Marketplace production upgrade adds shared buyer-capability reconciliation, versioned NDA evidence, record-level deal access, controlled package delivery, validated offers, Platform audit and mail hooks, Stripe entitlement events, REST routes, and an end-to-end release gate.

## Platform Purpose

This repository contains the WordPress plugin source, documentation, roadmap, branding placeholders, database schema, and deployment notes for Algonquian Real Estate LLC, a Connecticut limited liability company organized on February 11, 2026.

The Version 1.0 objective is to convert the website into a working acquisition and operations platform that can capture seller leads, create deal records, underwrite opportunities, register buyers, distribute controlled opportunities, and give internal users a command dashboard.

## Initial Shortcodes

```text
[algq_seller_intake]
[algq_mao_calculator]
[algq_buyer_registration]
[algq_deal_marketplace]
[algq_admin_dashboard]
```

## WPBakery Usage

```text
[vc_column_text]
[algq_seller_intake]
[/vc_column_text]
```

## Repository Layout

```text
plugin/      Legacy WordPress platform source
plugins/     Canonical unpacked plugin source packages
packages/    Distributable release archive index
assets/      Branding and front-end assets
database/    SQL schema and table notes
docs/        Installation and module documentation
roadmap/     Version roadmap and launch plan
branding/    Brand guidelines and image placeholders
```

## Current Status

Private production repository. Platform release status remains 1.0.0 Release Candidate while individual plugin packages may advance through independently documented production releases.
