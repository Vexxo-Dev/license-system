# License System API

Developer reference for using the License System JSON API from web apps, mobile apps, backend services, and Postman.

## Base URL

Local development:

```text
http://localhost:8000/public
```

API endpoints are under:

```text
http://localhost:8000/public/api
```

Example:

```text
http://localhost:8000/public/api/api_clients.php
```

## Setup

1. Import the database:

   ```powershell
   mysql -u root < database\schema.sql
   ```

2. Update database credentials in `includes/config.php`.

3. Start the PHP server from the project root:

   ```powershell
   php -S localhost:8000
   ```

## Request Format

The API accepts either JSON or form-encoded payloads.

Recommended headers:

```http
Content-Type: application/json
Accept: application/json
```

## Response Format

Successful responses:

```json
{
  "ok": true,
  "data": {}
}
```

Error responses:

```json
{
  "ok": false,
  "error": "Error message."
}
```

## Authentication

`POST /api/api_login.php` validates a user and returns user details.

This baseline API does not issue tokens and does not require authorization headers for the other API endpoints. Add token/session enforcement before using this API in production.

## Status Codes

| Code | Meaning |
| --- | --- |
| `200` | Request succeeded |
| `201` | Resource created |
| `204` | CORS preflight response |
| `400` | Required input is missing or invalid |
| `401` | Invalid login credentials |
| `403` | User account is inactive |
| `405` | HTTP method is not allowed |
| `409` | Duplicate resource conflict |

## CORS

All API endpoints send:

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Allow-Methods: GET, POST, OPTIONS
```

Adjust `includes/api.php` before production deployment.

## Postman

Import this collection:

```text
docs/license-system.postman_collection.json
```

The collection uses a `base_url` variable with this default:

```text
http://localhost:8000/public
```

## Endpoints

### Login

```http
POST /api/api_login.php
```

Validates a user login.

Request body:

```json
{
  "email": "admin@organization.com",
  "password": "password"
}
```

Success response:

```json
{
  "ok": true,
  "data": {
    "user": {
      "id": 1,
      "full_name": "Admin User",
      "email": "admin@organization.com",
      "role": "admin",
      "status": "active"
    }
  }
}
```

Errors:

| Status | Reason |
| --- | --- |
| `400` | Email or password is missing |
| `401` | Email or password is invalid |
| `403` | User exists but account is not active |
| `405` | Method is not `POST` |

Notes:

- If the `users` table is empty, the first successful login creates a `Primary Admin` user using the submitted email and password.

### List Users

```http
GET /api/api_users.php
```

Returns all users ordered by newest first.

Success response:

```json
{
  "ok": true,
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "client_name": "Acme Corp",
      "full_name": "Admin User",
      "email": "admin@organization.com",
      "role": "admin",
      "status": "active",
      "last_login_at": "2026-05-18 01:00:00"
    }
  ]
}
```

### Create User

```http
POST /api/api_users.php
```

Creates a user.

Request body:

```json
{
  "full_name": "Jane Doe",
  "client_id": 1,
  "email": "jane@example.com",
  "role": "manager",
  "status": "active",
  "password": "Temp1234!"
}
```

Fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `full_name` | string | Yes | User display name |
| `client_id` | number/null | No | Client company this user belongs to. Multiple users can share the same client. |
| `email` | string | Yes | Must be unique |
| `role` | string | No | `admin`, `manager`, or `viewer`; defaults to `viewer` |
| `status` | string | No | `active`, `inactive`, or `pending`; defaults to `active` |
| `password` | string | No | Defaults to `Temp1234!` |

Success response:

```json
{
  "ok": true,
  "data": {
    "id": 2,
    "client_id": 1,
    "full_name": "Jane Doe",
    "email": "jane@example.com",
    "role": "manager",
    "status": "active"
  }
}
```

Errors:

| Status | Reason |
| --- | --- |
| `400` | Full name or email is missing |
| `409` | Email already exists |
| `405` | Method is not `GET` or `POST` |

### List Clients

```http
GET /api/api_clients.php
```

Returns all clients ordered by newest first.

Success response:

```json
{
  "ok": true,
  "data": [
    {
      "id": 1,
      "name": "Acme Corp",
      "industry": "Technology",
      "status": "active",
      "total_licenses": 1250,
      "active_users": 1180,
      "primary_contact_name": "Jane Doe",
      "primary_contact_email": "admin@acmecorp.com"
    }
  ]
}
```

### Create Client

```http
POST /api/api_clients.php
```

Creates a client.

Request body:

```json
{
  "name": "Northwind",
  "industry": "Retail",
  "status": "active",
  "primary_contact_name": "Alicia King",
  "primary_contact_email": "alicia@northwind.com"
}
```

Fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `name` | string | Yes | Client/company name |
| `industry` | string | Yes | Client industry |
| `status` | string | No | `active`, `over_limit`, or `inactive`; defaults to `active` |
| `primary_contact_name` | string | No | Can be empty |
| `primary_contact_email` | string | No | Can be empty |

Success response:

```json
{
  "ok": true,
  "data": {
    "id": 4,
    "name": "Northwind",
    "industry": "Retail",
    "status": "active",
    "total_licenses": 0,
    "active_users": 0,
    "primary_contact_name": "Alicia King",
    "primary_contact_email": "alicia@northwind.com"
  }
}
```

Note: `total_licenses` and `active_users` returned by client list endpoints are derived values. `total_licenses` is counted from `licenses`; `active_users` is counted from active `users` assigned to that client.

Errors:

| Status | Reason |
| --- | --- |
| `400` | Name or industry is missing |
| `405` | Method is not `GET` or `POST` |

### List Licenses

```http
GET /api/api_licenses.php
```

Returns all licenses ordered by newest first.

Success response:

```json
{
  "ok": true,
  "data": [
    {
      "id": 1,
      "license_key": "LP-ENT-8X9A-V2M4",
      "status": "active",
      "type": "ENTERPRISE",
      "expires_at": "2026-12-31",
      "client_name": "Acme Corp",
      "client_id": 1
    }
  ]
}
```

### Create License

```http
POST /api/api_licenses.php
```

Creates a license for an existing client.

Request body:

```json
{
  "client_id": 1,
  "type": "ENTERPRISE",
  "status": "active",
  "expires_at": "2026-12-31",
  "license_key": "LP-ENT-CUSTOM-0001"
}
```

Fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `client_id` | number | Yes | Must reference an existing client |
| `type` | string | No | `ENTERPRISE`, `PROFESSIONAL`, `STANDARD`, or `BASIC`; defaults to `STANDARD` |
| `status` | string | No | `active`, `expired`, or `revoked`; defaults to `active` |
| `expires_at` | string/null | No | Date in `YYYY-MM-DD`; empty value becomes `null` |
| `license_key` | string | No | Auto-generated when omitted |

Success response:

```json
{
  "ok": true,
  "data": {
    "id": 6,
    "license_key": "LP-ENT-CUSTOM-0001",
    "client_id": 1,
    "status": "active",
    "type": "ENTERPRISE",
    "expires_at": "2026-12-31"
  }
}
```

Errors:

| Status | Reason |
| --- | --- |
| `400` | Client ID is missing or invalid |
| `405` | Method is not `GET` or `POST` |
| `500` | Database constraint error, such as duplicate license key or missing client |

## JavaScript Example

```js
const baseUrl = 'http://localhost:8000/public';

