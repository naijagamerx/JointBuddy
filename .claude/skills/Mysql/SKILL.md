---
name: MySQL Command-Line Helper
description: Provides a cheat sheet and a PowerShell helper script for running MySQL commands from the command line, especially for MAMP on Windows. Use this when you need to query, update, or manage a local MySQL database via the terminal.
allowed-tools: [shell]
---

# MySQL Command-Line Helper

This skill provides resources for interacting with a MySQL database from the command line, tailored for a Windows/PowerShell environment using MAMP.

## Key Resources

*   **`reference.md`**: A comprehensive cheat sheet containing common MySQL commands for viewing, selecting, updating, deleting, and importing data. It also includes a troubleshooting guide.
*   **`scripts/run-mysql.ps1`**: A PowerShell helper script to simplify executing SQL queries.

## How to Use

1.  **Consult the Cheat Sheet**: Open `reference.md` to find the command you need. Remember to replace all placeholders like `databasename`, `yourpassword`, etc., with your actual values.

2.  **Use the Helper Script (Recommended)**: To avoid typing the full path to the MySQL executable every time, you can use the helper script.

    **Example Usage:**
    ```powershell
    # First, dot-source the script to load the function into your session
    . .\scripts\run-mysql.ps1

    # Then, use the 'Invoke-MySqlQuery' function
    Invoke-MySqlQuery -Database "my_app_db" -Query "SHOW TABLES;"

    # Example with a select statement
    Invoke-MySqlQuery -Database "my_app_db" -Query "SELECT id, email FROM users;"
    ```
    Before running, you may need to edit the script to set your default MySQL path, username, and password.