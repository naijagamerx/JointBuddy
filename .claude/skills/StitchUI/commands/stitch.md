---
name: stitch
description: Shortcut to generate a UI screen from text description (alias for stitch-generate)
arguments:
  - name: prompt
    description: Description of the UI you want to generate
    required: true
    type: string
options:
  - name: device
    description: Device type (MOBILE, DESKTOP, TABLET)
    default: DESKTOP
examples:
  - "/stitch A login form with email and password"
  - "/stitch An analytics dashboard --device DESKTOP"
  - "/stitch A Kanban board --device DESKTOP"
---

# Generate UI (Short)

Quick command to generate a UI design from a text description using Google Stitch.

## Usage

```
/stitch "Your UI description"
/stitch "Description" --device MOBILE
```

## Examples

```
/stitch A login form with email, password, and submit button
/stitch An analytics dashboard with charts and tables
/stitch A task board with draggable cards --device DESKTOP
/stitch A mobile shopping cart --device MOBILE
```

## Full Workflow

1. **Generate**: `/stitch "Your prompt"`
2. **Wait**: 2-5 minutes for AI generation
3. **Check**: `/stitch-list PROJECT_ID`
4. **Replicate**: `/stitch-replicate PROJECT_ID`

## Tips

- Be specific about layout and components
- Mention style preferences (modern, minimal, etc.)
- Include any interactive elements needed

## Related Commands

- `/stitch-create-project` - Create a project first
- `/stitch-list` - Check generated screens
- `/stitch-replicate` - Download and code
- `/stitch-open` - View in browser
