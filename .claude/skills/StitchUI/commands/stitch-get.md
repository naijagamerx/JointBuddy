---
name: stitch-get
description: Get details of a Stitch project or screen including HTML download URL
arguments:
  - name: identifier
    description: Project ID or "PROJECT_ID SCREEN_ID" for specific screen
    required: true
    type: string
examples:
  - "/stitch-get 5977622028093387"
  - "/stitch-get 5977622028093387 60c9d74d30ea470493da7448f5d54e72"
---

# Get Stitch Details

Retrieves detailed information about a Stitch project or screen, including the HTML download URL.

## Usage

```
/stitch-get PROJECT_ID
/stitch-get "PROJECT_ID SCREEN_ID"
```

## Examples

### Get Project
```
User: /stitch-get 5977622028093387

Claude: Project: Task Planning Board
        Screens: 2
        Created: 2026-01-28
```

### Get Specific Screen
```
User: /stitch-get "5977622028093387 60c9d74d30ea470493da7448f5d54e72"

Claude: Screen: Kanban Task Planning Board
        Device: DESKTOP (2560x2048)
        HTML: https://contribution.googleusercontent.com/...
        Screenshot: https://lh3.googleusercontent.com/...
```

## What You Get

- **Project**: Name, screen count, creation date
- **Screen**: Title, device type, dimensions
- **downloadUrl**: Link to the generated HTML
- **screenshotUrl**: Link to preview image

## After Getting Details

1. **Download HTML**: Navigate to `downloadUrl` in browser
2. **View screenshot**: Open `screenshotUrl`
3. **Replicate**: Use `/stitch-replicate` for automatic coding

## Related Commands

- `/stitch-list` - List all screens in project
- `/stitch-screenshot` - Get just the screenshot URL
- `/stitch-replicate` - Download and code automatically
