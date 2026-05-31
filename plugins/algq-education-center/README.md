# Algonquian Education Center

Version: 1.0.0-rc1  
Author: Onegodian | Algonquian Real Estate

## Purpose

Algonquian Education Center provides the LMS, education-track, digital product, and platform training layer for Algonquian Real Estate. It supports seller education, buyer onboarding, lender preparation, acquisition training, internal platform training, and WooCommerce-linked digital products.

## Core Features

- Automatic page generation on activation
- LMS course, lesson, and guide post types
- Seller, buyer, lender, acquisition, and platform training tracks
- Learning progress table and AJAX completion actions
- WooCommerce product linking for paid courses and guides
- Admin dashboard with KPI cards and production status checks
- Front-end and admin branding assets
- Access control for public, registered, buyer, lender, paid, internal, and admin content

## Shortcodes

```text
[algq_education_home]
[algq_course_list]
[algq_course id="123"]
[algq_lesson id="456"]
[algq_education_track type="seller"]
[algq_education_track type="buyer"]
[algq_education_track type="lender"]
[algq_education_track type="acquisition"]
[algq_platform_training]
[algq_product_library]
[algq_user_progress]
```

## Auto-Created Pages

On activation, the plugin creates:

- `/education`
- `/education/courses`
- `/education/sellers`
- `/education/buyers`
- `/education/lenders`
- `/education/acquisition`
- `/education/platform-training`
- `/education/products`
- `/education/progress`
- `/plugin/education-center`
- `/plugin/education-center/start`
- `/plugin/education-center/docs`

## Custom Post Types

- `algq_course`
- `algq_lesson`
- `algq_guide`

## WooCommerce Integration

Courses may be linked to WooCommerce using:

- `algq_course_product_id`

Guides may be linked using:

- `algq_guide_product_id`

When enabled in settings, the Product Library displays linked premium training and guide products.

## Admin Menu

```text
Algonquian Education
├── Dashboard
├── Courses
├── Lessons
├── Guides
└── Settings
```

## Release Status

This package is a 1.0.0 release candidate and should be tested on staging before production deployment.
