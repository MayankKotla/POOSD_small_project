# Personal Contact Manager — Team 12 (POOSD)

COP 4331C — Software Design (Fall 2026), Dr. Aashish Yadavally

A per-user contact manager built on a LAMP stack (Linux, Apache, MySQL, PHP)
with a REST API documented in SwaggerHub, deployed to a DigitalOcean droplet
under a custom domain.

## Team

| Name              | Role      |
|-------------------|-----------|
| Mayank Kotla      | Project Manager |
| Arnov Kandlikar   | API |
| Lucas Morales     | API |
| Daniel Evans      | Database |
| Brandon Serrano   | Database |
| Kameron Ingram    | Front-End |

## Project structure

```
api/        PHP REST endpoints (register.php, login.php, getContacts.php, ...)
includes/   Shared PHP — DB connection (db_config.php, gitignored), auth helpers
public/     Front-end — HTML/CSS/JS served by Apache
  css/
  js/
```

## API contract

The core endpoints are implemented. See [docs/API.md](docs/API.md) for request
formats, session cookies, pagination, tests, and the frontend handoff. Update the
team's SwaggerHub definition to match this contract before submission.

## Local setup

Requires PHP with the `mysqli` extension and MySQL 8.0, matching the committed
`team12SmallProject.sql` dump.

1. From the repository root, copy the configuration if it does not already exist:

   ```bash
   cp -n includes/db_config.example.php includes/db_config.php
   ```

2. Edit `includes/db_config.php`. Keep `DB_NAME` as `team12SmallProject` and
   replace the placeholder MySQL username/password with the application
   credentials from the database team. `DB_HOST=localhost` means MySQL runs
   on the same machine as PHP. `DB_PORT` is optional and defaults to 3306.
   The real configuration is gitignored; never commit it.

3. Use the database the team already created. Only import the SQL dump into a
   fresh, empty database: it contains `DROP TABLE` statements that would remove
   existing tables and their data.

4. Check the connection from the repository root on the machine running PHP:

   ```bash
   php -r 'require "includes/db.php"; $db = get_db(); echo "Database connection successful\n"; $db->close();'
   ```

`includes/db.php` provides `get_db()`, which reads the private configuration,
opens a MySQL connection, and sets its character encoding to `utf8mb4`.
It enables MySQL exceptions so future endpoints can return controlled JSON
errors. The endpoint code must catch unexpected exceptions without returning
database details to the browser.

Registration, login, contact CRUD, listing, search, session checks, and logout are implemented.
The contacts frontend still needs integration and live-server verification.

## Registration API

`POST /api/register.php` accepts `Content-Type: application/json` with
`firstName`, `lastName`, `email`, and `password` as strings. It uses `get_db()`
and the existing `Users` table; account emails are stored in `Users.Login`.

- Names are trimmed and limited to 50 Unicode characters, with no control characters.
- Emails are trimmed, lowercased, validated, and limited to 50 characters to match `Login`.
- Passwords require at least 8 Unicode characters, at most 72 UTF-8 bytes, and no null bytes.
  They are not trimmed. Only a hash from PHP's `password_hash()` is stored.
- The insert uses a prepared statement, and the unique `Login` index rejects duplicate accounts.

Every response is JSON with `success` and `error`. Successful creation returns
HTTP `201` with `{"success":true,"error":""}`. Invalid fields or malformed JSON
return `400`, duplicate emails `409`, unsupported methods `405`, non-JSON
content `415`, and requests over 16 KiB `413`. Database/configuration failures
return `500` with a generic error; private database details are not returned.

For a local API check, first configure the database as described above, then run
`php -S 127.0.0.1:8000 -t public dev-router.php`. Send a POST to
`http://127.0.0.1:8000/api/register.php` from Postman with JSON such as:

```json
{"firstName":"Test","lastName":"User","email":"api-test@example.com","password":"test-password-123"}
```

This creates a test account in the configured database. Repeating it should
return `409`. Opening the address in a browser sends GET and should return `405`.

## Login API

`POST /api/login.php` takes an email and password as JSON. It finds the account
in `Users.Login` and checks the stored hash with `password_verify()`. Input rules
are the same as registration, and passwords are not trimmed.

Success returns `200` and `{"success":true,"id":123,"error":""}`, where `id`
is the account's database ID. A wrong password or unknown email returns `401`
and `{"success":false,"error":"Incorrect email or password."}`. Other errors
use the same statuses as registration.

Login saves the account ID in a PHP session and replaces the old session ID.
The `team12_session` cookie is HttpOnly, uses SameSite=Lax, and gets the Secure
flag on HTTPS. Use HTTPS on the live server. If a proxy handles HTTPS, configure
the trusted web server so PHP knows the request used HTTPS. PHP's session
directory must be writable and outside the public document root.

Contact endpoints should call `require_user_id()` from `includes/auth.php`.
It returns the logged-in account ID or a `401` error. Use that ID when checking
who owns a contact.

With the local API server running, POST this to
`http://127.0.0.1:8000/api/login.php` after registering the test account:

```json
{"email":"api-test@example.com","password":"test-password-123"}
```

Keep cookies enabled in Postman. `GET /api/me.php` checks the session;
`POST /api/logout.php` ends it. The login and registration pages use the real API
and display JSON error messages returned by the server.

## Deployment

Hosted on a DigitalOcean droplet (LAMP stack) under a custom domain — see the
team's project docs for the live URL once DNS is finalized.

For web deployment, serve `public/` as Apache's document root and map `/api/`
to the repository's `api/` directory. Keep `includes/`,
the SQL dump, and `.git/` outside the public document root.
