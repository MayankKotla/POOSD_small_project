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

1. Copy `includes/db_config.example.php` to `includes/db_config.php` and fill
   in real DB credentials (this file is gitignored — never commit it).
2. Point Apache's document root at this folder, or copy it into
   `/var/www/html` on the droplet.
3. Import the `Users` / `Contacts` schema into MySQL.

## Deployment

Hosted on a DigitalOcean droplet (LAMP stack) under a custom domain — see the
team's project docs for the live URL once DNS is finalized.
