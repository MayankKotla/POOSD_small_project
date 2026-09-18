"""Integration smoke test against a configured development API; creates test accounts."""
import http.cookiejar
import json
import sys
import urllib.error
import urllib.request
import uuid

base = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8000').rstrip('/')
checks = 0

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(who, endpoint, status=200, body=None, method=None):
    global checks
    data = None if body is None else json.dumps(body).encode()
    req = urllib.request.Request(base + '/api/' + endpoint, data=data,
        headers={'Content-Type': 'application/json'}, method=method or ('GET' if body is None else 'POST'))
    try:
        response = who.open(req)
    except urllib.error.HTTPError as error:
        response = error
    payload = json.loads(response.read())
    assert response.status == status, (endpoint, response.status, payload)
    checks += 1
    return payload

a, b, anonymous = client(), client(), client()
for endpoint in ['me.php', 'getContacts.php', 'searchContacts.php?search=x']:
    request(anonymous, endpoint, 401)
for endpoint in ['addContact.php', 'updateContact.php', 'deleteContact.php']:
    request(anonymous, endpoint, 401, {})
request(anonymous, 'logout.php', 405)
users = []
for who in [a, b]:
    account = dict(firstName='API', lastName='Test', email='t' + uuid.uuid4().hex + '@example.com', password='test-password-123')
    request(who, 'register.php', 201, account)
    request(who, 'register.php', 409, account)
    request(who, 'login.php', 401, dict(email=account['email'], password='wrong-password'))
    result = request(who, 'login.php', body=account)
    users.append(result['id'])
    assert request(who, 'me.php')['id'] == result['id']
    assert request(who, 'getContacts.php') == []

contact = dict(firstName='Ada', lastName='Lovelace', phone='555-0100', email='ada@example.com')
for invalid in [[], True, 123, None, '', 'x' * 51]:
    request(a, 'addContact.php', 400, dict(contact, firstName=invalid))
request(a, 'addContact.php', 400, dict(contact, email='invalid'))
first = request(a, 'addContact.php', 201, dict(contact, userId=users[1]))['id']
second = request(a, 'addContact.php', 201, dict(contact, firstName='100%_!'))['id']
assert request(b, 'getContacts.php') == []
rows = request(a, 'getContacts.php?limit=1&offset=0')
assert len(rows) == 1
assert len(request(a, 'getContacts.php?limit=1&offset=1')) == 1
assert request(a, 'getContacts.php?limit=1&offset=2') == []
for query in ['limit=0', 'limit=101', 'offset=-1', 'limit%5B%5D=1']:
    request(a, 'getContacts.php?' + query, 400)
for query in ['Ada%20Lovelace', '555-0100', 'ada%40example.com']:
    assert any(row['id'] == first for row in request(a, 'searchContacts.php?search=' + query))
for query in ['%25', '_', '!']:
    assert [row['id'] for row in request(a, 'searchContacts.php?search=' + query)] == [second]
assert request(a, 'searchContacts.php?search=%27%20OR%201%3D1--') == []
request(a, 'searchContacts.php?search%5B%5D=x', 400)
request(a, 'searchContacts.php?search=%FF', 400)
assert request(b, 'searchContacts.php?search=Ada') == []
request(b, 'updateContact.php', 404, dict(id=first, phone='999'))
request(b, 'deleteContact.php', 404, dict(id=first))
for invalid in [0, -1, 1.5, True, [], '2147483648', '01']:
    request(a, 'deleteContact.php', 400, dict(id=invalid))
request(a, 'updateContact.php', 400, dict(id=first))
request(a, 'updateContact.php', body=dict(id=first, phone=contact['phone']))
request(a, 'updateContact.php', body=dict(id=str(first), phone='555-9999', userId=users[1]))
assert next(row for row in request(a, 'getContacts.php') if row['id'] == first)['phone'] == '555-9999'
for contact_id in [first, second]:
    request(a, 'deleteContact.php', body=dict(id=contact_id))
    request(a, 'deleteContact.php', 404, dict(id=contact_id))
assert request(a, 'getContacts.php') == []
request(a, 'logout.php', method='POST')
request(a, 'me.php', 401)
request(a, 'addContact.php', 401, contact)
request(b, 'logout.php', method='POST')
print(f'Passed {checks} HTTP checks. Test account IDs: {users}')
