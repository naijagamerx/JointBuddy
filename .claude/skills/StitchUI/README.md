# StitchUI Skill - Activation Guide

## ✅ Skills Created Successfully!

Your Stitch MCP skill is ready. Here's how to activate and use it:

---

## Step 1: Activate Skill-Power

Skill-Power enables skills to be discovered and used proactively.

```bash
# In Claude Code, type:
/skill-power activate
```

Or just reference it in conversation - when you say "use stitch" or "generate a UI", Skill-Power will detect the need and suggest using StitchUI.

---

## Step 2: Activate Skill-Knowledge

Skill-Knowledge provides guidance on when and how to use skills.

```bash
# In Claude Code, type:
/skill-knowledge activate
```

This will help you understand when to use StitchUI and provide tips.

---

## Step 3: Start Using StitchUI!

### Quick Start

```bash
# Create a project and generate a UI
/stitch-create-project "My Project"

/stitch "A dashboard with sidebar, charts, and data tables"
```

### Full Workflow

```
1. Create Project
   /stitch-create-project "Dashboard"

2. Generate UI
   /stitch "A Kanban board with 4 columns"

3. Wait 2-5 minutes

4. List screens
   /stitch-list PROJECT_ID

5. Replicate in your project
   /stitch-replicate PROJECT_ID
```

---

## Available Commands

| Command | Description | Example |
|---------|-------------|---------|
| `/stitch` | Quick generate UI | `/stitch "A login form"` |
| `/stitch-create-project` | New project | `/stitch-create-project "Admin"` |
| `/stitch-generate` | Generate screen | `/stitch-generate "Dashboard"` |
| `/stitch-list` | List project screens | `/stitch-list 123456` |
| `/stitch-get` | Get design details | `/stitch-get 123456` |
| `/stitch-screenshot` | View screenshot | `/stitch-screenshot 123456` |
| `/stitch-replicate` | Code in project | `/stitch-replicate 123456` |
| `/stitch-open` | Open in browser | `/stitch-open 123456` |

---

## Example Session

```
User: I want to create a task management UI

Assistant (Skill-Power activates):
→ Detecting need for UI generation
→ Activating StitchUI skill

User: /stitch-create-project "Task Manager"

Assistant: ✓ Project created: projects/5977622028093387

User: /stitch "A Kanban board with columns: Backlog, To Do, In Progress, Done. Each column has task cards with priority badges (Urgent/High/Medium/Low), assignee avatars, and due dates. Sidebar navigation on the left with projects list."

Assistant: Generating... (2-5 minutes)
→ Screen created: projects/5977622028093387/screens/abc123

User: /stitch-replicate 5977622028093387

Assistant: ✓ Downloading HTML...
✓ Creating views/kanban-board.php
✓ Adding drag-drop functionality...

View: http://localhost/taskmanager/?page=kanban
```

---

## File Structure

```
.claude/skills/StitchUI/
├── SKILL.md                      # Main skill documentation
└── commands/
    ├── stitch.md                 # Quick generate command
    ├── stitch-create-project.md  # Create project
    ├── stitch-generate.md        # Generate from prompt
    ├── stitch-list.md            # List screens
    ├── stitch-get.md             # Get details
    ├── stitch-screenshot.md      # View screenshot
    ├── stitch-replicate.md       # Code in project
    └── stitch-open.md            # Open in browser
```

---

## MCP Tools Used

The skill uses these MCP tools:

- `mcp__stitch__create_project` - Create project
- `mcp__stitch__list_projects` - List your projects
- `mcp__stitch__get_project` - Get project details
- `mcp__stitch__generate_screen_from_text` - Generate UI
- `mcp__stitch__list_screens` - List screens
- `mcp__stitch__get_screen` - Get screen + HTML

---

## Tips for Best Results

1. **Be specific** in prompts - describe layout, colors, components
2. **Wait for generation** - takes 2-5 minutes
3. **Iterate** - generate, view, refine prompt, regenerate
4. **Use replicate** - automatically adds drag-drop, search, modals

---

## Skill Metadata

```yaml
name: StitchUI
version: 1.0.0
description: AI-powered UI generation using Google Stitch MCP
supported_languages: [HTML, CSS, JavaScript, Tailwind, PHP]
files_to_manage: [.stitch-ui/*, stitch-*.html]
```

---

## Next Steps

1. Try: `/stitch "A login page with email and password"`
2. View: `/stitch-screenshot PROJECT_ID`
3. Replicate: `/stitch-replicate PROJECT_ID`

Happy UI generating! 🎨
