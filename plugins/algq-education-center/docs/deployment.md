# Algonquian Education Center Deployment Guide

Version: 1.0.0-enterprise-rc1

## Purpose

This guide documents the final release-candidate deployment process for the Algonquian Education Center enterprise LMS plugin.

## Pre-Deployment Checklist

- Confirm `algq-education-center.php` loads all enterprise modules.
- Confirm activation creates required pages, roles, audit tables, and rewrite rules.
- Confirm WooCommerce is installed if paid courses, paid guides, or revenue analytics are used.
- Confirm Dompdf is installed if true PDF certificates are required. Without Dompdf, the certificate generator falls back to downloadable HTML certificates.
- Confirm HTTPS is active on all login, payment, tenant, certificate, and dashboard pages.
- Confirm administrator roles are limited to trusted users.

## Required WordPress Environment

- WordPress 6.x or higher recommended
- PHP 8.0 or higher recommended
- MySQL 5.7+ or MariaDB equivalent
- WooCommerce optional but recommended for monetization

## Installation

1. Upload `algq-education-center-1.0.0-enterprise-rc1.zip` through WordPress Admin.
2. Activate the plugin.
3. Visit Settings > Permalinks and save once to refresh rewrite rules.
4. Visit the Algonquian Education admin dashboard.
5. Review generated pages and shortcodes.
6. Configure LMS settings, certificate issuer, email notifications, CE credits, and WooCommerce settings.

## Post-Activation Validation

Validate:

- Courses can be created.
- Lessons can be assigned to courses.
- Users can enroll in courses.
- Progress can be marked complete.
- Certificates issue after course completion.
- Verification URLs resolve.
- PDF or HTML certificate downloads work.
- Audit logs record major events.
- REST and mobile API endpoints respond for authenticated users.
- Command Center KPI shortcode renders for administrators.

## Rollback

1. Deactivate the plugin.
2. Restore the previous plugin ZIP.
3. Restore database backup if schema changes must be reversed.
4. Re-save permalinks.

## Release Notes

This release candidate includes LMS, certification, CE credits, tenant management, white-label portals, corporate accounts, Command Center KPI sync, REST APIs, mobile APIs, SCORM/xAPI foundation, SaaS licensing, audit logging, privacy tools, export tools, and performance caching.
