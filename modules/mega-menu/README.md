# Algonquian Mega Menu

Responsive, accessible mega navigation for Algonquian Real Estate public services, investor resources, acquisition workflows, documentation, and protected platform operations.

## Navigation structure

### Property Owners

- Explore Your Options
- Sell Your Property
- Property Stewardship
- Inherited Property Guidance
- Senior Property Assistance

### Investors & Buyers

- Investor Network
- Buyer Registration
- Buyer Portal
- Funding & Capital
- Development Concepts

### Deals & Acquisitions

- Acquisition Criteria
- Underwriting
- Deal Marketplace
- Documents & Due Diligence
- Internal Deal Pipeline, shown only to authenticated users with `view_algq_deals`

### Technology Platform

- Plugin Library
- Getting Started
- Documentation
- Digital Store
- Admin Command Center, shown only to authenticated users with `manage_algq_platform`

This hierarchy reflects the platform’s public, registered-user, and internal operating layers. The broader site architecture already separates public pages, buyers and lenders, and protected deal, underwriting, document, and dashboard routes. fileciteturn16file4

## Installation

The module is packaged as a standalone WordPress plugin source directory.

1. Copy `modules/mega-menu` into the WordPress plugins directory or build it as a ZIP.
2. Activate **Algonquian Mega Menu**.
3. Place the menu through a shortcode or theme template.

## Shortcode

```text
[algq_mega_menu]
```

Optional label:

```text
[algq_mega_menu label="Explore ARE"]
```

When inserted inside WPBakery, use valid shortcode closure:

```text
[vc_column_text]
[algq_mega_menu]
[/vc_column_text]
```

Never close WPBakery with `</vc_column_text>`. fileciteturn16file2

## Theme template function

```php
<?php
if ( function_exists( 'algq_render_mega_menu' ) ) {
    algq_render_mega_menu();
}
?>
```

Recommended placement is directly below the primary site header or in the theme header template where the former navigation renders.

## Registered menu location

The module registers:

```text
algq_mega_menu
```

This location is reserved for future WordPress menu-editor integration. Version 1.0.0 uses the controlled platform hierarchy so required operational routes and permission boundaries remain consistent.

## Accessibility

The menu includes:

- semantic navigation markup;
- an accessible menu label;
- `aria-expanded` and `aria-controls` state;
- keyboard Escape handling;
- visible focus treatments;
- outside-click closing;
- reduced-motion support;
- responsive mobile panel behavior;
- descriptive link copy.

## Security and permissions

The menu does not perform writes and does not accept arbitrary URLs from visitors.

Protected links are conditionally rendered:

- `/pipeline/` requires `view_algq_deals`;
- `/dashboard/` requires `manage_algq_platform`.

The linked destination must still perform its own capability check. Hiding a navigation item is not an authorization control.

## Brand standard

The module uses the shared Algonquian interface system:

- dark navy foundation;
- gold action accent;
- teal and blue supporting accents;
- rounded controls;
- institutional typography;
- consistent button hierarchy;
- responsive layout.

The protected platform standard requires one design system and interaction model across all plugins and generated pages. fileciteturn16file24

## Activation testing

Before production deployment, verify:

1. Every public route exists or redirects intentionally.
2. Restricted links appear only for authorized users.
3. Destination pages enforce their own capabilities.
4. The menu does not conflict with the active WordPress theme header.
5. Desktop, tablet, and mobile layouts render correctly.
6. Keyboard focus remains visible and logical.
7. The menu closes with Escape and outside clicks.
8. No global theme CSS overrides the menu panel positioning.
9. Caching and optimization plugins preserve the JavaScript behavior.
10. The shortcode renders correctly inside WPBakery.

## Production integration

The preferred final integration is to fold this module into the Platform Plugin’s shared navigation service after theme-level testing. The Platform Plugin is the architectural owner of shared navigation, roles, capabilities, generated pages, and interface components. fileciteturn16file19
