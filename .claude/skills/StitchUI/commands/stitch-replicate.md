---
name: stitch-replicate
description: Download HTML from Stitch, save to /sample/, then replicate in your project
arguments:
  - name: projectId
    description: The Stitch project ID
    required: true
    type: string
  - name: screenId
    description: The Stitch screen ID (optional, uses first screen if omitted)
    required: false
    type: string
options:
  - name: sample
    description: Save raw HTML to /sample/ folder first
    default: true
  - name: output
    description: Output file path (relative to project root)
    default: views/generated-ui.php
examples:
  - "/stitch-replicate 5977622028093387"
  - "/stitch-replicate 5977622028093387 abc123def456"
  - "/stitch-replicate 5977622028093387 --output views/kanban.php"
---

# Replicate Stitch Design

Downloads the HTML from a Stitch design, saves to `/sample/`, then creates a coded version in your project.

## ⚠️ Important: How It Works

This command requires **TWO steps**:

1. **Get screen metadata** using MCP `get_screen` (returns `downloadUrl`)
2. **Download HTML** using `curl -s` (NOT WebFetch!)

```bash
# WRONG - MCP only returns URL, not HTML
mcp__stitch__get_screen → NO HTML returned!

# CORRECT - Use curl to fetch from downloadUrl
curl -s "https://contribution.usercontent.google.com/download?c=..." → HTML!
```

## Usage

```
/stitch-replicate PROJECT_ID
/stitch-replicate PROJECT_ID SCREEN_ID
/ststich-replicate PROJECT_ID --output "path/to/file.php"
```

## Examples

### Basic Usage
```
User: /stitch-replicate 10773818954716616028

Claude: Getting screen details...
       Project: Monochrome Login Variant 2
       Screen: Habit Tracker Analytics View
       downloadUrl: https://contribution.usercontent.google.com/download?c=...

       Downloading HTML to sample/...
       ✓ Saved: sample/habit-analytics-from-mcp.html

       Replicating to views/habit-analytics.php...
       ✓ Done!

View: http://localhost/taskmanager/views/habit-analytics.php
```

### With Specific Screen
```
/stitch-replicate 10773818954716616028 c51a7946cb2345d28b47e67b1452ba17
```

### Custom Output
```
/stitch-replicate 5977622028093387 --output "views/kanban-board.php"
```

## What It Does

1. **Fetches screen metadata** from Stitch via MCP
2. **Extracts downloadUrl** from the response
3. **Downloads HTML** using `curl -s`
4. **Saves raw HTML** to `/sample/design-from-mcp.html`
5. **Creates PHP file** in your project
6. **Integrates** with your existing layout (if PHP)

## File Locations

```
taskmanager/
├── sample/                          # ← Raw Stitch HTML goes here
│   └── habit-analytics-from-mcp.html
├── views/                           # ← Replicated PHP files go here
│   └── habit-analytics.php
└── stitch-screenshot.png            # ← Screenshot for reference
```

## Manual Alternative

If you need more control:

```bash
# Step 1: Get screen details
mcp__stitch__get_screen --projectId "ID" --screenId "ID"

# Step 2: Copy downloadUrl from result
# Step 3: Download with curl
curl -s "PASTE_URL_HERE" > sample/my-design.html

# Step 4: Copy to views/ and adapt
cp sample/my-design.html views/my-design.php
```

## Output File Structure

```php
<?php
// views/habit-analytics.php
// Replicated from Stitch project 10773818954716616028
// Screen: Habit Tracker Analytics View
// Saved raw to: sample/habit-analytics-from-mcp.html
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- ... -->
</head>
<body class="bg-background-light ...">
    <!-- Sidebar -->
    <aside>...</aside>

    <!-- Main Content -->
    <main>...</main>
</body>
</html>
```

## Tips

1. **Always save to /sample/ first** - keeps original Stitch HTML for reference
2. **Keep screenshots** - use `/stitch-screenshot` to capture visual
3. **Review before integrating** - check colors, fonts, spacing
4. **Version control** - commit the raw HTML in sample/

## Related Commands

- `/stitch-get PROJECT_ID SCREEN_ID` - Get screen metadata only
- `/stitch-screenshot PROJECT_ID` - Capture visual screenshot
- `/stitch-list PROJECT_ID` - List all screens in project
