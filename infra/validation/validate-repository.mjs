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

const composePath = resolve(root, 'compose.yaml');
assert.ok(existsSync(composePath), 'Missing compose.yaml');

const compose = read('compose.yaml');
assert.doesNotMatch(compose, /CLOUDFLARE|TUNNEL/i);
assert.doesNotMatch(compose, /network_mode:\s*host/i);
assert.doesNotMatch(compose, /^\s+env_file:/m, 'Root environment input must not be blanket-injected');
assert.doesNotMatch(compose, /(^|\n)\s*-\s*[^\n]*3306[^\n]*$/m, 'MySQL must not publish port 3306');
assert.doesNotMatch(compose, /\bsleep\b/i, 'Compose must use health checks instead of sleeps');

const serviceBlock = compose.match(/^services:\n([\s\S]*?)(?=^networks:)/m)?.[1];
assert.ok(serviceBlock, 'compose.yaml must declare services before networks');
const serviceNames = [...serviceBlock.matchAll(/^ {2}([a-z][a-z-]*):\s*$/gm)].map((match) => match[1]);
assert.deepEqual(serviceNames, ['gateway', 'web', 'api', 'mysql', 'mysql-test', 'api-test']);

assert.match(compose, /^ {2}gateway:\n[\s\S]*?^ {4}ports:\n {6}- "\$\{GATEWAY_HOST:-127\.0\.0\.1\}:\$\{GATEWAY_PORT:-8000\}:80"$/m);
assert.equal([...compose.matchAll(/^ {4}ports:$/gm)].length, 1, 'Only gateway may publish a host port');
assert.match(compose, /^ {2}mysql-test:\n[\s\S]*?^ {4}profiles: \[test\]$/m);
assert.match(compose, /^ {2}api-test:\n[\s\S]*?^ {4}profiles: \[test\]$/m);
assert.match(compose, /^ {2}api-test:\n[\s\S]*?^ {4}depends_on:\n {6}mysql-test: \{condition: service_healthy\}$/m);

const networkBlock = compose.match(/^networks:\n([\s\S]*?)(?=^volumes:)/m)?.[1];
assert.ok(networkBlock, 'compose.yaml must declare networks before volumes');
const networkNames = [...networkBlock.matchAll(/^ {2}([a-z][a-z-]*): \{\}$/gm)].map((match) => match[1]);
assert.deepEqual(networkNames, ['front', 'data', 'test']);

const volumeBlock = compose.match(/^volumes:\n([\s\S]*)$/m)?.[1];
assert.ok(volumeBlock, 'compose.yaml must declare volumes');
const volumeNames = [...volumeBlock.matchAll(/^ {2}([a-z][a-z_]*): \{\}$/gm)].map((match) => match[1]);
assert.deepEqual(volumeNames, ['web_node_modules', 'api_vendor', 'mysql_data', 'api_public_media']);
console.log('Repository and environment contract pass.');