async function getClients() {
  const response = await fetch(`${baseUrl}/api/api_clients.php`, {
    headers: {
      Accept: 'application/json'
    }
  });

  const payload = await response.json();

  if (!payload.ok) {
    throw new Error(payload.error || 'Request failed');
  }

  return payload.data;
}
```

## cURL Examples

Login:

```bash
curl -X POST "http://localhost:8000/public/api/api_login.php" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@organization.com\",\"password\":\"password\"}"
```

Create client:

```bash
curl -X POST "http://localhost:8000/public/api/api_clients.php" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Northwind\",\"industry\":\"Retail\",\"status\":\"active\",\"primary_contact_name\":\"Alicia King\",\"primary_contact_email\":\"alicia@northwind.com\"}"
```

Create license:

```bash
curl -X POST "http://localhost:8000/public/api/api_licenses.php" \
  -H "Content-Type: application/json" \
  -d "{\"client_id\":1,\"type\":\"ENTERPRISE\",\"status\":\"active\",\"expires_at\":\"2026-12-31\"}"
```

## UI Form Actions

These endpoints are used by the PHP UI and redirect back to pages. Use the JSON API endpoints above for app integrations.

| Action | Method | Purpose |
| --- | --- | --- |
| `/login.php` | `POST` | Browser login form |
| `/actions/users_add.php` | `POST` | Browser add-user form |
| `/actions/clients_add.php` | `POST` | Browser add-client form |
| `/actions/licenses_add.php` | `POST` | Browser issue-license form |
