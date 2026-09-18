# API handoff

Run `php -S 127.0.0.1:8000 -t public dev-router.php` from the repository root after
configuring the database as described in README.md. This router is for local
use. Production Apache must serve public/ and map /api/ to api/, keeping includes/
and the SQL dump private. Requires PHP 8+ with mysqli and MySQL 8.

All POST bodies are JSON objects with Content-Type: application/json (logout
needs no body). Login sets an HttpOnly session cookie. Send that cookie on later
requests. User ownership always comes from the session; do not send userId.

| Endpoint under /api/ | Method | Input | Success |
|---|---|---|---|
| register.php | POST | firstName, lastName, email, password | 201: {success:true,error:""} |
| login.php | POST | email, password | 200: {success:true,id,error:""} |
| me.php | GET | none | 200: {success:true,id,error:""} |
| logout.php | POST | none | 200: {success:true,error:""} |
| addContact.php | POST | firstName, lastName, phone, email | 201: {success:true,id,error:""} |
| updateContact.php | POST | id plus one or more contact fields | 200: {success:true,error:""} |
| deleteContact.php | POST | id | 200: {success:true,error:""} |
| getContacts.php | GET | optional limit, offset | 200: array of contacts |
| searchContacts.php | GET | search; optional limit, offset | 200: array of contacts |

Contact rows contain id, firstName, lastName, phone, email, and dateAdded. No
matches returns []. Both list endpoints sort by lastName, firstName, then id.
The default page has 50 rows; limit accepts 1–100 and offset starts at 0. Fetch
additional pages until fewer than limit rows return. Search matches a literal
substring of first name, last name, full name, phone, or email. An empty search
lists contacts. Search is limited to 100 characters; %, _, and ! are literal.

All four contact fields must be nonempty strings of at most 50 Unicode characters
without control characters; email must be valid. Updates accept any subset of
these four fields; omitted fields are unchanged. Contact IDs are positive integers
(or digit strings) at most 2147483647. Saving unchanged values succeeds.

Errors use {success:false,error:"message"}: 400 invalid input, 401 missing login
or wrong credentials, 404 missing/unowned contact, 405 wrong method, 409 duplicate
account, 413 oversized body, 415 wrong content type, 500 server failure.

## Frontend integration

Use same-origin fetch so cookies are sent. Check /api/me.php on the contacts page
and send unauthenticated users to login.html. After the contacts page is merged,
enable the login redirect. Replace mock contact operations with these endpoints;
use URLSearchParams for search queries. Refresh the list after a successful write.
Render contact strings with textContent, never by concatenating them into innerHTML.
Parse JSON even for HTTP errors so the user sees the API's error message. On logout,
POST logout.php, then navigate to login.html. A client-stored user ID grants no access.

## Verification and remaining work

Run `python3 tests/api_http.py http://127.0.0.1:8000` only against a development
database: it creates two uniquely named test accounts and deletes its contacts
on success. It leaves the accounts for inspection; remove those test accounts
manually afterward if desired. Run `node tests/api_client.mjs` for UI error handling.

Before counting the API as complete, repeat the HTTP checks on the team's MySQL 8
server, connect and test the contacts UI, verify HTTPS/session cookies and Apache
routing, and update SwaggerHub. Code completion alone does not verify deployment.
