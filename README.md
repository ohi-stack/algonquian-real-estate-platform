# Algonquian Real Estate Platform

Enterprise real estate acquisition, underwriting, CRM, buyer registration, document automation, investor operations, Property Stewardship, and technology platform for Algonquian Real Estate LLC.

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

## Platform Purpose

This repository contains the WordPress plugin source, documentation, roadmap, branding placeholders, database schema, deployment notes, and website route architecture for Algonquian Real Estate LLC, a Connecticut limited liability company organized on February 11, 2026.

The Version 1.0 objective is to convert the website into a working acquisition and operations platform that can capture seller leads, create deal records, underwrite opportunities, register buyers, coordinate Property Stewardship activity, manage documents, and give authorized internal users a command dashboard.

## Canonical Website Architecture

- [Algonquian Real Estate Website Sitemap](docs/website-sitemap.md)
- [Public Sitemap and Indexing Policy](config/sitemap-policy.json)

The sitemap separates public company, service, educational, investment, technology, and lead-generation pages from protected operational routes. SEO exclusions do not replace authentication, authorization, record-level access control, or secure file delivery.

## Initial Shortcodes

```text
[algq_seller_intake]
[algq_mao_calculator]
[algq_buyer_registration]
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
plugin/      WordPress plugin source
assets/      Branding and front-end assets
database/    SQL schema and table notes
docs/        Installation, architecture, sitemap, and module documentation
config/      Machine-readable platform and indexing policies
roadmap/     Version roadmap and launch plan
branding/    Brand guidelines and image placeholders
```

## Current Status

Private production repository. Release Status: 1.0.0 Release Candidate.
