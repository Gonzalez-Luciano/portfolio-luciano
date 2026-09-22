import assert from 'node:assert/strict';
import {existsSync, readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import {resolve} from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');

for (const path of [
  '.editorconfig',
  '.env.example',
  '.dockerignore',
  'docs/ENVIRONMENT.md',
  'docs/testing/PHASE_3_VERIFICATION.md',
  'infra/validation/verify-hmr.ps1',
]) {
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

const service = (name) => {
  const start = serviceBlock.indexOf(`  ${name}:\n`);
  assert.notEqual(start, -1, `Missing ${name} service`);

  const remaining = serviceBlock.slice(start + name.length + 4);
  const next = remaining.search(/^ {2}[a-z][a-z-]*:\s*$/m);

  return next === -1 ? remaining : remaining.slice(0, next);
};

assert.match(compose, /^ {2}gateway:\n[\s\S]*?^ {4}ports:\n {6}- "\$\{GATEWAY_HOST:-127\.0\.0\.1\}:\$\{GATEWAY_PORT:-8000\}:80"$/m);
assert.equal([...compose.matchAll(/^ {4}ports:$/gm)].length, 1, 'Only gateway may publish a host port');
assert.match(compose, /^ {2}mysql-test:\n[\s\S]*?^ {4}profiles: \[test\]$/m);
assert.match(compose, /^ {2}api-test:\n[\s\S]*?^ {4}profiles: \[test\]$/m);
assert.match(compose, /^ {2}api-test:\n[\s\S]*?^ {4}depends_on:\n {6}mysql-test: \{condition: service_healthy\}$/m);

const apiService = service('api');
const gatewayService = service('gateway');
const webService = service('web');
const webDockerfile = read('web/Dockerfile');
const viteConfig = read('web/vite.config.ts');
const runtimeConfigPlugin = read('web/vite/runtime-config-plugin.ts');

assert.doesNotMatch(
  webDockerfile,
  /RUN pnpm install/,
  'The web image must not populate /app/node_modules before the named volume mounts',
);
assert.match(
  webDockerfile,
  /pnpm config set store-dir \/pnpm\/store --global/,
  'The web dependency store must stay outside the bind-mounted source tree',
);
assert.match(
  webService,
  /^ {6}- type: volume\n {8}source: web_node_modules\n {8}target: \/app\/node_modules\n {8}volume:\n {10}nocopy: true$/m,
  'The web dependency volume must start empty without Docker copy-up',
);
assert.match(
  viteConfig,
  /usePolling:\s*true/,
  'Vite development watching must be able to poll the Windows bind mount',
);
assert.match(
  webService,
  /^ {6}VITE_USE_POLLING: "true"$/m,
  'The Windows bind mount must enable Vite polling in development',
);
assert.match(webService, /^ {4}command: pnpm dev$/m, 'The web service must run the Vite development server');
assert.match(viteConfig, /frontendAsset404ContractPlugin/, 'Vite must register the frontend asset 404 contract');
assert.match(runtimeConfigPlugin, /\/assets\//, 'The frontend asset contract must cover /assets/*');
assert.match(runtimeConfigPlugin, /\/social\//, 'The frontend asset contract must cover /social/*');
assert.match(runtimeConfigPlugin, /404\.html/, 'The frontend asset contract must serve the static 404 body');
assert.match(runtimeConfigPlugin, /statSync/, 'The frontend asset contract must distinguish files from directories');
assert.match(runtimeConfigPlugin, /\.isFile\(\)/, 'Only regular public files may fall through to Vite');

assert.match(apiService, /^ {6}- api_public_media:\/var\/www\/html\/storage\/app\/public$/m);
assert.match(apiService, /^ {6}- api_private_media:\/var\/www\/html\/storage\/app\/private$/m);
assert.equal(
  [...compose.matchAll(/^ {6}- api_public_media:\/var\/www\/html\/storage\/app\/public$/gm)].length,
  1,
  'Only the development API service may mount api_public_media',
);
assert.doesNotMatch(
  gatewayService,
  /(?:^ {6}- \.\/api:|\/var\/www\/html|\/storage(?:\/|$))/m,
  'Gateway must not mount a Laravel path',
);

const networkBlock = compose.match(/^networks:\n([\s\S]*?)(?=^volumes:)/m)?.[1];
assert.ok(networkBlock, 'compose.yaml must declare networks before volumes');
const networkNames = [...networkBlock.matchAll(/^ {2}([a-z][a-z-]*): \{\}$/gm)].map((match) => match[1]);
assert.deepEqual(networkNames, ['front', 'data', 'test']);

const volumeBlock = compose.match(/^volumes:\n([\s\S]*)$/m)?.[1];
assert.ok(volumeBlock, 'compose.yaml must declare volumes');
const volumeNames = [...volumeBlock.matchAll(/^ {2}([a-z][a-z_]*): \{\}$/gm)].map((match) => match[1]);
assert.deepEqual(volumeNames, ['web_node_modules', 'api_vendor', 'mysql_data', 'api_private_media', 'api_public_media']);

for (const path of ['infra/caddy/Caddyfile', 'infra/caddy/laravel-routes.json', 'infra/caddy/ROUTE_OWNERSHIP.md', 'docs/testing/PHASE_3_BROWSER_SMOKE.md']) {
  assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);
}

const caddy = read('infra/caddy/Caddyfile');
assert.doesNotMatch(caddy, /livewire-[0-9a-f]{8,}/i, 'Caddy must not copy a generated Livewire hash');
assert.doesNotMatch(caddy, /cloudflare|trusted_proxies/i, 'Gateway must not own Cloudflare proxy configuration');
assert.doesNotMatch(caddy, /(?:^|\n)\s*(?:root|file_server)\b/m, 'Gateway must not serve application files directly');
// Observable route contract, expressed against named matchers/handlers in Caddyfile.
assert.match(caddy, /\/es.*308/);
assert.match(caddy, /\/es\/.*308/);
assert.match(caddy, /\/en\/.*308/);
assert.match(caddy, /rewrite[^\n]*\/en\/index\.html/);
assert.match(caddy, /Cache-Control[^\n]*no-store/);
assert.match(caddy, /@livewireTechnical/);
assert.match(caddy, /@livewireAssets/);
assert.match(caddy, /handle_response/);
assert.match(caddy, /copy_response 404/);
assert.doesNotMatch(caddy, /@(?:backendNoIndex|nonIndexable)[^\n]*\/storage\/\*/);
assert.doesNotMatch(caddy, /@(?:backendNoIndex|nonIndexable)[^\n]*\/cv\/\*/);
assert.match(
  caddy,
  /\/node_modules\/\.pnpm\/\*/,
  'Gateway must whitelist Vite-emitted pnpm module imports',
);

assert.ok(existsSync(resolve(root, 'web/public/404.html')), 'Missing web/public/404.html');
assert.ok(existsSync(resolve(root, 'web/vite/runtime-config-plugin.ts')), 'Missing web/vite/runtime-config-plugin.ts');

const routeInventory = JSON.parse(read('infra/caddy/laravel-routes.json'));
assert.ok(routeInventory.some((route) => route.uri === 'api/v1'));
assert.ok(routeInventory.some((route) => /^livewire-[^/]+\//.test(route.uri)));
assert.ok(routeInventory.some((route) => route.uri === 'cv/luciano-gonzalez-es.pdf'));
assert.ok(routeInventory.some((route) => route.uri === 'cv/luciano-gonzalez-en.pdf'));
assert.match(caddy, /^\t@backendPublic path[^\n]*\/cv\/\*/m, 'The public backend matcher must cover /cv/*');

const readme = read('README.md');
assert.match(readme, /docker compose up -d --wait/);
assert.match(readme, /storage:unlink/);
assert.match(readme, /python3/);
assert.match(readme, /docker inspect/);
assert.match(readme, /HostConfig\.PortBindings/);
assert.match(readme, /com\.docker\.compose\.service/);
assert.doesNotMatch(readme, /docker compose(?: --profile test)? port/);
assert.match(readme, /Docker Desktop is the single engine|Docker Desktop es el único engine/);
assert.match(readme, /no instalar un segundo Docker Engine/);

const environment = read('docs/ENVIRONMENT.md');
assert.match(environment, /same engine|mismo engine/);
assert.match(environment, /second Ubuntu Docker Engine|segundo Docker Engine/);

const verification = read('docs/testing/PHASE_3_VERIFICATION.md');
assert.match(verification, /Destructive scope and clean bootstrap/);
assert.match(verification, /Ubuntu WSL2 parity/);
assert.match(verification, /Not observed/);
assert.doesNotMatch(verification, /entrypoint creates it/);
console.log('Repository and environment contract pass.');
