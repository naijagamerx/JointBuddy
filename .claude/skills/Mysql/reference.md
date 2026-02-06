# ✅ MySQL Command-Line Cheat Sheet (Generic for PowerShell / Windows / MAMP)

This guide provides a **generalized and reusable version** of MySQL command-line operations. All examples use **placeholders** that you should replace with your specific details. These commands leverage the MySQL client directly, typically installed with environments like MAMP, XAMPP, or similar setups.

## 📝 Placeholders to Replace

Before using any command, make sure to replace the following placeholders with your actual values:

*   `databasename` → your actual database name (e.g., `my_app_db`)
*   `tablename` → your specific table name (e.g., `users`, `products`)
*   `columnname` → the column you want to query or modify (e.g., `id`, `email`, `name`)
*   `yourpassword` → your MySQL password (e.g., `root`, `mysecretpass`)
*   `yourfile.sql` → the path to your SQL file (e.g., `C:\temp\backup.sql`)

---

## 1️⃣ See All Tables in a Database

This command lists every table inside your chosen database.

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "SHOW TABLES;"
```

---

## 2️⃣ Describe a Table (View Its Columns)

Use this to inspect the structure of a table, including its columns, data types, and keys.

**Example for a `users` table:**

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "DESCRIBE users;"
```

**For any other table:**

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "DESCRIBE tablename;"
```

---

## 3️⃣ Select Data (Read Information)

Retrieve specific data from your tables.

**Example: Get `id` and `email` from the `users` table:**

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "SELECT id, email FROM users;"
```

**Example: Get specific columns from another table:**

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "SELECT id, columnname FROM tablename;"
```

💡 **Tip:** Use `DESCRIBE tablename;` first to check available columns before constructing your `SELECT` query.

---

## 4️⃣ Update Data (Modify a Record)

Modify existing records in your database.

**Example: Update a user’s email where `id = 1`:**

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "UPDATE users SET email = 'newemail@example.com' WHERE id = 1;"
```

✅ **Important:** **Always** include a `WHERE` clause when updating data to avoid modifying all rows in the table.

---

## 5️⃣ Delete Data (Remove a Record)

Remove specific records from your database.

**Example: Delete a record with a specific `id` from `tablename`:**

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "DELETE FROM tablename WHERE id = 10;"
```

✅ **Important:** Always include a `WHERE` clause (e.g., `WHERE id = something`) to prevent deleting all records from the table.

---

## 6️⃣ Import an SQL File

If you have an `.sql` file (e.g., a database backup or schema definition), you can import its contents into your database using one of the following methods:

### Option 1 – Stream with PowerShell

This method is generally robust for larger files as it streams the content.

```powershell
Get-Content yourfile.sql | C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename
```

### Option 2 – Universal Way (Redirect Input)

This is the most universally compatible method, working across various shells and environments.

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename < yourfile.sql
```

---

## ⚠️ Common Problems and Fixes

| Problem                              | How to Fix                                                                                                               |
| :----------------------------------- | :----------------------------------------------------------------------------------------------------------------------- |
| **Password warning**                 | If you see a warning about using passwords on the command line, it's generally safe to *ignore it* in local development environments. |
| **Wrong table name**                 | Run `SHOW TABLES;` to verify the correct spelling and existence of your table.                                           |
| **Wrong column name**                | Run `DESCRIBE tablename;` to see all available columns and their exact names for the specified table.                    |
| **Updated or deleted too many rows** | This occurs when a `WHERE` clause is omitted or incorrect. **Always** use a specific `WHERE` clause for `UPDATE` and `DELETE` operations. |
| **Command too long**                 | If your SQL query is excessively long for the command line, consider putting the SQL into a `.sql` file and importing it using one of the methods above. |

---

## 🧩 Quick Template for Any Command

Use this template as a base for executing any SQL command directly from the command line.

```powershell
C:\MAMP\bin\mysql\bin\mysql -u root -pyourpassword databasename -e "SQL_QUERY_HERE"
```

Simply replace `SQL_QUERY_HERE` with your actual SQL command (e.g., `SELECT * FROM users;`, `INSERT INTO products (name) VALUES ('New Product');`).