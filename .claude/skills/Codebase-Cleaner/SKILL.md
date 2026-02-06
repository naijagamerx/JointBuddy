---
name: Codebase-Cleaner
description: Automatically activates when "clean codebase" is mentioned. Scans codebase for test, debug, and check files then moves them to test_delete folder for easy cleanup when project is complete.
supported_languages: [Any]
author: Claude Code System
version: 1.0.0
trigger_phrases: ["clean codebase", "codebase cleaning", "organize codebase", "cleanup code"]
---

# Codebase Cleaner: Move Test Files to test_delete

## 🧹 Purpose
Automatically activates when you say "clean codebase" to scan and move all test, debug, and check files to `test_delete/` folder for eventual deletion.

## 🎯 Activation
This skill automatically activates when you say:
- "clean codebase"
- "codebase cleaning"
- "organize codebase"
- "cleanup code"

## 📂 Files to Move to test_delete/

### Test Files:
- `*test*.php`, `*test*.js`, `*test*.py`
- `test_*.*` (test prefix files)
- Files in `/tests/`, `/test/` directories

### Debug Files:
- `*debug*.php`, `debug_*.*`
- `*dump*.php`, `var_dump*.*`
- `*.log`, `debug.log`, `error.log`

### Check Files:
- `*check*.php`, `check_*.*`
- `*verify*.php`, `validate_*.*`

### Analysis Files (NEW):
- `*analyze*.php`, `analyze_*.*`
- `*analysis*.php`, `analysis_*.*`
- Files with "analyze" in content or purpose

### Creation Files (NEW):
- `*create*.php`, `create_*.*`
- `*generate*.php`, `generate_*.*`
- `*build*.php`, `build_*.*`
- Files with "create", "generate", "build" in purpose

### Temporary Files:
- `*sample*.php`, `sample_*.*`
- `*example*.php`, `example_*.*`
- `*demo*.php`, `demo_*.*`
- `*temp*.php`, `temp_*.*`
- `*tmp*.php`, `tmp_*.*`

### Development Files (NEW):
- `*dev*.php`, `dev_*.*`
- `*sandbox*.php`, `sandbox_*.*`
- `*experiment*.php`, `experiment_*.*`
- `*trial*.php`, `trial_*.*`

## 🔄 7-Step Process

1. **Activation**: User says "clean codebase"
2. **Announcement**: "I'm using Codebase-Cleaner skill to clean your codebase"
3. **Intelligent Scanning**: Analyze codebase for needed vs not-needed files
4. **Smart Classification**: Categorize files by purpose and necessity
5. **Analysis Report**: Show what files are not needed and why
6. **User Confirmation**: Ask permission to move non-essential files
7. **File Movement**: Execute moves with detailed logging

## 🧠 Intelligent File Analysis

### What Gets Analyzed:
- **File Names**: Pattern matching for development-related keywords
- **File Content**: Scans file purpose and functionality
- **Directory Structure**: Identifies development vs production directories
- **File Dependencies**: Determines if files are referenced by production code
- **Import/Include Analysis**: Checks if files are actually used

### Analysis Categories:

#### NOT NEEDED (Move to test_delete):
- **Analysis Tools**: `analyze_*.php`, `analyze_*.js`, analysis scripts
- **Generation Scripts**: `create_*.php`, `generate_*.py`, build scripts
- **Testing Files**: All test-related files and directories
- **Debug Tools**: Debug scripts, dump utilities, log files
- **Development Utilities**: Sandbox files, experiment scripts
- **Temporary Files**: Samples, demos, temp data

