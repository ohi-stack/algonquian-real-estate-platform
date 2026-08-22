# Algonquian Real Estate Enterprise Mega Menu

## Purpose

The enterprise mega menu creates one structured navigation system for AlgonquianRealEstate.com. It organizes the website around the audiences and operating systems already established by Algonquian Real Estate LLC:

1. Property Owners
2. Acquisitions
3. Investors & Capital
4. Technology Platform
5. Documents & Resources

The structure avoids presenting the company as only a “we buy houses” business. It provides clear paths for owners who are selling, planning, inheriting, downsizing, or seeking property stewardship, while preserving direct navigation to acquisition, investor, technology, and institutional-document functions.

## Module

```text
modules/enterprise-mega-menu/algq-enterprise-mega-menu.php
```

Assets:

```text
modules/enterprise-mega-menu/assets/algq-mega-menu.css
modules/enterprise-mega-menu/assets/algq-mega-menu.js
```

## Shortcode

```text
[algq_mega_menu]
```

WPBakery placement:

```text
[vc_column_text]
[algq_mega_menu]
[/vc_column_text]
```

Do not use an HTML-style closing tag for `vc_column_text`.

## Menu architecture

### Property Owners

- Sell Your Property
- What Are My Options?
- Property Stewardship
- Inherited Property Guidance
- Senior Property Assistance
- Trusted Property Contact

### Acquisitions

- Acquisition Criteria
- Development Concepts
- Multifamily
- Commercial Properties
- Seller Financing
- Submit a Deal

### Investors & Capital

- Investor Network
- Buyer Registration
- Buyer Portal
- Private Capital
- Funding Relationships
- Investor Resources

### Technology Platform

- Platform Overview
- Plugin Suite
- Pipeline CRM
- MAO Engine
- Document Library
- Automation Engine

### Documents & Resources

- Document Library
- Forms & Documents
- Plugin Guides
- Digital Store
- Property Resources
- Contact

## Permanent actions

The right side of the desktop navigation includes:

- Contact
- Submit a Property

The mobile menu retains these actions at the end of the expanded navigation.

## Design system

The module uses the shared Algonquian interface vocabulary:

- Dark navy and institutional blue
- Gold primary accent
- Teal-compatible design tokens
- White and light-gray surfaces
- Rounded cards
- Clear heading hierarchy
- Branded classification blocks
- Responsive desktop, tablet, and mobile layouts

## Accessibility

The implementation includes:

- Semantic `nav` landmark
- Descriptive navigation label
- Button-based submenu triggers
- `aria-expanded` state
- `aria-controls` relationships
- Hidden panel state
- Escape-key close behavior
- Outside-click close behavior
- Keyboard-visible focus styles
- Mobile menu state controls

## WordPress integration options

### Shortcode placement

Place `[algq_mega_menu]` in a global header template, WPBakery header area, reusable block, or page-builder template.

### Theme integration

A theme may call:

```php
echo do_shortcode( '[algq_mega_menu]' );
```

The theme should remove or hide its competing primary navigation only after the mega menu has been tested across desktop and mobile layouts.

## Route governance

Menu URLs are defined centrally in the module. A route may exist before the related page is published; production deployment should validate every destination and either publish the page, redirect the route, or remove the link before launch.

The mega menu does not own page content, user permissions, deal records, documents, or portal authorization. Those remain under the appropriate platform plugin or service module.

## Production validation checklist

- Activate without PHP errors.
- Confirm shortcode output.
- Confirm CSS and JavaScript load only once.
- Test all menu links.
- Test desktop open and close behavior.
- Test tablet and mobile navigation.
- Test keyboard-only operation.
- Test Escape-key behavior.
- Test screen-reader state announcements.
- Confirm the panel does not appear behind theme headers or overlays.
- Confirm no global theme CSS overrides the menu.
- Confirm account and internal links remain permission protected.
- Confirm theme navigation is not removed until the replacement passes review.
