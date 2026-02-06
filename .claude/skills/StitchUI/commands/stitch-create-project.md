---
name: stitch-create-project
description: Create a new Google Stitch project container for UI designs
arguments:
  - name: name
    description: The name or description of your project
    required: true
    type: string
examples:
  - "/stitch-create-project Admin Dashboard"
  - "/stitch-create-project E-commerce Site"
  - "/stitch-create-project Task Management App"
---

# Create Stitch Project

Creates a new project container in Google Stitch to organize your UI designs.

## Usage

```
/stitch-create-project "Project Name"
```

## What It Does

1. **Creates a project** in your Google Stitch account
2. **Returns a project ID** (e.g., `5977622028093387`)
3. **Enables screen generation** within this project

## Example

```
User: /stitch-create-project "E-commerce Admin Panel"

Claude: Project created: projects/5977622028093387
        Title: E-commerce Admin Panel

        Ready to generate screens!
        Use: /stitch-generate "Your UI description"
```

## Next Steps

After creating a project:

1. **Generate screens**: `/stitch-generate "Your UI description"`
2. **List screens**: `/stitch-list PROJECT_ID`
3. **Get details**: `/stitch-get PROJECT_ID`

## Project Management

- One project can contain multiple screens
- Use descriptive names to organize designs
- All screens in a project share the same theme

## Related Commands

- `/stitch-generate` - Create a screen in this project
- `/stitch-list` - View all screens in project
- `/stitch-replicate` - Download and code the design