#### KEEP (Essential for Production):
- **Core Application Files**: Main business logic
- **Configuration Files**: Database, API, environment configs
- **Public Assets**: CSS, JS, images needed by production
- **Vendor Dependencies**: Third-party libraries
- **Database Migrations**: Schema changes needed for production
- **Documentation**: README, API docs (unless they're development guides)

## 📊 Example Usage

### When Triggered:
```
User: "clean codebase"

🧹 Codebase-Cleaner Active
I'm using Codebase-Cleaner skill to clean your codebase.

Scanning project structure...
Found 15 files to move to test_delete/:

📁 Analysis Files (5):
   analyze/performance_test.php → test_delete/performance_test.php
   scripts/analyze_database.php → test_delete/analyze_database.php
   tools/analyze_code_quality.js → test_delete/analyze_code_quality.js
   analysis/seo_analyzer.py → test_delete/seo_analyzer.py
   src/analyze_user_data.php → test_delete/analyze_user_data.php

📁 Creation Files (4):
   create/sample_data.php → test_delete/sample_data.php
   scripts/generate_reports.py → test_delete/generate_reports.py
   tools/create_mock_data.js → test_delete/create_mock_data.js
   build/create_migration.php → test_delete/create_migration.php

📁 Test Files (6):
   src/UserTest.php → test_delete/UserTest.php
   auth/test_login.php → test_delete/test_login.php
   tests/CalculatorTest.js → test_delete/CalculatorTest.js
   debug/test_output.php → test_delete/test_output.php
   helpers/test_helper.php → test_delete/test_helper.php
   api/test_endpoint.php → test_delete/test_endpoint.php

📁 Debug Files (2):
   debug/output.log → test_delete/output.log
   src/debug_helper.php → test_delete/debug_helper.php

Move these 15 files to test_delete/? (Yes/No)
```

### After Completion:
```
✅ Codebase Cleaning Complete

Files Moved: 17
- Analysis files: 5
- Creation files: 4
- Test files: 6
- Debug files: 2

Analysis Summary:
- Found 5 analysis tools not needed for production
- Found 4 creation/generation scripts
- Found 6 test files scattered throughout codebase
- Found 2 debug utilities and logs

All files moved to: test_delete/
Ready for deletion when project is complete.

Movement Log:
✓ analyze/performance_test.php → test_delete/performance_test.php
✓ scripts/analyze_database.php → test_delete/analyze_database.php
✓ tools/analyze_code_quality.js → test_delete/analyze_code_quality.js
✓ analysis/seo_analyzer.py → test_delete/seo_analyzer.py
✓ src/analyze_user_data.php → test_delete/analyze_user_data.php
✓ create/sample_data.php → test_delete/sample_data.php
✓ scripts/generate_reports.py → test_delete/generate_reports.py
✓ tools/create_mock_data.js → test_delete/create_mock_data.js
✓ build/create_migration.php → test_delete/create_migration.php
✓ src/UserTest.php → test_delete/UserTest.php
... (8 more files)
```

## 🔍 Search Patterns

### File Patterns:
- `*test*` (test files)
- `*debug*` (debug files)
- `*dump*` (dump files)
- `*check*` (check files)
- `*verify*` (verify files)
- `*validate*` (validation files)
- `*analyze*` (analysis files - NEW)
- `*analysis*` (analysis tools - NEW)
- `*create*` (creation scripts - NEW)
- `*generate*` (generation scripts - NEW)
- `*build*` (build scripts - NEW)
- `*sample*` (sample files)
- `*example*` (example files)
- `*demo*` (demo files)
- `*temp*` (temporary files)
- `*tmp*` (temporary files)
- `*dev*` (development files - NEW)
- `*sandbox*` (sandbox files - NEW)
- `*experiment*` (experiment files - NEW)
- `*.log` (log files)

### Directory Patterns (NEW):
- `/analyze/` (analysis directories)
- `/create/` (creation directories)
- `/build/` (build directories)
- `/development/` (development directories)
- `/sandbox/` (sandbox directories)

### Directories to Exclude:
- `vendor/`
- `node_modules/`
- `.git/`
- `.claude/`
- `test_delete/`

## 🛡️ Safety Checks

Before Moving:
- Create `test_delete/` directory if needed
- Check for duplicate file names
- Verify write permissions
- Count files to be moved

## 📝 Simple Logging

Creates basic log of what was moved:
```
Codebase Cleaning Log - 2025-10-27 14:30:00
=========================================
Files moved to test_delete/:
✓ src/UserTest.php → test_delete/UserTest.php
✓ auth/test_login.php → test_delete/test_login.php
...
Total: 15 files moved successfully
```

## 🎯 End Goal

After running this skill:
- Production code is clean and organized
- All non-essential files (analyze, create, test, debug) are in `test_delete/`
- Intelligent analysis separates needed vs not-needed files
- You can delete the entire `test_delete/` folder when project is complete
- No more searching through development files while working on production code

## ✅ Success Criteria

- Triggers automatically on "clean codebase"
- Intelligently analyzes files to determine necessity
- Finds all analysis, creation, test, debug, and development files
- Shows clear analysis report before moving
- Gets user confirmation for intelligent classification
- Moves non-essential files to `test_delete/` successfully
- Provides detailed analysis summary and movement log

## 🔍 Enhanced Analysis Features

### Smart Content Analysis:
- Scans file content to determine purpose
- Checks if files are referenced by production code
- Identifies one-time utility scripts vs essential code
- Analyzes import/include dependencies

### Directory Intelligence:
- Recognizes development-specific directories
- Identifies temporary vs permanent directories
- Understands project structure patterns

### File Purpose Detection:
- Distinguishes between utility and core functionality
- Identifies development vs production configurations
- Separates testing tools from application logic

This skill makes it easy to keep your codebase clean by intelligently analyzing what files are actually needed for production!