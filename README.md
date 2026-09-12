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

## API contract (draft — see SwaggerHub for the source of truth)

| Endpoint                  | Method | Body / Query                          | Returns |
|----------------------------|--------|----------------------------------------|---------|
| `/api/register.php`        | POST   | `{firstName, lastName, email, password}` | `{success, error}` |
| `/api/login.php`           | POST   | `{email, password}`                    | `{success, id}` |
| `/api/getContacts.php`     | GET    | `?id=<userId>`                         | `[{id, firstName, lastName, phone, email}, ...]` |
| `/api/addContact.php`      | POST   | `{userId, firstName, lastName, phone, email}` | `{success, error}` |
| `/api/updateContact.php`   | POST   | `{id, firstName, lastName, phone, email}` | `{success, error}` |
| `/api/deleteContact.php`   | POST   | `{id}`                                 | `{success, error}` |
| `/api/searchContacts.php`  | GET    | `?id=<userId>&search=<term>`           | `[{id, firstName, lastName, phone, email}, ...]` |

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

The database connection is the first API milestone. Registration, login, and
contact endpoints are still to come; the frontend currently uses mock data.

## Deployment

Hosted on a DigitalOcean droplet (LAMP stack) under a custom domain — see the
team's project docs for the live URL once DNS is finalized.

For web deployment, serve `public/` as Apache's document root and map `/api/`
to the repository's `api/` directory once the endpoints exist. Keep `includes/`,
the SQL dump, and `.git/` outside the public document root.
