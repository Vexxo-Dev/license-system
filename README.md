# License System

A PHP and MySQL license management system with pages for login, dashboard, users, clients, and licenses.

## Requirements

- PHP 8+
- MySQL or MariaDB
- A local web server, or PHP's built-in development server

## Setup

1. Start MySQL. If you use XAMPP, start MySQL from the XAMPP Control Panel.

2. Import the database schema:

   ```powershell
   mysql -u root < database\schema.sql
   ```

   If your MySQL user has a password:

   ```powershell
   mysql -u root -p < database\schema.sql
   ```

3. Check the database credentials in `includes/config.php`:

   ```php
   return [
       'db_host' => '127.0.0.1',
       'db_name' => 'license_system',
       'db_user' => 'root',
       'db_pass' => '',
       'db_charset' => 'utf8mb4'
   ];
   ```

## Run Locally

From the project root:

```powershell
cd D:\Projects\license-system
php -S localhost:8000
```

Open:

```text
http://localhost:8000/
```

The root `index.php` redirects to:

```text
http://localhost:8000/public/login.php
```

## Default Login

```text
Email: admin@organization.com
Password: password
```

## Project Structure

```text
database/schema.sql       Database schema and seed data
includes/config.php       Database connection settings
includes/db.php           PDO database connection helper
public/                   Main PHP pages and API endpoints
public/actions/           Form action handlers
public/api/               JSON API endpoints
public/assets/            CSS and JavaScript assets
legacy/html/              Original static HTML pages
```

## API Documentation

See `docs/API.md` for API endpoint details.
