---
name: stitch-list
description: List all screens in a Stitch project
arguments:
  - name: projectId
    description: The Stitch project ID
    required: true
    type: string
examples:
  - "/stitch-list 5977622028093387"
  - "/stitch-list my-project-id"
---

# List Project Screens

Shows all screens created in a Google Stitch project.

## Usage

```
/stitch-list PROJECT_ID
```

## Example

```
User: /stitch-list 5977622028093387

Claude: Screens in project 5977622028093387:
        ┌─────────────────────────────────┐
        │ 1. Kanban Task Planning Board   │
        │    Device: DESKTOP (2560x2048)  │
        │    Status: Complete             │
        └─────────────────────────────────┘

        Use: /stitch-replicate 5977622028093387
```

## What It Shows

- Screen name and title
- Device type and dimensions
- Whether HTML is available
- Screenshot availability

## After Listing

1. **Get details**: `/stitch-get PROJECT_ID`
2. **View screenshot**: `/stitch-screenshot PROJECT_ID`
3. **Replicate**: `/stitch-replicate PROJECT_ID`

## Related Commands

- `/stitch-create-project` - Create a new project
- `/stitch-generate` - Add a new screen
- `/stitch-replicate` - Download and code the design
