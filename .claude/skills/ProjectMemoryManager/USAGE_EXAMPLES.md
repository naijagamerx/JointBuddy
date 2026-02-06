# Project Memory Manager - Usage Examples

This document provides comprehensive examples of using the Project Memory Manager skill in various real-world scenarios.

## 🚀 Getting Started

### Initial Setup
```bash
# First time setup - initialize the memory system
/initialize-memory

# Verify everything is working
/validate-memory-file
/memory-status
```

### Basic Task Logging
```bash
# Manual logging after completing work
/log-task "Added user registration with email verification"

# With additional details
/log-task "Fixed critical bug in payment processing" --files="src/PaymentService.php" --notes="Undefined variable causing transaction failures"

# Automatic logging (skill detects task completion)
# After editing files, skill will prompt:
# Task completed: Updated User model validation
# Outcome: Success
# Files: src/Models/User.php
# Confirm logging to project_memory.md? (Yes/No)
```

## 📊 Daily Development Workflow

### Morning Session
```bash
# Check what you worked on yesterday
/show-tasks-yesterday

# Review project statistics
/memory-status

# Start new task
# ... work on feature ...
/log-task "Implemented user profile picture upload"
```

### During Development
```bash
# After fixing a bug
/log-task "Fixed SQL injection vulnerability in query builder" --files="src/Database/QueryBuilder.php" --notes="Replaced string interpolation with prepared statements"

# After implementing feature
/log-task "Added real-time notifications using WebSockets" --files="src/Services/NotificationService.php,src/Events/MessageEvent.php" --notes="Integrated with Pusher for real-time updates"

# After refactoring
/log-task "Refactored authentication middleware for better testability" --files="src/Middleware/AuthMiddleware.php,tests/AuthMiddlewareTest.php" --notes="Extracted dependencies and added interface contracts"
```

### End of Day
```bash
# Review daily progress
/show-tasks-today

# Check productivity metrics
/productivity-report --period=today

# Update any incomplete tasks
/log-task "Started API documentation (in progress)" --outcome="In Progress"
```

## 🔍 Query Examples

### Time-Based Queries
```bash
# Recent activity
/show-recent-tasks
/show-tasks-today
/show-tasks-yesterday
/show-tasks-from-last-week

# Specific date ranges
/show-tasks-october-2025
/find-tasks-from-2025-10-01-to-2025-10-21
/show-tasks-last-month
```

### File-Based Queries
```bash
# Specific files
/what-did-we-do-to-User.php
/show-tasks-for-authentication-system
/find-all-work-on-payment-processing
/list-modifications-to-API-endpoints

# File patterns
/find-tasks-for-all-php-files
/show-tasks-affecting-configuration
/list-work-on-test-files
```

### Task Type Queries
```bash
# Bug fixes
/show-all-bug-fixes
/list-recent-bug-fixes
/find-unresolved-issues

# Features
/list-feature-implementations
/show-completed-features
/find-incomplete-features

# Maintenance
/show-refactoring-tasks
/list-performance-improvements
/find-code-quality-tasks

# Deployment
/show-deployment-activities
/list-release-preparations
/find-production-issues
```

### Keyword Searches
```bash
# Technology-specific
/find-tasks-about-validation
/search-for-payment-related-work
/show-tasks-containing-authentication
/find-tasks-about-database

# Problem-specific
/search-for-security-issues
/find-performance-problems
/show-tasks-about-error-handling
```

## 📈 Real-World Scenarios

### Scenario 1: Debugging a Complex Issue
```bash
# User reports: "Login system not working"

# 1. Search for authentication-related work
/find-tasks-about-authentication

# 2. Check recent changes to login files
/what-did-we-do-to-LoginController.php
/show-tasks-for-auth-middleware

# 3. Look for recent security changes
/find-tasks-about-security
/search-for-validation-changes

# 4. Review outcome: Found that password validation was updated yesterday
# 5. Fix the issue and log the fix
/log-task "Fixed login validation issue" --files="src/Controllers/AuthController.php" --notes="Password validation was too strict, causing valid logins to fail"
```

### Scenario 2: Planning New Feature
```bash
# Planning to add two-factor authentication

# 1. Research existing authentication work
/find-tasks-about-authentication
/show-tasks-for-user-security

# 2. Check what files were involved
/what-did-we-do-to-User.php
/show-tasks-for-auth-service

# 3. Look for similar features
/find-tasks-about-notification
/search-for-email-verification

# 4. Based on history, plan implementation
# 5. Start implementation and log progress
/log-task "Started 2FA implementation" --outcome="In Progress" --files="src/Auth/TwoFactorAuth.php"
```

