# License System

A PHP and MySQL license management system with pages for login, dashboard, users, clients, and licenses.

## Requirements

- PHP 8+
- MySQL or MariaDB
- A local web server, or PHP's built-in development server

## Setup

1. Start MySQL. If you use XAMPP, start MySQL from the XAMPP Control Panel.

2. Import the database schema:

   ```bash
   mysql -u root < database\schema.sql
   ```

   If your MySQL user has a password:

   ```bash
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

## Architecture Style

This project uses a simple PHP page-based monolith structure, not a full MVC framework.

The code is organized by responsibility:

- `public/*.php`: UI pages rendered by PHP.
- `public/actions/`: form POST handlers for browser actions.
- `public/api/`: JSON API endpoints for external apps and Postman.
- `includes/`: shared backend helpers such as auth, database connection, API response helpers, and reusable layout components.
- `public/assets/`: frontend CSS and JavaScript used by the PHP pages.
- `database/`: schema and seed data.

So the structure is best described as:

```text
PHP monolith with separated UI pages, action handlers, JSON API endpoints, shared backend includes, and frontend assets.
```

It is not strict MVC because there are no separate controller/model/view layers or routing framework. If the project grows, it can later be refactored into MVC by moving database logic into models, request handling into controllers, and page templates into views.

## Folder Structure

```text
license-system/
├── index.php
├── README.md
├── LICENSE
├── database/
│   └── schema.sql
├── docs/
│   ├── API.md
│   └── license-system.postman_collection.json
├── includes/
│   ├── api.php
│   ├── auth.php
│   ├── components.php
│   ├── config.php
│   └── db.php
├── public/
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── users.php
│   ├── clients.php
│   ├── licence.php
│   ├── actions/
│   │   ├── clients_add.php
│   │   ├── clients_manage.php
│   │   ├── licenses_add.php
│   │   ├── licenses_manage.php
│   │   ├── users_add.php
│   │   └── users_manage.php
│   ├── api/
│   │   ├── api_clients.php
│   │   ├── api_licenses.php
│   │   ├── api_login.php
│   │   └── api_users.php
│   └── assets/
│       ├── css/
│       └── js/
└── legacy/
    └── html/
```

Key folders:

- `database/`: database schema and seed data.
- `docs/`: API reference and Postman collection.
- `includes/`: shared configuration, database, auth, API, and layout helpers.
- `public/`: browser-accessible PHP pages, form actions, API endpoints, and assets.
- `legacy/`: archived static HTML files kept for reference.

## API Documentation

See:

```text
docs/API.md
```

It documents:

- Base URL and setup
- Request/response formats
- Authentication behavior
- Status codes
- User, client, license, and login endpoints
- JavaScript `fetch` examples
- cURL examples

## Postman Collection

Import this file into Postman:

```text
docs/license-system.postman_collection.json
```

The collection includes:

- Login
- List/Create Users
- List/Create Clients
- List/Create Licenses
- Create License With Custom Key

Default Postman variable:

```text
base_url = http://localhost:8000/public
```

## Contributors

[![Contributors](https://contrib.rocks/image?repo=Vexxo-Dev/license-system)](https://github.com/Vexxo-Dev/license-system/graphs/contributors)

## Stargazers

[![Stargazers](https://stargazers.yassintube126.workers.dev/Vexxo-Dev/license-system)](https://github.com/Vexxo-Dev/license-system/stargazers)
