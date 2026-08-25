# ARE Shared Admin UI

Canonical visual and interaction layer for Algonquian Real Estate plugin admin screens.

## Scope
Apply to every ARE-owned WordPress plugin admin screen. The visual system follows `docs/ARE-PLUGIN-UI-STANDARD.md` and provides the shared classes already required there.

## Brand tokens
- Blue `#0b3d91`
- Gold `#d4af37`
- Deep navy `#0f172a`
- Charcoal `#101820`
- Teal `#167c80`
- White `#ffffff`
- Soft background `#f4f6f8`

## Motion standard
Motion is restrained and functional: entrance transitions for cards, hover elevation for actionable surfaces, a subtle decorative hero orbit, status pulse, tab/button feedback, and dismiss transitions. `prefers-reduced-motion: reduce` disables all nonessential animation.

## Integration contract
Plugins should render admin roots with `.algq-admin-shell` or `.algq-dashboard` and use the component classes defined in the UI standard. Enqueue `are-admin-ui.css` on ARE admin screens. Enqueue `are-admin-ui.js` when dark-mode toggles, tabs, dismissible notices, or export confirmation are present.

Do not create plugin-specific competing palettes. Plugin-specific CSS may handle layout or domain-specific controls, but should consume this shared visual vocabulary.

## Release migration
This directory is deliberately platform-neutral so the shared assets can be copied into the Platform/Core shared UI service during release packaging. Existing plugin screens can be migrated incrementally without changing their authoritative data or business logic.
