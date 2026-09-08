# Algonquian Real Estate Public Page Style Standard

**Owner:** Algonquian Real Estate LLC  
**Applies to:** Public WordPress/WPBakery pages, service pages, company pages, acquisition pages, investor/capital pages, Technology Division pages, property-owner pages, public plugin overview pages, and customer-facing landing pages.

## Governing Rule

New and materially revised public pages should preserve the same visual family as the current Mission and Vision reference page.

The design system is intentionally institutional, cinematic, restrained, and operational. Page-specific content, metrics, CTAs, cards, and section labels may change; the overall visual language should remain consistent.

## Canonical Hero Scaffold

Use a full-width WPBakery row with parallax imagery and a dark overlay.

Preferred structure:

```text
[vc_row full_width="stretch_row_content" parallax="content-moving" parallax_image="2036"]
[vc_column css=".vc_custom_are_page_hero{background-color:rgba(0,0,0,.47) !important;}"]
[vc_empty_space height="118px"]
[vc_column_text]
...
[/vc_column_text]
...
[/vc_column]
[/vc_row]
```

Image `2036` is the standard public/company/acquisition hero reference unless a page has an approved page-specific image. Image `6422` may be used for approved alternate closing or service-oriented hero treatments.

## Hero Treatment

Hero content should normally contain:

1. A classification badge.
2. Large white page title.
3. Gold divider.
4. Strong white lead statement.
5. Supporting paragraph in muted white.
6. Two clear CTA buttons where appropriate.
7. A four-card operational summary row where useful.

### Classification Badge

Use:

```html
<div style="display:inline-flex;padding:10px 18px;border:1px solid rgba(209,165,74,.55);border-radius:999px;background:rgba(7,21,34,.44);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px);color:#f0d99a;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">
Section • Topic • Purpose
</div>
```

### H1 Standard

```html
<h1 style="max-width:1000px;margin:26px auto 0;color:#fff;font-size:64px;font-weight:800;line-height:1.03;letter-spacing:-.038em;text-shadow:0 8px 26px rgba(0,0,0,.34);">
Page Title
</h1>
```

### Divider

```html
<div style="width:84px;height:3px;margin:25px auto;background:#d1a54a;"></div>
```

### Lead Statement

```html
<p style="max-width:940px;margin:0 auto;color:#fff;font-size:25px;font-weight:650;line-height:1.45;">
Customer-relevant primary statement.
</p>
```

### Supporting Copy

```html
<p style="max-width:900px;margin:18px auto 0;color:rgba(255,255,255,.84);font-size:18px;line-height:1.75;">
Supporting explanation.
</p>
```

## Canonical Colors

- Deep navy: `#071522`
- Secondary navy: `#0B1F33`
- ARE blue: `#0B3A63`
- Gold: `#D1A54A`
- Light gold: `#F0D99A`
- Teal: `#0F8F83`
- Light teal: `#36C2B4`
- Light neutral background: `#F4F7FA`
- Border: `#DCE4EA`
- Primary text: `#071522`
- Secondary text: `#5E6B78`
- White: `#FFFFFF`

## Section Rhythm

The standard page alternates among:

- cinematic parallax hero;
- light neutral `#f4f7fa` sections;
- white sections;
- deep navy `#071522` sections;
- final cinematic CTA hero.

Normal vertical section spacing is approximately `86px` top and bottom. Major heroes use roughly `100px` to `118px` opening spacing.

## Typography

- Main H1: approximately `64px`, weight `800`, tight tracking.
- Major H2: approximately `44px`–`50px`, weight `800`.
- Card H3: approximately `21px`–`27px`, weight `800`.
- Eyebrow/classification labels: `11px`–`12px`, weight `800`, uppercase, tracking around `.10em`–`.12em`.
- Standard body: `17px`–`20px`, line-height about `1.75`–`1.8`.
- Small card body: approximately `14px`, line-height `1.7`–`1.8`.

## Card System

### Light cards

```html
<div style="height:100%;padding:31px;background:#f4f7fa;border:1px solid #dce4ea;border-radius:20px;">
...
</div>
```

### Dark operational cards

```html
<div style="height:100%;padding:36px;border:1px solid rgba(209,165,74,.24);border-radius:22px;background:rgba(209,165,74,.06);">
...
</div>
```

### Glass hero cards

