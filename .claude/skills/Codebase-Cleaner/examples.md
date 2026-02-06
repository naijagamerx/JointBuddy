# Codebase Cleaner Examples

## Example Usage

### Triggering the Skill
```
User: clean codebase
```

### What Happens Next
```
🧹 Codebase-Cleaner Active
I'm using Codebase-Cleaner skill to clean your codebase.

Scanning project structure...
Found 12 files to move to test_delete/:

📁 Test Files (7):
   src/UserTest.php → test_delete/UserTest.php
   auth/test_login.php → test_delete/test_login.php
   tests/CalculatorTest.js → test_delete/CalculatorTest.js
   src/validate_test.py → test_delete/validate_test.py
   helpers/test_helper.php → test_delete/test_helper.php
   debug/test_output.php → test_delete/test_output.php
   api/test_endpoint.php → test_delete/test_endpoint.php

📁 Debug Files (3):
   debug/output.log → test_delete/output.log
   src/debug_helper.php → test_delete/debug_helper.php
   logs/error_log.txt → test_delete/error_log.txt

📁 Check Files (2):
   check/validate_email.php → test_delete/validate_email.php
   scripts/verify_data.py → test_delete/verify_data.py

Move these 12 files to test_delete/? (Yes/No)
```

### After User Confirms "Yes"
```
✅ Codebase Cleaning Complete

Files Moved: 12
- Test files: 7
- Debug files: 3
- Check files: 2

All files moved to: test_delete/
Ready for deletion when project is complete.

Movement Log:
✓ src/UserTest.php → test_delete/UserTest.php
✓ auth/test_login.php → test_delete/test_login.php
✓ tests/CalculatorTest.js → test_delete/CalculatorTest.js
✓ src/validate_test.py → test_delete/validate_test.py
✓ helpers/test_helper.php → test_delete/test_helper.php
✓ debug/test_output.php → test_delete/test_output.php
✓ api/test_endpoint.php → test_delete/test_endpoint.php
✓ debug/output.log → test_delete/output.log
✓ src/debug_helper.php → test_delete/debug_helper.php
✓ logs/error_log.txt → test_delete/error_log.txt
✓ check/validate_email.php → test_delete/validate_email.php
✓ scripts/verify_data.py → test_delete/verify_data.py
```

## Project Before and After

### Before Codebase Cleaning
```
my_project/
├── src/
│   ├── User.php
│   ├── UserTest.php          ← Test file mixed with production
│   ├── debug_helper.php      ← Debug file in src
│   └── validate_test.py      ← Test file in src
├── tests/
│   └── CalculatorTest.js     ← Test files scattered
├── debug/
│   └── output.log            ← Debug files everywhere
├── check/
│   └── validate_email.php    ← Check files mixed in
└── scripts/
    └── verify_data.py        ← Scripts mixed with tests
```

### After Codebase Cleaning
```
my_project/
├── src/
│   └── User.php              ← Clean production code only!
├── test_delete/              ← All test/debug files here
│   ├── UserTest.php
│   ├── debug_helper.php
│   ├── validate_test.py
│   ├── CalculatorTest.js
│   ├── output.log
│   ├── validate_email.php
│   └── verify_data.py
└── [other clean directories]
```

## Real Benefits

### Before Using Skill
- Production code mixed with test files
- Hard to find actual source code
- Debug files cluttering project
- Time wasted searching through test files
- Risk of deploying test files to production

### After Using Skill
- Clean, organized production code
- All test/debug files isolated
- Easy to delete test files when done
- Faster development workflow
- Production-ready codebase structure

## Common Scenarios

### Scenario 1: Web Development Project
```
Found 25 files to move:
- Test files: 15 (PHPUnit tests, JavaScript tests)
- Debug files: 6 (debug scripts, error logs)
- Check files: 4 (validation scripts)

Result: Clean web application ready for deployment
```

### Scenario 2: Python Data Project
```
Found 18 files to move:
- Test files: 12 (pytest files, test data)
- Debug files: 3 (debug prints, analysis logs)
- Temp files: 3 (sample datasets, examples)

Result: Clean data processing pipeline
```

### Scenario 3: API Project
```
Found 8 files to move:
- Test files: 5 (API endpoint tests)
- Debug files: 2 (request/response logs)
- Check files: 1 (parameter validation)

Result: Clean API ready for production
```

## Usage Tips

### Best Time to Use
- After completing major features
- Before code reviews
- Before deployment
- When project feels cluttered

### What Happens to Moved Files
- Files are moved (not copied)
- Original directory structure is preserved in `test_delete/`
- Files can be deleted when project is complete
- No changes to file content

### Safety Features
- Always asks for confirmation before moving
- Shows exactly what will be moved
- Maintains file permissions
- Creates movement log

This skill transforms a messy development project into a clean, production-ready codebase!