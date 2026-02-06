# Codebase Cleaner Skill

**Purpose**: Intelligently analyzes and moves non-essential files (analyze, create, test, debug, development) to `test_delete/` folder when you say "clean codebase".

## How It Works

1. **Automatic Activation**: Say "clean codebase" to trigger
2. **Intelligent Analysis**: Scans and analyzes files to determine what's needed vs not needed
3. **Smart Classification**: Categorizes files by purpose and necessity
4. **Analysis Report**: Shows what files are not needed and why
5. **User Confirmation**: Asks permission before moving non-essential files
6. **File Organization**: Moves files to `test_delete/` folder
7. **Detailed Reporting**: Shows analysis summary and movement log

## Files Analyzed & Moved

### Analysis Files (NEW)
- `analyze_*.php`, `analyze_*.js` - Performance analysis, code analysis tools
- `analysis_*.py` - Data analysis scripts
- Files in `/analyze/` directories

### Creation/Generation Files (NEW)
- `create_*.php`, `generate_*.py` - Data creation, report generation
- `build_*.js`, `build_*.php` - Build scripts, migration creators
- Files in `/create/`, `/build/` directories

### Development Files (NEW)
- `dev_*.php`, `sandbox_*.js` - Development utilities, sandbox experiments
- `experiment_*.py`, `trial_*.js` - Experimental code, trial implementations
- Files in `/development/`, `/sandbox/` directories

### Traditional Files
- **Test Files**: Anything with "test" in the name
- **Debug Files**: Debug scripts, dump files, logs
- **Check Files**: Validation, verification, check scripts
- **Temp Files**: Samples, examples, demos, temporary files

## What Gets Kept (Essential for Production)

- Core application files and business logic
- Configuration files (database, API, environment)
- Public assets (CSS, JS, images)
- Vendor dependencies and third-party libraries
- Database migrations
- Essential documentation

## End Result

- Clean production code with only essential files
- All non-essential files intelligently categorized in `test_delete/`
- Analysis report showing why files were moved
- Ready to delete `test_delete/` when project is complete

## Usage

Just say: **"clean codebase"**

The skill will intelligently analyze your codebase and handle everything else automatically!