```html
<div style="height:100%;padding:24px 18px;border:1px solid rgba(209,165,74,.28);border-radius:18px;background:rgba(7,21,34,.48);backdrop-filter:blur(8px);text-align:center;">
...
</div>
```

Gold, teal, blue-gray, and white accents may distinguish the cards, but the number of competing accent colors should remain restrained.

## CTA Standard

Prefer paired CTA buttons in centered half-width columns:

```text
[vc_row_inner]
[vc_column_inner width="1/2"]
[la_btn title="Primary Action" shape="rounded" color="primary" size="lg" align="right" link="url:/target/"]
[/vc_column_inner]
[vc_column_inner width="1/2"]
[la_btn title="Secondary Action" style="outline" shape="rounded" size="lg" align="left" link="url:/target/"]
[/vc_column_inner]
[/vc_row_inner]
```

Public CTA language should follow the customer-intent standard: use specific actions such as `See My Options`, `Submit My Property`, `Request Property Support`, or `Discuss a Lending Relationship` instead of generic `Learn More` when a more concrete action exists.

## Customer-First Content Requirement

The visual system and customer-voice system operate together.

A page may retain the formal ARE service title, but its hero, lead statement, body copy, FAQ, CTA, and supporting sections should first help the visitor recognize their situation.

Examples:

- Inherited Property Guidance → `I inherited a house. What do I do now?`
- Sell As-Is → `I need to sell without making repairs first.`
- Property Stewardship → `Who can keep an eye on my property when I cannot be there?`
- Seller Financing → `Can I sell and receive payments over time?`

See `docs/CUSTOMER-VOICE-AND-SEARCH-INTENT-STANDARD.md` and `config/customer-intent-map.json`.

## Operating-Priority Sections

Pages that explain company strategy, services, technology, or operating systems may use three-column or four-column priority cards to communicate:

- Real Estate First
- Revenue First
- Automation First
- Property / Operations / Relationships
- Understand / Organize / Watch / Decide

Use page-specific labels where appropriate while preserving the same structural hierarchy.

## Technology Positioning

ARE Tech should be framed as an internal operating and commercialization capability, not as a substitute for the real-estate business.

Technology content should answer whether the system:

- reduces labor;
- improves conversion;
- protects records;
- advances a transaction;
- lowers costs;
- strengthens compliance;
- improves reporting; or
- creates reusable commercial value.

## Human Authority / AI Positioning

When AI or automation is discussed publicly, preserve the distinction:

**Systems support:** research, organization, analysis, drafting, workflow routing, monitoring, documentation, and approved automation.

**Human authority:** negotiations, final offers, contracts, legal decisions, capital commitments, movement of funds, transaction approval, and closing.

## Milestone / Roadmap Sections

Six-column milestone rows may be used for concise progression:

`Foundation → First Deal → Track Record → Capital → Portfolio → Scale`

Future-state language must be clearly distinguished from completed results.

## Disclosure Panels

Use subtle bordered light panels or dark glass panels for forward-looking statements, legal/professional boundaries, or other compliance-sensitive material.

Forward-looking statements must not imply completed acquisitions, portfolio ownership, secured financing, investment commitments, revenue, or operating milestones unless separately documented.

## Closing Hero

Major pages should normally end with a cinematic final CTA section using the same hero family, often with image `6422` or another approved image.

Closing content should include:

- page-specific badge;
- strong H2;
- gold divider;
- concise next-step copy;
- paired CTAs;
- compliance/disclosure panel where appropriate.

## WPBakery Syntax Rule

Always use:

```text
[vc_column_text]
...
[/vc_column_text]
```

Never use `</vc_column_text>`.

All quote characters in actual WordPress content should be normal straight shortcode-compatible quotes, even if source material was pasted with smart quotes.

## Production Acceptance

A public ARE page passes the style standard when:

- it visually belongs to the same ARE design family;
- the first screen communicates the visitor's situation and page purpose;
- typography and spacing match the institutional hierarchy;
- navy, gold, teal, white, and neutral colors are used consistently;
- cards and CTAs use the standard component treatments;
- WPBakery nesting is valid;
- mobile and desktop layouts remain readable;
- customer-facing language follows the customer-intent standard;
- forward-looking claims are clearly qualified; and
- page-specific content changes do not create a new visual language.
