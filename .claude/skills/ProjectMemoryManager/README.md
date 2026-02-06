# Project Memory Manager Skill

A comprehensive persistent memory solution for Claude Code that maintains detailed task history, enabling contextual development across sessions. This skill transforms Claude Code from a stateless assistant into a contextual development partner that remembers your project's evolution.

## 🚀 Quick Start

### Basic Usage
```bash
# Initialize project memory (first time setup)
/initialize-memory

# Log a completed task
/log-task "Added user authentication to login system"

# Query project history
/show-project-history
/what-did-we-do-last-week
/find-tasks-about-authentication

# Get project statistics
/memory-status
/show-project-statistics
```

### Automatic Integration
The skill automatically detects when tasks are completed and prompts for logging:
```
Task completed: Implemented JWT authentication
Outcome: Success
Files: src/Auth/JWTService.php, src/Middleware/AuthMiddleware.php
Confirm logging to project_memory.md? (Yes/No)
```

## 📋 Features

### 🔄 Persistent Memory
- **Cross-Session Context**: Maintains project history across Claude Code sessions
- **Task Logging**: Detailed records of all completed work with timestamps
- **File Tracking**: Automatically tracks which files were modified
- **Outcome Monitoring**: Records success/failure states and notes

### 🔍 Smart Querying
- **Natural Language**: Search using everyday language
- **Date Ranges**: Query by time periods (last week, yesterday, October 2025)
- **File-Based**: Find all work done on specific files
- **Keyword Search**: Search by task types, technologies, or keywords

### 📊 Analytics & Insights
- **Project Statistics**: Success rates, activity patterns, file frequency
- **Trend Analysis**: Track development velocity and quality metrics
- **Memory Management**: Archive old entries, maintain performance
- **Smart Suggestions**: Context-aware recommendations based on history

## 🎯 Usage Examples

### Task Logging
```bash
# Manual logging with details
/log-task "Fixed validation bug in User model" --files="src/Models/User.php" --notes="Undefined variable $userData causing runtime errors"

# Automatic logging after file edits
# (Skill detects file changes and prompts for confirmation)
```

### Project Queries
```bash
# Time-based queries
/show-tasks-from-last-week
/what-did-we-do-in-october
/list-tasks-yesterday

# File-specific queries
/what-did-we-change-in-User.php
/show-tasks-for-authentication-system
/find-all-work-on-api-endpoints

# Task-type queries
/show-all-bug-fixes
/list-feature-implementations
/find-refactoring-tasks
/show-deployment-activities

# Keyword searches
/find-tasks-about-validation
/search-for-payment-related-work
/show-tasks-containing-security
```

### Memory Management
```bash
/memory-status                # Show current statistics
/validate-memory-file        # Check file integrity
/archive-old-entries         # Clean up old records
/export-memory-to-json       # Backup data
/import-memory-from-backup   # Restore data
```

## 📁 File Structure

### project_memory.md Format
```markdown
# Project Memory Log

## Task Statistics
- Total Tasks: 47
- Tasks This Week: 8
- Success Rate: 94%
- Most Active Files: src/Models/User.php, src/Controllers/AuthController.php

## Task History
- **Task ID**: T20251021-001
  - Date: 2025-10-21 15:11:00 SAST
  - Task Description: Added JWT authentication system
  - Outcome: Success
  - User Confirmation: Yes, 2025-10-21 15:12:00 SAST
  - Related Files: src/Auth/JWTService.php, src/Middleware/AuthMiddleware.php
  - Notes: Implemented secure token generation with 2-hour expiration

- **Task ID**: T20251021-002
  - Date: 2025-10-21 14:30:00 SAST
  - Task Description: Fixed undefined variable in User model
  - Outcome: Success
  - User Confirmation: Yes, 2025-10-21 14:31:00 SAST
  - Related Files: src/Models/User.php
  - Notes: Variable $userData was undefined, causing runtime errors

## Quick Reference
### Recent Tasks
1. T20251021-001: Added JWT authentication system
2. T20251021-002: Fixed undefined variable in User model
3. T20251020-015: Updated email validation

### Files Modified This Week
- src/Models/User.php (3 changes)
- src/Auth/JWTService.php (2 changes)
- src/Controllers/AuthController.php (1 change)
```

## ⚙️ Configuration

### project_memory_config.json
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

### Environment Variables
```bash
# Customize behavior
MEMORY_TIMEZONE=UTC
MEMORY_AUTO_LOG=false
MEMORY_CONFIRM_TIMEOUT=120
MEMORY_MAX_RESULTS=20
```

## 🔧 Advanced Features

### Pattern Recognition
The skill automatically identifies:
- **Recurring Issues**: Problems that appear multiple times
- **Success Patterns**: Approaches that consistently work
- **File Relationships**: Files that are frequently modified together
- **Development Rhythms**: Peak productivity times and patterns

