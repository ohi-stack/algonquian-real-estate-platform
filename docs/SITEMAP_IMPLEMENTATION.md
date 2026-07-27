# Sitemap Implementation Requirements

## Purpose

Translate `docs/ENTERPRISE_SITEMAP.md` into controlled WordPress pages, dynamic application routes, navigation, redirects, XML sitemaps, and capability boundaries.

## Route classes

### Static WordPress pages

Use ordinary WordPress pages for public company, service, acquisition, investor, lender, technology, documentation, legal, and landing-page content.

### Dynamic application routes

Do not create one WordPress page for every deal, offer, document, buyer record, funding record, or underwriting scenario. Implement dynamic paths through plugin endpoints, rewrite rules, REST-backed application views, or protected shortcode containers.

Dynamic route examples:

- `/deals/{deal-id}/`
- `/underwriting/{deal-id}/`
- `/offers/{offer-id}/`
- `/documents/{document-id}/`
- `/buyer/deals/{deal-id}/`
- `/funding/{deal-id}/`

### WordPress administration

Settings, users, capabilities, plugin registry, Mail Gateway, audit logs, system health, file controls, generated-page controls, and mega-menu administration should normally be WordPress-admin screens. Public-looking `/admin/.../` paths may be retained only as documented aliases or protected application routes.

## Page-generation rules

The Platform Plugin page generator must:

1. Create required static pages idempotently.
2. Resolve and create missing parent pages.
3. Store generated page IDs.
4. Add `_algq_generated_page` metadata.
5. Add `_algq_page_type` metadata.
6. Add `_algq_access_class` metadata.
7. Preserve administrator-edited content.
8. Avoid duplicate pages and duplicate slugs.
9. Restore missing required pages only through an authorized rebuild process.
10. Record material rebuild actions in the central audit log.

## Access classes

Use these canonical values:

- `public`
- `registered`
- `internal`
- `management`
- `administrator`

Navigation visibility is not authorization. Every protected destination must enforce the applicable capability.

## Capability examples

- `view_algq_buyer_portal`
- `view_algq_stewardship_portal`
- `view_algq_deals`
- `manage_algq_deals`
- `manage_algq_underwriting`
- `generate_algq_offers`
- `manage_algq_documents`
- `manage_algq_signatures`
- `manage_algq_automation`
- `view_algq_reports`
- `manage_algq_platform`

## Mega-menu mapping

The enterprise mega menu should use four top-level groups:

1. Property Owners
2. Investors and Buyers
3. Deals and Acquisitions
4. Technology Platform

Public links are always available. Registered and internal links must be rendered only when the current user has the relevant capability.

## Canonical-slug policy

Where earlier drafts used multiple routes for the same concept, use one canonical public route and redirect aliases.

Recommended canonical choices:

- `/what-are-my-options/`
- `/sell-your-property/`
- `/property-stewardship-services/`
- `/senior-property-assistance/`
- `/inherited-property-guidance/`
- `/acquisition-criteria/`
- `/our-portfolio/`
- `/development-concepts/`
- `/investors/`
- `/buyers/register/`
- `/technology/`
- `/plugins/`
- `/documentation/`
- `/forms-and-documents/`

Potential aliases should issue permanent redirects after production review.

## XML sitemap policy

Public indexable pages should be included in the site XML sitemap.

Exclude:

- authenticated portals;
- internal deals and pipeline routes;
- underwriting, offers, funding, and private documents;
- account pages;
- administrative pages;
- signature requests;
- search and filtered-result pages;
- draft development concepts;
- restricted plugin settings and system-health pages.

## Robots and metadata

Protected and operational pages should return appropriate authentication responses and include `noindex, nofollow` when a page shell is reachable before authentication.

Public development-concept pages should include status and ownership disclaimers and must not imply ownership, authorization, affiliation, or completed development.

## WPBakery standard

All generated WPBakery content must use:

```text
[vc_column_text]
Content
[/vc_column_text]
```

Never use `</vc_column_text>`.

## Release validation

Before deployment:

- verify every public route;
- verify every protected route and capability;
- test canonical redirects;
- confirm no duplicate slugs;
- confirm dynamic records are not created as ordinary pages;
- validate desktop and mobile navigation;
- validate XML sitemap exclusions;
- confirm protected pages are not indexed;
- run broken-link checks;
- confirm generated-page rebuilds preserve edited content;
- audit all page-generation and redirect changes.
