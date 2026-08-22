# Codex Tasks — Algonquian Real Estate Production Synchronization

This file tracks implementation work that must remain synchronized with the canonical plugin source, the production manifest, and `docs/SHORTCODE-UI-CONTRACT.md`.

Historical Version 1 scaffolding instructions are obsolete and must not be used to reintroduce legacy shortcode names or monolithic duplicate business records.

## Task 1 — Runtime shortcode inventory

For every canonical plugin in `config/plugin-manifest.json`:

- enumerate runtime `add_shortcode()` registrations;
- classify each tag as preferred, compatibility, legacy-supported or internal/documentation-only;
- verify the registered callback is reachable from the plugin boot sequence;
- remove documentation claims for classes that exist but are never initialized;
- update package README and plugin documentation from source truth.

Acceptance criteria:

- no documented production shortcode is unregistered;
- no registered page-facing shortcode is missing from package documentation;
- compatibility tags are not presented as preferred interfaces.

## Task 2 — Meaningful shortcode rendering

Render every page-facing shortcode through WordPress in an appropriate test role.

Acceptance criteria:

- output is not empty;
- output does not contain the literal shortcode tag;
- output contains no unresolved placeholder/scaffold/TODO language;
- zero-record conditions render an intentional empty state;
- unauthorized roles receive an intentional restricted/login state;
- assets are enqueued only when needed.

## Task 3 — ARE UI synchronization

Apply `docs/SHORTCODE-UI-CONTRACT.md` and `docs/ARE-PLUGIN-UI-STANDARD.md` to every plugin-facing workspace and public form.

Acceptance criteria:

- shared navy/gold/teal/white design tokens;
- responsive desktop/tablet/mobile layout;
- accessible labels and focus behavior;
- no horizontal viewport overflow;
- operational dashboards use coherent KPI/panel/card components;
- public forms visually belong to the same Algonquian Real Estate website.

## Task 4 — Deal Intake conversion routes

Ensure the production Deal Intake renderer powers seller/property submission routes.

Required interfaces:

- `[algq_deal_intake_form]`
- `[algq_property_submission]`
- `[algq_homeowner_options]`
- `[algq_seller_portal]`

Legacy-supported interfaces may remain for compatibility, but new pages must prefer the canonical `algq_*` family.

Acceptance criteria:

- `/submit-a-property/` and `/sell-your-property/` do not expose placeholder or raw shortcode text;
- submissions create/associate the canonical deal according to the current integration contract;
- production form security gate passes.

## Task 5 — Pipeline CRM interfaces

Required preferred interfaces:

- `[algq_pipeline_dashboard]`
- `[algq_pipeline_board]`
- `[algq_pipeline_activity]`

Acceptance criteria:

- dashboard renders live canonical deal KPIs;
- board renders stages, counts, deal cards and valid empty states;
- stage transitions remain server-authorized and conflict-aware;
- activity renders durable event history;
- `[algq_pipeline_crm]` remains compatibility-only if provided by the Platform layer.

## Task 6 — Seller financing cross-plugin workflow

Preserve ownership boundaries:

- MAO Engine owns seller-financing underwriting calculations and approved scenarios;
- Offer Generator owns seller-facing proposal/term-sheet/LOI generation;
- Pipeline CRM owns deal status and negotiation lifecycle;
- Document Library owns controlled document records;
- PDF & Signature owns PDF/signature execution support;
- Funding Tracker owns operational capital/debt records after terms become real;
- Automation Engine owns reminders/events.

Acceptance criteria:

- Offer Generator imports approved MAO values instead of silently recalculating them;
- underwriting/proposal/debt records retain canonical Deal ID links;
- human approval boundaries remain enforced.

## Task 7 — Buyer and investor access flow

Implement and test:

`Investors & Capital → Buyer Registration → Account → Buyer Login → Buyer Dashboard → Authorized Marketplace`

Acceptance criteria:

- `[algq_buyer_login]` renders a real login interface;
- Buyer Portal and Marketplace share the intended buyer role/capability contract;
- registered-tier access works for eligible authenticated buyers;
- private/premium/NDA/download/offer controls remain deal-specific;
- legacy `/deal-marketplace/` and current marketplace routing are handled intentionally during migration.

## Task 8 — Document and PDF archive behavior

Acceptance criteria:

- generated PDFs remain in protected storage;
- archive metadata includes Deal ID, document UUID/version, source plugin/source record and hash where applicable;
- Media Library indexing does not bypass private authorization;
- configured archive email delivery uses the Platform Mail Gateway and avoids duplicate sends;
- large-file failure does not invalidate successful document generation.

## Task 9 — Platform forms and security

All form-owning plugins must pass the production-form gate.

Acceptance criteria:

- nonce/CSRF controls;
- server-side sanitization and validation;
- contextual escaping;
- capability/record authorization;
- explicit safe error states;
- durable audit events for material actions;
- public abuse/rate limiting;
- upload MIME/size/authorization validation.

## Task 10 — Navigation and page-generation behavior

Acceptance criteria:

- mobile hamburger opens the primary menu content in one step;
- no second "Menu" click is required merely to reveal navigation;
- desktop utility controls compact before primary navigation overflows;
- generated WPBakery pages use `[vc_column_text]...[/vc_column_text]`;
- page generation is idempotent and preserves administrator-edited content when required interface metadata remains present.

## Task 11 — WordPress installation and end-to-end test

Build installable ZIPs only from canonical unpacked source.

Run the documented release gate and then execute the complete workflow in a disposable WordPress environment:

`Seller submission → Deal → Pipeline → MAO underwriting → Offer → PDF/document → Signature → Funding/Buyer workflow → Closing status → Automation → Command Center → Audit verification`

A package may be described as production-ready only after applicable tests pass and evidence is recorded.
