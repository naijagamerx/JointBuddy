# PowerShell Helper Script for MySQL Commands
#
# This script provides a function to simplify running SQL queries from the PowerShell command line,
# especially for local development environments like MAMP or XAMPP.
#
# --- HOW TO USE ---
# 1. EDIT the variables in the "CONFIGURATION" section below to match your local setup.
#
# 2. LOAD THE FUNCTION into your current PowerShell session by "dot-sourcing" the script:
#    . .\scripts\run-mysql.ps1
#
# 3. CALL THE FUNCTION with your database and query:
#    Invoke-MySqlQuery -Database "my_database" -Query "SHOW TABLES;"
#    Invoke-MySqlQuery -Database "my_database" -Query "SELECT * FROM users LIMIT 5;"
#
# 4. (OPTIONAL) To make the function always available, add the dot-sourcing command
#    from step 2 to your PowerShell profile script.

function Invoke-MySqlQuery {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory=$true)]
        [string]$Database,

        [Parameter(Mandatory=$true)]
        [string]$Query
    )

    # --- CONFIGURATION ---
    # Edit these variables to match your MySQL environment.
    $mysqlPath = "C:\MAMP\bin\mysql\bin\mysql.exe"
    $mysqlUser = "root"
    $mysqlPassword = "root" # Replace with your actual password, e.g., "root" or "mysecretpass"
    # --- END CONFIGURATION ---

    # Check if the mysql executable exists at the specified path
    if (-not (Test-Path $mysqlPath)) {
        Write-Error "MySQL executable not found at '$mysqlPath'. Please update the path in the script."
        return
    }

    # Construct the command arguments
    $arguments = @(
        "-u", $mysqlUser,
        "-p$($mysqlPassword)",
        $Database,
        "-e", $Query
    )

    # Execute the command
    try {
        Write-Host "Executing Query: $Query" -ForegroundColor Cyan
        & $mysqlPath $arguments
    }
    catch {
        Write-Error "An error occurred while executing the MySQL command: $_"
    }
}

# You can export the function to make it more easily discoverable, though dot-sourcing is sufficient.
Export-ModuleMember -Function Invoke-MySqlQuery