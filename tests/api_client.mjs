import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../public/js/auth.js', import.meta.url), 'utf8');
let response;
const context = vm.createContext({document: {addEventListener() {}}, fetch: async () => response});
vm.runInContext(source, context);
for (const status of [400, 401, 409, 500]) {
  response = {ok: false, status, json: async () => ({success: false, error: 'API error message'})};
  const result = await context.callApi('login.php', {});
  assert.equal(result.error, 'API error message');
}
response = {ok: true, json: async () => ({success: true, id: 12})};
assert.equal((await context.callApi('login.php', {})).id, 12);
response = {ok: false, status: 502, json: async () => {throw new Error('Invalid JSON');}};
await assert.rejects(() => context.callApi('login.php', {}));
console.log('Passed auth client success and error-response checks.');
