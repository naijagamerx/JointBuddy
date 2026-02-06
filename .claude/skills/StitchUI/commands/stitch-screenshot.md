---
name: stitch-screenshot
description: Get the screenshot URL for a Stitch design and view it
arguments:
  - name: projectId
    description: The Stitch project ID
    required: true
    type: string
examples:
  - "/stitch-screenshot 5977622028093387"
---

# View Screenshot

Gets the screenshot URL for a Stitch design and opens it in the browser.

## Usage

```
/stitch-screenshot PROJECT_ID
```

## Example

```
User: /stitch-screenshot 5977622028093387

Claude: Screenshot URL: https://lh3.googleusercontent.com/...
        Opening in browser...

        [Screenshot displays]
```

## What It Does

1. **Fetches screen details** from Stitch MCP
2. **Extracts screenshot URL**
3. **Opens in browser** for preview
4. **Displays** the generated UI design

## After Viewing

1. **Satisfied?**: Use `/stitch-replicate` to code it
2. **Need changes?**: Refine prompt and generate again

## Related Commands

- `/stitch-generate` - Create a new design
- `/stitch-list` - See all screens
- `/stitch-replicate` - Download and code
- `/stitch-open` - Open project page
