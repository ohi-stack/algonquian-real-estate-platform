# Algonquian MAO Engine Production Acceptance

A release is not production-approved solely because the header says `2.0.0`.

## Required evidence

1. Clean installation and 1.0.0-to-2.0.0 migration on WordPress 6.5+ and PHP 8.1+.
2. No fatal errors, warnings, or notices with `WP_DEBUG` enabled.
3. Independent fixtures for wholesale, flip, rental, and multifamily formulas.
4. Public users can calculate but cannot save, approve, or read scenarios.
5. Analysts can save drafts but cannot approve.
6. Approvers can approve once; ordinary UI does not rewrite approval evidence.
7. Offer Generator receives only approved scenarios.
8. Pipeline CRM remains the only authority that persists deal stage changes.
9. Generated pages are correctly nested and use `[/vc_column_text]`.
10. Existing administrator-edited pages are not overwritten.
11. Rate-limit, nonce, REST validation, SQL, escaping, and audit tests pass.
12. Deactivation preserves data; uninstall removes data only after explicit opt-in.