### Scenario 3: Code Review Preparation
```bash
# Preparing for team code review

# 1. Get overview of recent work
/show-tasks-from-last-week
/memory-status

# 2. Focus on specific changes
/show-tasks-for-api-endpoints
/find-tasks-about-database-changes

# 3. Generate summary
/analyze-development-patterns
/productivity-report --period=last_week

# 4. Log review preparation
/log-task "Prepared code review documentation" --files="docs/review-notes.md"
```

### Scenario 4: Onboarding New Developer
```bash
# Helping new team member understand project

# 1. Show project overview
/memory-status
/show-project-statistics

# 2. Focus on recent major features
/find-tasks-about-user-management
/show-tasks-for-payment-system

# 3. Explain architecture decisions
/find-tasks-about-database-design
/show-tasks-for-api-architecture

# 4. Show common patterns
/analyze-development-patterns
/show-common-task-types
```

## 🔧 Advanced Usage

### Complex Queries
```bash
# Multiple criteria
/find-tasks --outcome="success" --files="*.php" --days=7
/show-failed-tasks --from="2025-10-01"
/list-feature-implementations --quarter=4

# Pattern matching
/find-tasks-regex="validation|security"
/search-for-tasks-containing="(API|REST)"
/show-bug-fixes-for-critical-issues
```

### Analytics and Reporting
```bash
# Productivity analysis
/productivity-report --period=last_month
/analyze-development-patterns --detailed
/show-success-rate-by-task-type

# Quality metrics
/bug-frequency-analysis
/code-quality-trends
/test-coverage-impact

# Team insights (if shared)
/team-contribution-summary
/collaboration-patterns
/knowledge-sharing-metrics
```

### Memory Management
```bash
# Maintenance tasks
/validate-memory-file
/clean-duplicate-entries
/compact-memory
/optimize-search-indexes

# Archiving
/archive-old-entries --days=90
/export-memory-to-json --file="backup_20251021.json"
/import-memory-from-backup --file="restore_point.json"

# Configuration
/show-current-configuration
/update-memory-settings
/reset-memory-statistics
```

## 🎯 Integration Examples

### Git Integration
```bash
# Before committing
git status
/log-task "Prepared commit for user authentication feature" --files="src/Auth/*"

# After merging
/log-task "Merged feature branch: user-profile-enhancements"

# Tag releases
/log-task "Released version 1.2.0 with user management improvements"
```

### IDE Integration
```bash
# VS Code task integration
# Create tasks.json:
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Log Current Work",
            "type": "shell",
            "command": "echo 'Use /log-task in Claude Code to log your current work'"
        },
        {
            "label": "Show Recent Tasks",
            "type": "shell",
            "command": "echo 'Use /show-recent-tasks in Claude Code'"
        }
    ]
}
```

### Team Collaboration
```bash
# Share knowledge with team
/find-tasks-about-api-design
/export-memory-to-json --file="team_knowledge.json"

# Learn from others' work
/show-tasks-by-developer-john
/analyze-team-patterns

# Handover preparation
/find-all-work-on-critical-systems
/show-incomplete-features
/export-handover-documentation
```

## 📝 Template Examples

### Bug Fix Template
```bash
/log-task "Fixed [BUG DESCRIPTION]" \
  --files="file1.php, file2.php" \
  --notes="Root cause: [EXPLANATION]. Solution: [IMPLEMENTATION]. Impact: [AFFECTED AREAS]"
```

### Feature Template
```bash
/log-task "Implemented [FEATURE NAME]" \
  --files="files involved" \
  --notes="Requirements: [USER NEEDS]. Implementation: [TECHNICAL APPROACH]. Testing: [COVERAGE DETAILS]"
```

### Refactoring Template
```bash
/log-task "Refactored [COMPONENT/SYSTEM]" \
  --files="refactored files" \
  --notes="Reason: [WHY REFACTORED]. Changes: [WHAT CHANGED]. Benefits: [IMPROVEMENTS]"
```

## 🚨 Troubleshooting Examples

### Missing Entries
```bash
# Check if logging worked
/show-recent-tasks
/validate-memory-file

# Find lost entries
/search-for-tasks-containing="specific keyword"
/find-tasks-by-date="2025-10-21"

# Recover if needed
/import-memory-from-backup
/rebuild-memory-from-scratch
```

### Search Issues
```bash
# Query not working
/help-query-syntax
/show-search-tips

# Try alternative approaches
/find-tasks-about-related-topic
/show-tasks-for-related-files
/browse-all-tasks-by-date
```

### Performance Problems
```bash
# Slow queries
/optimize-search-indexes
/compact-memory
/archive-old-entries --days=30

# Large file issues
/check-memory-file-size
/validate-memory-file --detailed
/create-new-memory-file --archive-old
```

These examples demonstrate how the Project Memory Manager becomes an essential part of your development workflow, providing context, tracking progress, and maintaining project knowledge across sessions and team members.