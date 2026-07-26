# Pipeline CRM Enterprise Page Scaffold

## Purpose

The Pipeline CRM page family uses the approved Algonquian Real Estate WPBakery visual system and creates dedicated routes for the live workspace, plugin overview, getting-started guide, and documentation.

## Generated Routes

- `/pipeline/`
- `/plugin/pipeline-crm/`
- `/plugin/pipeline-crm/start/`
- `/plugin/pipeline-crm/docs/`

## Hero Standard

- `stretch_row_content`
- Parallax media attachment ID `6422` by default
- Dark navy gradient overlay
- Centered classification badge
- 58px institutional headline
- Two `la_btn` calls to action

## Pipeline Metric Cards

1. System — Acquisition Pipeline
2. Primary Record — Canonical Deal Record
3. Interface — Kanban & Deal Workspace
4. Lifecycle — Intake Through Closing

## Getting-Started Content

The generated page covers:

- Platform and schema readiness
- Controlled stage configuration
- Granular user permissions
- First-deal creation
- Lifecycle workflow
- Required transition controls
- Daily operating practice
- Live Pipeline CRM shortcode workspace

## WPBakery Syntax Rule

Use:

```text
[vc_column_text]
Content
[/vc_column_text]
```

Never use an HTML-style `</vc_column_text>` closing tag.

## Preservation Rule

The generator does not overwrite administrator-edited pages that already contain the required module shortcode. Missing pages or pages missing the required shortcode may be generated or repaired through the platform page-generation process.
