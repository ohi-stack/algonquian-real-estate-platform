# Protected Systems Access Control

**Repository:** `ohi-stack/algonquian-real-estate-platform`  
**Authority:** Algonquian Real Estate Platform Plugin  
**Classification:** Internal Production Security Standard

## Purpose

Operational systems containing deal, capital, underwriting, offer, document, client, or internal analytical information must not be publicly accessible merely because a WordPress page is published or a shortcode is present.

The Platform Plugin provides a centralized front-door authorization layer while each companion plugin remains responsible for its own record-level and action-level permissions.

## Security model

The protected-system gate applies two controls:

1. **Route authorization** — protected operational pages are intercepted during `template_redirect`, before the page template or page-builder content renders.
2. **Shortcode authorization** — protected operational shortcodes are independently intercepted with `pre_do_shortcode_tag`, preventing a shortcode from becoming an authorization bypass if it is embedded in another page.

Protected requests also receive:

- `DONOTCACHEPAGE`
- WordPress no-cache headers
- `X-Robots-Tag: noindex, nofollow, noarchive`
- `Referrer-Policy: same-origin`
- centralized audit logging for denied attempts

Unauthenticated users are sent to the WordPress login screen with a return URL. Authenticated users without the required capability receive HTTP 403.

## Access matrix

| System | Protected operational interfaces | Authorization basis |
|---|---|---|
| Funding Tracker | Funding overview/system route, funding dashboard, capital-source records | `view_algq_funding` or `manage_algq_funding` |
| Pipeline CRM | CRM dashboard, Kanban board, activity, legacy CRM bridge | Pipeline management/edit/create capabilities, or internal-operations view plus deal view |
| Underwriting | MAO calculator and underwriting workspace | `view_algq_underwriting`, `manage_algq_underwriting`, or approval authority |
| Offers | Offer dashboard, builder, history | Offer-management, history, create, or edit authority |
| Document operations | Document Library, PDF engine, signature archive | Document view/manage/download or PDF/signature authority |
| Admin Command Center | Executive operational dashboard | `view_algq_command_center` or `manage_algq_command_center` |
| Internal analytics | KPI, pipeline, activity, health, audit/report surfaces | Command Center, report export, audit, or system-health authority |
| Buyer portal | Buyer dashboard, assigned deals, marketplace dashboard, NDA/offer workflow | Buyer-portal or buyer-dashboard capability, or portal management authority |
| Property Stewardship portal | Client stewardship records | `view_algq_stewardship_portal` or `manage_algq_stewardship` |
| Tenant portal | Tenant operational portal | Tenant-portal capability or property/tenant management authority |
| Legacy client portal | `/client-portal/` | At least one recognized client-portal entitlement or administrator authority |

`manage_options` remains the emergency WordPress administrator override.

## Public interfaces intentionally left public

The security gate does **not** lock public-facing lead-generation or access-request entry points merely because they are related to a protected system. Examples include:

- seller/property intake
- buyer registration
- buyer login
- public service descriptions
- public plugin documentation that does not render operational data
- document-access request forms that do not disclose protected documents

Authorization must begin before private records, operational metrics, deal data, documents, or client-specific data are rendered.

## Record-level authorization remains mandatory

This platform gate does not replace companion-plugin controls. A logged-in user who may enter a portal must still be limited to records authorized for that user.

Examples:

- a buyer may only see deals assigned to that buyer and must satisfy NDA/download rules;
- a stewardship client may only see stewardship records linked to that account;
- a tenant may only see the tenant's own lease, documents, payments, and maintenance records;
- a document download must still pass document-level access control;
- a user allowed to view underwriting does not automatically have authority to approve it;
- a user allowed to view offers does not automatically have authority to send or bind Algonquian Real Estate LLC.

## Deployment verification

Before production release, test each protected system using at least these identities:

1. logged-out visitor;
2. ordinary WordPress subscriber with no Algonquian capability;
3. authorized domain user (buyer, acquisition user, analyst, offer manager, or client as applicable);
4. Algonquian Platform Manager;
5. WordPress administrator.

For every protected route verify:

- logged-out access redirects to login;
- unauthorized logged-in access returns HTTP 403;
- authorized users can render the intended interface;
- private pages are not cached;
- protected pages return noindex/noarchive headers;
- denied attempts create audit events;
- REST/admin-post/AJAX actions retain their own nonce, capability, and record-level checks;
- direct file URLs do not bypass protected delivery controls.

## Production rule

A menu item, hidden navigation link, unpublished button, or JavaScript visibility rule is never an authorization boundary. Operational access must be decided server-side on every protected request.