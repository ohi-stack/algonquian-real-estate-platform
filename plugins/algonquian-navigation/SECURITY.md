# Security Policy — Algonquian Navigation

Algonquian Navigation is a presentation and routing layer. It must never be treated as an authorization system.

## Security requirements

- All rendered URLs use WordPress URL escaping.
- All rendered labels and attributes use output escaping.
- Navigation schema filters may change labels and routes but do not grant capabilities.
- Buyer, client, document, funding, CRM, underwriting, offer, automation, commerce, and administrative destinations must enforce their own authentication, capability, and record-level authorization rules.
- The plugin does not store passwords, API keys, payment data, personal financial information, or transaction records.
- The plugin does not expose administrative data merely because a link is hidden or shown.
- No unauthenticated mutation endpoint is registered by this plugin.

## Accessibility and interaction safety

- Mobile and desktop controls synchronize `aria-expanded` with visible state.
- Escape closes open navigation state.
- Mobile focus is contained while the drawer is open.
- Closing the mobile drawer resets expanded submenus.
- Reduced-motion preferences are honored.

## Reporting

Security issues should be reported privately through the Algonquian Real Estate Technology Division rather than disclosed in public issue comments when doing so would expose an active vulnerability.
