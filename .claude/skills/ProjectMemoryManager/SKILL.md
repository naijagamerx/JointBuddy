---
name: ProjectMemoryManager
description: Maintains a persistent project history by logging tasks, outcomes, and user confirmations in a project_memory.md file. Supports querying past actions for context in Claude Code.
supported_languages: [Any]
files_to_manage: [project_memory.md]
author: Claude Code Assistant
version: 1.0.0
---

# Project Memory Manager

A comprehensive skill that provides persistent memory for Claude Code by maintaining a detailed task history log. This skill ensures project context is preserved across sessions, enabling Claude to reference past actions, decisions, and outcomes.

## Core Features

- **Persistent Task Logging**: Records all completed tasks with detailed information
- **User Confirmation Workflow**: Ensures accuracy through explicit user confirmation
- **Natural Language Queries**: Search and retrieve historical data using natural language
- **Structured Documentation**: Maintains well-organized project_memory.md file
- **Cross-Session Memory**: Preserves context between different Claude Code sessions
- **File Tracking**: Automatically tracks which files were modified in each task
- **Outcome Monitoring**: Records success/failure states and notes

## Usage

### Task Logging
```bash
# Automatic logging after task completion
# (Triggered automatically when tasks are completed)

# Manual logging request
/log-task "Added validation to User model"
/log-task "Fixed bug in authentication system"

# Log with specific details
/log-task "Refactored payment processing" --files="src/Payment.php,tests/PaymentTest.php" --notes="Improved performance by 20%"
```

### Querying Project History
```bash
# General queries
/show-project-history
/list-recent-tasks

# Specific date ranges
/show-tasks-from-last-week
/list-tasks-october-2025

# File-specific queries
/what-did-we-do-to-User.php
/show-tasks-for-api-endpoints

# Task type queries
/show-all-bug-fixes
/list-refactoring-tasks
/show-successful-deployments

# Search by keywords
/find-tasks-about-validation
/search-tasks-containing-payment
```

### Memory Management
```bash
/memory-status
/clean-old-entries
/archive-memory
/backup-project-memory
```

## Task Logging Workflow

### 1. Automatic Detection
The skill automatically detects when tasks are completed:
- File edits and saves
- Test executions
- Code refactoring
- Feature implementations
- Bug fixes
- Configuration changes

### 2. Task Information Collection
For each task, the skill collects:
- **Unique Task ID**: Format `TYYYYMMDD-NNN`
- **Timestamp**: Current date and time with timezone
- **Task Description**: Derived from user prompts or Claude actions
- **Outcome**: Success/failure state
- **Related Files**: List of files modified or created
- **Notes**: Additional observations and context

### 3. User Confirmation
Before logging, the skill presents a summary:
```
Task completed: Added validation to User model
Outcome: Success
Files: src/Models/User.php, tests/UserTest.php
Notes: Implemented email and phone number validation

Confirm logging to project_memory.md? (Yes/No)
```

### 4. Log Entry Format
Each entry is formatted as:
```markdown
- **Task ID**: T20251021-001
  - Date: 2025-10-21 15:11:00 SAST
  - Task Description: Added validation to User model
  - Outcome: Success
  - User Confirmation: Yes, 2025-10-21 15:12:00 SAST
  - Related Files: src/Models/User.php, tests/UserTest.php
  - Notes: Implemented email and phone number validation, PSR-12 compliant
```

## Querying Capabilities

### Natural Language Processing
The skill understands various query types:

**Date-based Queries:**
- "Show tasks from last week"
- "What did we do in October 2025?"
- "List tasks from yesterday"

**File-based Queries:**
- "What did we change in User.php?"
- "Show all tasks affecting the authentication system"
- "List modifications to API endpoints"

**Task-type Queries:**
- "Show all bug fixes"
- "List refactoring tasks"
- "What features have we implemented?"

**Keyword Searches:**
- "Find tasks about validation"
- "Search for payment-related changes"
- "Show tasks containing 'security'"

### Query Response Format
```
Found 3 tasks matching "validation":

1. T20251021-001: Added validation to User model (Success)
   Files: src/Models/User.php, tests/UserTest.php
   Date: 2025-10-21 15:11:00

2. T20251020-015: Updated form validation in ContactController (Success)
   Files: src/Controllers/ContactController.php
   Date: 2025-10-20 14:30:00

3. T20251019-008: Fixed validation bug in registration (Success)
   Files: src/Services/AuthService.php
   Date: 2025-10-19 11:45:00

Full details available in project_memory.md
```

## File Structure

