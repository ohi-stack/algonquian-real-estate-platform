# Changelog

All notable changes to the Algonquian Education Center plugin will be documented in this file.

## [1.0.0-rc1] - 2026-05-31

### Added

- Initial release candidate for Algonquian Education Center.
- WordPress plugin bootstrap with release metadata.
- Activation hook for setup routines.
- Automatic page generation for the Education Center, course library, user progress, seller education, buyer education, lender education, acquisition training, platform training, digital product library, and plugin documentation pages.
- Custom post types:
  - `algq_course`
  - `algq_lesson`
  - `algq_guide`
- LMS metadata fields for course audience, access level, duration, difficulty, WooCommerce product ID, parent course ID, lesson order, video URL, download URL, guide category, guide product ID, and related plugin.
- Shortcodes:
  - `[algq_education_home]`
  - `[algq_course_list]`
  - `[algq_course]`
  - `[algq_lesson]`
  - `[algq_education_track]`
  - `[algq_platform_training]`
  - `[algq_product_library]`
  - `[algq_user_progress]`
- Buffered template rendering for all shortcodes.
- Learning progress database table.
- AJAX lesson completion and incomplete actions.
- Course completion percentage calculations.
- User learning summary reporting.
- Access control for public, registered, buyer, lender, paid, internal, and admin content.
- WooCommerce integration for paid courses, guides, product buttons, and linked product library queries.
- Admin dashboard with KPI cards and production status checks.
- Settings screen for brand theme, progress visibility, and WooCommerce link enablement.
- Front-end templates for education home, course list, education tracks, platform training, product library, user progress, course detail, and lesson detail.
- Branded front-end CSS using Algonquian Real Estate navy, blue, gold, and white styling.
- Branded admin CSS for executive dashboard presentation.
- Front-end JavaScript for lesson completion and progress updates.
- Admin JavaScript for dashboard interactions and theme-preview behavior.
- Optional uninstall cleanup for plugin options, progress table, courses, lessons, and guides.
- README documentation.

### Security

- Added direct file access protection to PHP files.
- Added nonce verification for AJAX progress updates.
- Added nonce verification and capability checks for post metadata saves.
- Added settings sanitization.
- Added output escaping across templates and admin screens.
- Added prepared SQL queries for progress lookups and counts.

### Notes

- This version is a release candidate and should be tested in staging before use on a production site.
- WooCommerce features require WooCommerce to be installed and enabled in the Education Center settings.
