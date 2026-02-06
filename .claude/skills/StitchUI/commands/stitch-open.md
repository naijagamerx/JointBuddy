---
name: stitch-open
description: Open a Stitch project or design in the browser
arguments:
  - name: identifier
    description: Project ID, screen ID, or URL
    required: true
    type: string
examples:
  - "/stitch-open 5977622028093387"
  - "/stitch-open https://stitch.withgoogle.com/projects/5977622028093387"
  - "/stitch-open 60c9d74d30ea470493da7448f5d54e72"
---

# Open in Browser

Opens a Stitch project or design in your browser for preview.

## Usage

```
/stitch-open PROJECT_ID
/stitch-open "https://stitch.withgoogle.com/..."
/stitch-open "SCREEN_URL"
```

## Examples

### Open Project
```
User: /stitch-open 5977622028093387

Claude: Opening https://stitch.withgoogle.com/projects/5977622028093387
```

### Open Screenshot
```
User: /stitch-open https://lh3.googleusercontent.com/...

Claude: Opening screenshot in browser...
```

## What Opens

| Input | Opens |
|-------|-------|
| Project ID | Stitch project page |
| URL | Direct link |
| Screenshot URL | Preview image |

## Related Commands

- `/stitch-screenshot` - Get screenshot URL
- `/stitch-get` - Get all URLs for a design
- `/stitch-replicate` - Download and code
