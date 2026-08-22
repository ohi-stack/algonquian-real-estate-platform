# Algonquian Navigation

**Version:** 0.2.0  
**Owner:** Algonquian Real Estate, LLC  
**Purpose:** Public enterprise navigation for AlgonquianRealEstate.com.

## Production interface

Algonquian Navigation renders the approved six-section public header navigation:

1. Property Owners
2. Acquisitions
3. Investors & Capital
4. Services
5. Technology
6. Company

It also renders utility actions for Search, Buyer Login, Client Portal, Contact, and the primary **Submit a Property** CTA, plus a four-column footer.

## Responsive behavior

- 1400px and wider: six primary navigation labels plus full utility labels.
- 1025–1399px: six primary labels remain visible while secondary utilities collapse to accessible icons.
- 1024px and below: hamburger drawer.
- The first hamburger tap immediately exposes all six primary sections.
- There is no second generic `Menu` control inside the drawer.
- Each primary section independently expands its own grouped links.
- Closing the drawer resets expanded sections and restores focus to the hamburger when appropriate.
- Escape closes the mobile drawer or open desktop sections.
- The mobile drawer locks body scrolling and provides an overlay/backdrop.

The 1024px mobile/tablet switch is intentionally conservative to prevent horizontal overflow on smaller laptops and tablets.

## Shortcodes

```text
[algq_mega_menu]
[algq_footer_links]
```

WPBakery placement:

```text
[vc_column_text]
[algq_mega_menu]
[/vc_column_text]
```

Never use `</vc_column_text>`.

## Theme integration

A theme can render the navigation directly:

```php
if ( function_exists( 'algq_render_mega_menu' ) ) {
    algq_render_mega_menu();
}
```

The plugin registers these WordPress menu locations for theme interoperability:

- `algq_primary_menu`
- `algq_utility_menu`
- `algq_mobile_menu`
- `algq_footer_company`
- `algq_footer_property`
- `algq_footer_investors`
- `algq_footer_legal`

The built-in renderer uses the canonical schema by default. Themes and companion plugins can alter labels or routes through:

- `algq_navigation_schema`
- `algq_navigation_utilities`
- `algq_footer_navigation_schema`

## Accessibility

The renderer includes:

- semantic `nav`, lists, links, and buttons;
- synchronized `aria-expanded` states;
- `aria-controls` relationships;
- visible keyboard focus states;
- Escape-key closing;
- mobile focus containment;
- accessible icon labels and titles;
- reduced-motion handling.

Navigation visibility is presentation only. Protected buyer, client, CRM, underwriting, funding, document, automation, and administrative destinations must enforce authorization in the destination plugin.

## Acceptance tests

Before live deployment, verify at minimum:

- 1440px desktop;
- 1366px laptop;
- 1280px laptop;
- 1024px tablet;
- 768px tablet;
- 430px mobile;
- 390px mobile / iPhone Safari;
- first-tap hamburger behavior;
- section accordion behavior;
- keyboard and Escape behavior;
- no horizontal page overflow;
- utility icons remain understandable through `aria-label`/`title`;
- Submit a Property remains visually prominent;
- protected routes still enforce their own access controls.

Repository validation is not a substitute for testing the installed WordPress theme/header integration.
