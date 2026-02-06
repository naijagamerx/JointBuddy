---
name: StitchUI
description: AI-powered UI generation using Google Stitch MCP. Generates production-ready UI designs from text descriptions, downloads the HTML, and integrates designs into your project. Perfect for creating dashboards, forms, tables, and any UI component.
supported_languages: [HTML, CSS, JavaScript, Tailwind, React, Vue, PHP]
files_to_manage: [.stitch-ui/*, stitch-*.html, sample/*]
author: Claude Code Assistant
version: 1.1.0
---

# StitchUI - AI UI Generator

Generates production-ready UI designs using Google Stitch MCP. Describe what you want in plain English, and Stitch creates the actual UI code.

## When to Use

Use StitchUI when:
- User says "generate a UI for..."
- User wants to create a new page or component
- User needs a dashboard, form, table, or modal
- User wants to replicate a design from Stitch
- Building new features with visual interfaces

## MCP Tools Available

### Core Generation Tools
| Tool | Purpose | Returns |
|------|---------|---------|
| `mcp__stitch__create_project` | Create a new project container | Project ID |
| `mcp__stitch__list_projects` | List all your Stitch projects | Project list |
| `mcp__stitch__get_project` | Get project details | Project metadata |
| `mcp__stitch__generate_screen_from_text` | Generate UI from text prompt | Generation status |
| `mcp__stitch__list_screens` | List screens in a project | Screen list |
| `mcp__stitch__get_screen` | **Get screen details** | Metadata + `downloadUrl` |

### Browser Tools (for viewing)
| Tool | Purpose |
|------|---------|
| `mcp__chrome-devtools__navigate_page` | Open Stitch project or generated page |
| `mcp__chrome-devtools__take_screenshot` | Capture screenshot of design |
| `mcp__chrome-devtools__take_snapshot` | Get page structure |

## ⚠️ CRITICAL: How to Retrieve HTML Code

The `get_screen` MCP tool does NOT return HTML directly! It returns **metadata with a downloadUrl**. You MUST use `curl` to fetch the actual HTML.

### Correct Workflow

```bash
# Step 1: Get screen details (returns downloadUrl)
mcp__stitch__get_screen --projectId "10773818954716616028" --screenId "c51a7946cb2345d28b47e67b1452ba17"

# Returns:
# {
#   "htmlCode": {
#     "downloadUrl": "https://contribution.usercontent.google.com/download?c=...",
#     "name": "projects/.../files/..."
#   },
#   "title": "Habit Tracker Analytics View",
#   ...
# }

# Step 2: Download HTML using curl (NOT WebFetch!)
curl -s "DOWNLOAD_URL" > sample/habit-analytics-from-mcp.html
```

### Why This Matters

| Method | Works? | Result |
|--------|--------|--------|
| `mcp__stitch__get_screen` alone | ❌ | Returns only metadata (URL, not HTML) |
| `WebFetch` on downloadUrl | ⚠️ | May return summarized/different version |
| `curl -s "URL"` | ✅ | Returns exact raw HTML from Stitch |

## Complete Workflow

### Step 1: Create a Project
```
/stitch-create-project "My Dashboard Project"
```
Returns: Project ID (e.g., `5977622028093387`)

### Step 2: Generate a Screen
```
/stitch-generate "A Kanban board with 4 columns..."
```
- **deviceType**: `MOBILE`, `DESKTOP`, or `TABLET` (default: DESKTOP)
- **modelId**: `GEMINI_3_PRO` or `GEMINI_3_FLASH` (default: FLASH)

### Step 3: Wait for Generation
Generation takes 2-5 minutes. Check status with:
```
/stitch-list PROJECT_ID
```

### Step 4: Get Screen Details
```
/stitch-get PROJECT_ID SCREEN_ID
```
Returns metadata including `downloadUrl`.

### Step 5: Download HTML to /sample/
```bash
curl -s "DOWNLOAD_URL" > sample/design-name.html
```

### Step 6: Replicate in Your Project
1. HTML is saved to `/sample/`
2. Copy/adapt to `views/your-page.php`
3. Integrate with PHP layout and data

## Commands Reference

| Command | Description |
|---------|-------------|
| `/stitch "prompt"` | Generate UI from text (shortcut) |
| `/stitch-create-project "name"` | Create new Stitch project |
| `/stitch-generate "prompt"` | Generate screen from prompt |
| `/stitch-list "PROJECT_ID"` | List screens in project |
| `/stitch-get "PROJECT_ID" "SCREEN_ID"` | Get screen metadata |
| `/stitch-replicate "PROJECT_ID" "SCREEN_ID"` | Download HTML to sample/, then create in project |
| `/stitch-open "PROJECT_ID"` | Open in browser |

## Quick Retrieval Command

To quickly retrieve and save a Stitch design:

```bash
# Get screen metadata
RESULT=$(mcp__stitch__get_screen --projectId "PROJECT_ID" --screenId "SCREEN_ID")

# Extract downloadUrl (manually from result)
# Then download:
curl -s "https://contribution.usercontent.google.com/download?c=..." > sample/design-name.html
```

## Example Session

```
User: /stitch-get 10773818954716616028 c51a7946cb2345d28b47e67b1452ba17

Claude: {
  "title": "Habit Tracker Analytics View",
  "htmlCode": {
    "downloadUrl": "https://contribution.usercontent.google.com/download?c=..."
  }
}

User: (runs curl command)
curl -s "https://contribution.usercontent.google.com/download?c=..." > sample/habit-analytics.html

Claude: ✓ Saved to sample/habit-analytics.html (324 lines)
        Now replicating to views/habit-analytics.php...
        ✓ Design replicated!
```

## Prompts Best Practices

### Good Prompts
```
/stitch "A login form with email and password fields, remember me checkbox, and submit button. Modern design with shadow effects."

/stitch "An analytics dashboard showing 4 metric cards (revenue, users, orders, growth), a line chart, and a data table below. Sidebar navigation."

/stitch "A task board with draggable cards, each showing title, assignee avatar, priority badge, and due date."
```

### What to Include
- **Layout**: Sidebar, header, content area, grid, etc.
- **Components**: Cards, forms, tables, charts, buttons
- **Data**: What information to display
- **Style**: Modern, minimal, dark mode, etc.
- **Interactivity**: Drag-drop, hover effects, modals

## Integration Tips

### For PHP Projects (LazyMan)
1. Download HTML to `/sample/`
2. Copy to `views/your-page.php`
3. Wrap with PHP layout (header/footer)
4. Add PHP data bindings: `<?= $variable ?>`
5. Add CSRF tokens to forms

### File Locations
- **Raw Stitch HTML**: `/sample/design-name.html`
- **Adapted PHP view**: `views/design-name.php`
- **Screenshots**: `/sample/stitch-screenshot.png`

## Troubleshooting

### "HTML not matching what I see in Stitch"
- Use `curl -s` NOT `WebFetch`
- Check if multiple screen versions exist
- Clear browser cache

### "downloadUrl is empty"
- Screen may still be generating
- Wait 2-5 minutes and try again

### "curl command fails"
- URL may have special characters - wrap in quotes
- Check network connectivity
- Try with `-v` flag for verbose output

## Quick Reference

```bash
# Retrieve and save a Stitch design
mcp__stitch__get_screen --projectId "ID" --screenId "ID"
# Copy the downloadUrl from result
curl -s "PASTE_URL_HERE" > sample/design-name.html
```
