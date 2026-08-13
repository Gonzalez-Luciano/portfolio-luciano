import assert from 'node:assert/strict';
import {existsSync, readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import {resolve} from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');

for (const path of ['.editorconfig', '.env.example', '.dockerignore', 'docs/ENVIRONMENT.md']) {
  assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);
}

const tracked = execFileSync('git', ['ls-files'], {cwd: root, encoding: 'utf8'}).split(/\r?\n/);
assert.deepEqual(tracked.filter((path) => /(^|\/)\.env(\.|$)/.test(path) && !path.endsWith('.example')), []);

const example = read('.env.example');
assert.doesNotMatch(example, /CLOUDFLARE|TUNNEL/i);
assert.match(example, /MYSQL_PASSWORD=replace-with-/);

for (const variable of [
  'COMPOSE_PROJECT_NAME',
  'GATEWAY_HOST',
  'GATEWAY_PORT',
  'APP_URL',
  'APP_KEY',
  'MYSQL_DATABASE',
  'MYSQL_USER',
  'MYSQL_PASSWORD',
  'MYSQL_ROOT_PASSWORD',
  'MYSQL_TEST_DATABASE',
  'MYSQL_TEST_USER',
  'MYSQL_TEST_PASSWORD',
  'MYSQL_TEST_ROOT_PASSWORD',
]) {
  assert.match(example, new RegExp(`^${variable}=.+$`, 'm'), `Missing ${variable} in .env.example`);
}

for (const variable of ['APP_KEY', 'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD', 'MYSQL_TEST_PASSWORD', 'MYSQL_TEST_ROOT_PASSWORD']) {
  assert.match(example, new RegExp(`^${variable}=.*replace-with-`, 'm'), `${variable} must be a placeholder`);
}

if (existsSync(resolve(root, 'compose.yaml'))) {
  const compose = read('compose.yaml');
  assert.doesNotMatch(compose, /CLOUDFLARE|TUNNEL/i);
}
console.log('Repository and environment contract pass.');
