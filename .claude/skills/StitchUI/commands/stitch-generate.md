---
name: stitch-generate
description: Generate a UI screen from a text description using Google Stitch AI
arguments:
  - name: prompt
    description: Detailed description of the UI you want to generate
    required: true
    type: string
options:
  - name: device
    description: Device type (MOBILE, DESKTOP, TABLET)
    default: DESKTOP
  - name: model
    description: AI model (GEMINI_3_FLASH, GEMINI_3_PRO)
    default: GEMINI_3_FLASH
examples:
  - "/stitch-generate A Kanban board with 4 columns"
  - "/stitch-generate A login form with email and password"
  - "/stitch-generate An analytics dashboard --device DESKTOP --model GEMINI_3_PRO"
---

# Generate UI from Text

Uses Google Stitch AI to generate a production-ready UI design from your text description.

## Usage

```
/stitch-generate "Your UI description"
/stitch-generate "Description" --device MOBILE
/stitch-generate "Description" --model GEMINI_3_PRO
```

## Options

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `--device` | MOBILE, DESKTOP, TABLET | DESKTOP | Target device type |
| `--model` | FLASH, PRO | FLASH | AI model (PRO = slower but better) |

## Examples

### Simple Form
```
/stitch-generate "A login form with email, password fields, remember me checkbox, and submit button. Modern design with shadow."
```

### Dashboard
```
/stitch-generate "An analytics dashboard with 4 metric cards at top, a line chart in the center, and a data table below. Sidebar navigation."
```

### Kanban Board
```
/stitch-generate "A Kanban board with 4 columns: Backlog, To Do, In Progress, Done. Each column has task cards with priority badges, avatars, and due dates."
```

### Mobile View
```
/stitch-generate "A mobile task list with swipe actions for complete and delete. Shows task title, due date, and priority indicator." --device MOBILE
```

## What Happens Next

1. **Generation starts** (takes 2-5 minutes)
2. **Screen is created** in your Stitch project
3. **Use `/stitch-list`** to check status
4. **Use `/stitch-replicate`** to download and code

## Tips for Good Prompts

### Include:
- **Layout**: Sidebar, header, grid, etc.
- **Components**: Cards, forms, tables, charts
- **Data**: What information to show
- **Style**: Modern, minimal, dark mode
- **Interactivity**: Hover effects, drag-drop

### Example Prompts
```
Good: "A dashboard with sidebar navigation, top bar with search, 4 stat cards with icons, main area with bar chart, and recent activity list."

Bad: "Make a dashboard"
```

## Related Commands

- `/stitch-create-project` - Create project first
- `/stitch-list` - Check generation status
- `/stitch-replicate` - Download and code
- `/stitch-screenshot` - View the design
