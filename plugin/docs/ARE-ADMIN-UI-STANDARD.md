# ARE Admin UI Standard

## Status

Platform-wide design contract for Algonquian Real Estate WordPress administration screens.

## Governing rule

The Platform Plugin owns the shared admin presentation layer. Companion plugins own their operational records and workflows, but should use this common UI vocabulary so the ARE plugin suite presents one consistent operating environment.

## Core palette

- Deep navy: `#071522`
- Secondary navy: `#0B1F33`
- ARE blue: `#0B3A63`
- Gold: `#D1A54A`
- Gold light: `#F0D99A`
- Teal: `#0F8F83`
- Teal light: `#36C2B4`
- Main background: `#F4F7FA`
- White surface: `#FFFFFF`
- Dark surface: `#0E192D`
- Primary text: `#071522`
- Secondary text: `#5E6B78`
- Border: `#DCE4EA`
- Success: `#2E8B72`
- Warning: `#C99635`
- Critical: `#B84A4A`

## Shared components

Companion plugins may use these classes for predictable styling:

- `.are-ui-card`
- `.are-ui-panel`
- `.are-ui-grid`
- `.are-ui-kpi-grid`
- `.are-ui-kpi`
- `.are-ui-kpi__value`
- `.are-ui-kpi__label`
- `.are-ui-toolbar`
- `.are-ui-badge`
- `.are-ui-status`
- `.are-ui-status--success`
- `.are-ui-status--warning`
- `.are-ui-status--critical`
- `.are-ui-status--neutral`
- `.are-ui-progress`
- `.are-ui-progress__bar`
- `.are-ui-skeleton`
- `.are-ui-empty`
- `.are-ui-button--primary`
- `.are-ui-button--secondary`

The UI layer also styles standard WordPress `.postbox`, `.card`, `.widefat`, `.form-table`, buttons, notices, tabs, and existing ARE classes such as `.algq-kpi` and `.algq-platform-kpis` when they appear on an ARE screen.

## Motion

Motion is operational and restrained:

- card entrance: approximately 420 ms;
- hover lift: approximately 240 ms;
- button response: approximately 180 ms;
- progress fill: approximately 550 ms;
- live-status pulse only when an element intentionally uses `data-are-live="1"`;
- skeleton loading only when `.are-ui-skeleton` is intentionally rendered.

The shared JavaScript never treats motion as a business-state change. It does not mutate authoritative records or infer operational status.

## Accessibility

- `prefers-reduced-motion: reduce` disables nonessential animation and transitions.
- Keyboard focus uses a visible teal focus ring.
- Status colors must not be the sole source of meaning; plugins should include text labels.
- Responsive tables remain horizontally scrollable on narrow screens.
- Existing WordPress capability and record-level authorization rules remain unchanged by the UI layer.

## Screen scope

The Platform applies the shared admin assets only when the current WordPress screen contains an ARE identifier such as `algq`, `algonquian`, or `are-` in the page, post type, taxonomy, screen ID, parent base, or hook suffix.

Companion plugins may extend or suppress this decision with:

```php
add_filter(
    'algq_admin_ui_is_are_screen',
    static function ( bool $is_are_screen, array $context ): bool {
        return $is_are_screen;
    },
    10,
    2
);
```

This keeps unrelated WordPress and third-party plugin screens outside the ARE design layer.

## Widget standard

Dashboard widgets should use a consistent information hierarchy:

1. label or operational eyebrow;
2. primary metric or status;
3. context/comparison where applicable;
4. explicit next action when actionable;
5. last-updated/source context when material.

Recommended examples:

- Pipeline: active deals, stage counts, overdue next actions, closings.
- MAO: scenario result, assumption status, risk flag, approval state.
- Funding: required capital, committed capital, gap, deadline.
- Automation: active rules, queued runs, failures, last event.
- Documents: required, received, pending approval, expiring.
- Stewardship: active properties, visits due, open concerns, vendor actions.
- Command Center: leads, qualified deals, offers, contracts, capital, closing, revenue, exceptions.

## Release requirement

A plugin admin screen is not UI-complete until it has been checked in desktop and mobile layouts for:

- branded header;
- KPI/widget presentation;
- tables and forms;
- buttons and tabs;
- loading/empty/error/access states where applicable;
- focus visibility;
- reduced-motion behavior;
- no data-ownership changes introduced by presentation code.
