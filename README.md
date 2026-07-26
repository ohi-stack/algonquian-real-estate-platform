# Algonquian Real Estate Platform

Enterprise real estate acquisition, underwriting, CRM, buyer registration, document automation, homeowner services, and investor operations platform for Algonquian Real Estate LLC.

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
6. Property Stewardship Services™

## Platform Purpose

This repository contains the WordPress plugin source, modular service packages, documentation, roadmap, branding placeholders, database schema, and deployment notes for Algonquian Real Estate LLC, a Connecticut limited liability company organized on February 11, 2026.

The Version 1.0 objective is to convert the website into a working acquisition and operations platform that can capture seller leads, create deal records, underwrite opportunities, register buyers, coordinate owner-authorized property stewardship activity, and give internal users a command dashboard.

## Initial Shortcodes

```text
[algq_seller_intake]
[algq_mao_calculator]
[algq_buyer_registration]
[algq_admin_dashboard]
[algq_property_stewardship]
[algq_stewardship_portal]
```

## Property Stewardship Services

The stewardship module supports property observation, visit reporting, maintenance scheduling, vendor coordination, emergency-contact authorization, and transition support. It is expressly structured as a property-coordination service rather than a legal, fiduciary, caregiving, licensed-inspection, insurance-adjustment, or security role.

Module source:

```text
modules/property-stewardship/algq-property-stewardship.php
```

Documentation:

```text
docs/property-stewardship-services.md
```

## WPBakery Usage

```text
[vc_column_text]
[algq_seller_intake]
[/vc_column_text]
```

Stewardship example:

```text
[vc_column_text]
[algq_property_stewardship]
[/vc_column_text]
```

## Repository Layout

```text
plugin/      WordPress platform plugin source
modules/     Modular companion service packages
assets/      Branding and front-end assets
database/    SQL schema and table notes
docs/        Installation and module documentation
roadmap/     Version roadmap and launch plan
branding/    Brand guidelines and image placeholders
```

## Current Status

Private production repository. Release Status: 1.0.0 Release Candidate.