### Smart Suggestions
Based on project history:
- **Similar Solutions**: Suggests approaches from similar past tasks
- **Related Files**: Recommends files that might need modification
- **Warning System**: Alerts about potential issues encountered before
- **Workflow Optimization**: Suggests improvements to development process

### Integration Features
- **Git Integration**: Correlates with git commits and branches
- **IDE Integration**: Works with VS Code, PhpStorm, etc.
- **CI/CD Integration**: Connects with deployment pipelines
- **Team Collaboration**: Share memory across team members

## 📈 Analytics Dashboard

### Project Metrics
```bash
/show-project-statistics
# Output:
# 📊 Project Statistics
# Total Tasks: 47
# Tasks This Week: 8
# Success Rate: 94%
# Most Active Files: src/Models/User.php (3), src/Auth/JWTService.php (2)
# Average Tasks Per Day: 2.3
# Current Streak: 5 days
```

### Development Insights
```bash
/analyze-development-patterns
# Output:
# 📈 Development Pattern Analysis
# Peak Productivity: 14:00-16:00
# Most Productive Day: Tuesday
# Common Task Types: Bug fixes (40%), Features (35%), Refactoring (25%)
# Average Task Duration: 45 minutes
```

## 🎨 Best Practices

### Effective Logging
1. **Be Specific**: Use clear, descriptive task descriptions
2. **Include Context**: Add notes about decisions and approaches
3. **Track Files**: Always include all relevant files
4. **Regular Reviews**: Weekly review of project history
5. **Consistent Format**: Maintain uniform logging habits

### Query Optimization
1. **Specific Searches**: Use precise terms and dates
2. **Combine Filters**: Use multiple criteria for better results
3. **Date Ranges**: Limit searches to relevant time periods
4. **File Focus**: Query specific files when investigating issues
5. **Keyword Strategy**: Use consistent terminology

### Memory Maintenance
1. **Regular Cleanup**: Archive old entries monthly
2. **Backup Strategy**: Export memory before major changes
3. **Validation**: Check file integrity weekly
4. **Performance**: Monitor file size and query speed
5. **Documentation**: Keep configuration updated

## 🔍 Troubleshooting

### Common Issues

**Missing Entries**
```bash
# Check if confirmation was provided
/search-tasks --recent
# Verify file permissions
/memory-status
```

**Search Problems**
```bash
# Validate query syntax
/help-query-syntax
# Check file integrity
/validate-memory-file
```

**File Permission Issues**
```bash
# Check permissions
ls -la project_memory.md
# Fix permissions if needed
chmod 644 project_memory.md
```

**Performance Issues**
```bash
# Archive old entries
/archive-old-entries --days=60
# Check file size
/memory-status
```

### Recovery Procedures
```bash
# Validate and repair
/validate-memory-file
/repair-memory-file

# Restore from backup
/import-memory-from-backup --file="backup_20251021.json"

# Rebuild if corrupted
/rebuild-memory-from-scratch
```

## 🧪 Testing

### Test the Installation
```bash
# Initialize if first time
/initialize-memory

# Test logging
/log-task "Test: Memory Manager functionality"

# Test querying
/show-recent-tasks
/find-tasks-about-test

# Validate setup
/validate-memory-file
```

### Sample Workflow
```bash
# 1. Complete a task
# (Edit files, implement feature, etc.)

# 2. Log the task
/log-task "Implemented user registration with email verification"

# 3. Review the log
/show-recent-tasks

# 4. Query related work
/find-tasks-about-user-registration

# 5. Check statistics
/memory-status
```

## 📚 Integration Examples

### Git Hooks
```bash
# .git/hooks/post-commit
#!/bin/bash
echo "📝 Consider logging recent changes with /log-task"
```

### VS Code Tasks
```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Log Current Work",
            "type": "shell",
            "command": "echo 'Use /log-task in Claude Code to log your work'"
        }
    ]
}
```

### CI/CD Integration
```yaml
# .github/workflows/memory-update.yml
- name: Update Project Memory
  run: |
    if [ "$GITHUB_EVENT_NAME" = "push" ]; then
      echo "Deployment completed. Consider logging with /log-task"
    fi
```

## 🎖️ Advanced Usage

### Custom Queries
```bash
# Complex date ranges
/show-tasks --from="2025-10-01" --to="2025-10-21"

# Multiple file filters
/find-tasks --files="User.php,AuthService.php"

# Outcome-based filtering
/show-failed-tasks
/show-successful-tasks

# Combine filters
/find-tasks --outcome="success" --files="*.php" --days=7
```

### Memory Analytics
```bash
# Productivity analysis
/productivity-report --period="last_month"

# Bug tracking
/bug-frequency-analysis --period="quarter"

# Feature tracking
/feature-completion-rate

# Team metrics (if shared)
/team-contribution-summary
```

This skill provides the persistent memory foundation that enables Claude Code to become a true development partner with full project context and historical awareness.