### project_memory.md Organization
```markdown
# Project Memory Log

## Task Statistics
- Total Tasks: [Number]
- Tasks This Week: [Number]
- Success Rate: [Percentage]%
- Most Active Files: [List]

## Task History
- **Task ID**: T20251021-001
  - Date: 2025-10-21 15:11:00 SAST
  - Task Description: [Description]
  - Outcome: [Success/Failure]
  - User Confirmation: [Yes/No with timestamp]
  - Related Files: [List]
  - Notes: [Additional details]

## Quick Reference
### Recent Tasks
[List of 5 most recent tasks]

### Files Modified This Week
[List of files with change counts]

### Common Task Types
[Bug fixes, Features, Refactoring, etc.]
```

## Integration Features

### Automatic Context Awareness
The skill integrates with Claude Code by:
- Automatically detecting task completion events
- Providing relevant historical context for new tasks
- Suggesting related past work when appropriate
- Maintaining continuity across sessions

### Smart Suggestions
Based on project history, the skill can:
- Suggest similar approaches to current tasks
- Warn about potential issues encountered before
- Recommend files that might need modification
- Identify patterns in development workflow

### Cross-Reference Capabilities
- Links related tasks automatically
- Tracks file modification history
- Maintains dependency relationships
- Identifies recurring issues or patterns

## Advanced Features

### Memory Analytics
```bash
/show-project-statistics
/analyze-development-patterns
/productivity-report
/bug-frequency-analysis
```

### Memory Management
```bash
/archive-old-entries --older-than="3 months"
/clean-duplicate-entries
/compact-memory
/export-memory-to-json
/import-memory-from-backup
```

### Search and Filter
```bash
/search-tasks --keyword="validation" --date-range="2025-10-01:2025-10-21"
/filter-tasks --outcome="success" --files="*.php"
/find-related-tasks --task-id="T20251021-001"
```

## Error Handling

### File Access Issues
- **Permission Problems**: "Error: Cannot write to project_memory.md. Check file permissions."
- **Disk Space**: "Warning: Low disk space. Consider archiving old entries."
- **File Corruption**: "Error: project_memory.md appears corrupted. Creating backup."

### Data Integrity
- **Duplicate Prevention**: Automatic detection and handling of duplicate entries
- **ID Collision**: Automatic incrementing to avoid duplicate task IDs
- **Format Validation**: Ensures entries follow proper structure

### User Interaction
- **Unclear Descriptions**: "Please provide a clearer task description."
- **Missing Confirmation**: "Task not logged due to lack of confirmation."
- **Timeout Handling**: "Confirmation timeout. Task not logged."

## Configuration

### Skill Configuration
Create `project_memory_config.json`:
```json
{
    "timezone": "SAST",
    "auto_log": true,
    "confirmation_timeout": 60,
    "max_entries_per_query": 10,
    "archive_after_days": 90,
    "auto_backup": true,
    "include_file_stats": true,
    "track_outcomes": true,
    "smart_suggestions": true
}
```

### Customization Options
- **Timezone**: Set your preferred timezone
- **Auto-logging**: Enable/disable automatic task detection
- **Confirmation timeout**: Adjust user confirmation timeout
- **Archive settings**: Configure automatic archiving
- **Backup preferences**: Set up automatic backups

## Best Practices

### Effective Logging
1. **Be Specific**: Use clear, descriptive task names
2. **Include Context**: Add relevant notes about decisions made
3. **Track Files**: Always include modified files
4. **Regular Reviews**: Periodically review project history
5. **Consistent Format**: Maintain consistent logging habits

### Query Optimization
1. **Specific Queries**: Use precise search terms
2. **Date Ranges**: Limit searches to relevant time periods
3. **File Focus**: Query specific files when appropriate
4. **Keywords**: Use relevant technical terms
5. **Regular Cleanup**: Archive old entries to maintain performance

## Troubleshooting

### Common Issues
1. **Missing Entries**: Check if confirmation was provided
2. **Search Issues**: Verify query syntax and keywords
3. **File Permissions**: Ensure write access to project directory
4. **Large Files**: Consider archiving old entries
5. **Format Problems**: Validate entry structure

### Recovery Procedures
```bash
/validate-memory-file
/repair-memory-file
/restore-from-backup
/rebuild-indexes
```

## Examples and Templates

### Sample Entry
```markdown
- **Task ID**: T20251021-001
  - Date: 2025-10-21 15:11:00 SAST
  - Task Description: Implemented JWT authentication system
  - Outcome: Success
  - User Confirmation: Yes, 2025-10-21 15:12:00 SAST
  - Related Files: src/Services/AuthService.php, src/Middleware/JWTMiddleware.php, config/auth.php
  - Notes: Added secure token generation, 2-hour expiration, refresh token support
```

### Query Examples
- "Show me all authentication-related work"
- "What did we fix last week?"
- "List all tasks affecting User.php"
- "Find tasks that mention 'security'"
- "What features were implemented in October?"

This skill provides the persistent memory capability that transforms Claude Code from a stateless assistant into a contextual development partner that remembers your project's history and evolution.