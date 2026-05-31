# Algonquian Real Estate Platform Release Checklist

## Release Status

**1.0.0 Release Candidate**

This checklist must be completed before plugin ZIPs are uploaded to a live WordPress site.

## Pre-ZIP Checks

Run from repository root:

```bash
find . -path './vendor' -prune -o -path './node_modules' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
```

If PHPCS is installed:

```bash
phpcs --standard=WordPress plugin plugins
```

Build plugin ZIPs:

```bash
bash scripts/build-plugin-zips.sh
```

## Required WordPress Plugins

Minimum site stack:

- WordPress current stable version
- WooCommerce
- WP Mail SMTP or equivalent transactional mail plugin
- Backup plugin or host-level backup
- Security plugin or WAF

Optional but recommended:

- WooCommerce Subscriptions
- WooCommerce Memberships
- Stripe gateway
- PayPal gateway
- FluentCRM

## Installation Order

Install in this order when available:

1. `algq-core`
2. `algq-deal-intake`
3. `algq-pipeline-crm`
4. `algq-mao-engine`
5. `algq-offer-generator`
6. `algq-document-library`
7. `algq-pdf-signature`
8. `algq-automation-engine`
9. `algq-buyer-portal`
10. `algq-funding-tracker`
11. `algq-marketplace`
12. `algq-command-center`
13. `algq-reporting-analytics`
14. `algq-revenue-systems`
15. `algq-product-vault`
16. `algq-affiliate-engine`

If `algq-core` is not yet complete, install only plugins that include standalone dependency fallbacks.

## Activation Checks

For each plugin:

- Activate without fatal error.
- Confirm admin menu loads.
- Confirm shortcodes render without direct output warnings.
- Confirm database tables/options are created.
- Confirm deactivation does not delete live data.
- Confirm uninstall does not delete tables unless destructive cleanup is explicitly enabled.

## Security Checks

Each production plugin must have:

- No direct file access.
- Nonces on public/admin forms.
- Capability checks on admin actions.
- REST permission callbacks.
- Sanitized input.
- Escaped output.
- Prepared SQL statements.
- No plaintext API keys, payment credentials, or secrets.

## Minimum End-to-End Flow

Validate before public launch:

1. Seller submits property.
2. Deal record is created.
3. MAO underwriting is generated.
4. Deal enters pipeline.
5. Offer can be generated.
6. PDF/document can be generated.
7. Buyer can register.
8. Buyer access can be approved/revoked.
9. Funding record can be attached.
10. Command Center metrics update.
11. WooCommerce product can be purchased.
12. Protected download/license access works.

## Rollback Plan

Before installing ZIPs:

- Create full database backup.
- Create full file backup.
- Export current active plugin list.
- Install on staging first whenever possible.
- Upload one plugin at a time.
- If a fatal error occurs, disable the plugin folder by renaming it through hosting file manager or SFTP.

## Release Classification

Until every item above passes, use:

**Production Candidate / Staging Approved**

After all checks pass, use:

**Production Release 1.0.0**
