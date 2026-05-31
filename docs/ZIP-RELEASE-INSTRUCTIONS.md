# Zip Release Instructions

Release Status: 1.0.0 Release Candidate  
Owner: Onegodian | Algonquian Real Estate

## Purpose

This document controls how installable WordPress plugin zip files should be generated for same-day deployment to AlgonquianRealEstate.com.

## Required Folder Shape

WordPress expects the uploaded zip to contain one top-level plugin folder, with the main plugin file directly inside that folder.

Correct:

```text
algonquian-real-estate-platform.zip
└── algonquian-real-estate-platform/
    ├── algonquian-real-estate-platform.php
    ├── includes/
    ├── templates/
    ├── assets/
    ├── docs/
    ├── README.md
    └── CHANGELOG.md
```

Incorrect:

```text
algonquian-real-estate-platform.zip
└── repo-root/
    └── plugin/
        └── algonquian-real-estate-platform.php
```

## Manual Zip Command

From the parent directory of the plugin folder:

```bash
zip -r algonquian-real-estate-platform.zip algonquian-real-estate-platform \
  -x "*.git*" \
  -x "*/node_modules/*" \
  -x "*/vendor/bin/*" \
  -x "*/tests/*" \
  -x "*/.DS_Store" \
  -x "*/__MACOSX/*" \
  -x "*/.env" \
  -x "*/.env.*"
```

## Pre-Upload Checklist

1. Confirm the zip contains one plugin folder.
2. Confirm the main plugin PHP file is at the first folder level.
3. Confirm no secret keys are included.
4. Confirm no `.git` folder is included.
5. Confirm no local `.env` files are included.
6. Confirm README states Release Status: 1.0.0 Release Candidate.
7. Confirm CHANGELOG has the current version entry.
8. Upload zip through WordPress Admin > Plugins > Add New > Upload Plugin.
9. Activate plugin.
10. Visit the admin screen and shortcode pages.

## First Activation Test

After activation, verify:

- No fatal error on activation.
- Required database tables exist.
- Required pages were created.
- Admin menu loads.
- Public shortcodes render.
- Admin-only shortcodes reject non-admin users.
- Missing third-party credentials show admin notices only.

## Production Safety Notes

- Do not enable destructive uninstall routines on launch day.
- Do not delete database tables on deactivation.
- Keep backups before replacing plugins.
- Activate one plugin at a time and test before moving to the next.
- Payment plugins must remain in test mode until live Stripe keys and webhook secrets are confirmed.
