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

// --- Production runtime contract (Phase 11) -------------------------------
// The production runtime must stay portable: it runs identically on a
// workstation and on the VPS, and it never carries host, Cloudflare or
// operations-owned configuration.

for (const path of [
  'compose.production.yaml',
  '.env.production.example',
  'infra/caddy/Caddyfile.production',
  'infra/docker/api/Dockerfile.production',
  'infra/docker/api/production-entrypoint.sh',
  'infra/docker/api/php-production.ini',
  'infra/docker/web/Dockerfile',
  'infra/docker/web/Caddyfile',
  'infra/docker/web/entrypoint.sh',
  'infra/docker/gateway/Dockerfile',
  'infra/validation/smoke-production.sh',
]) {
  assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);
}

const production = read('compose.production.yaml');

assert.doesNotMatch(production, /CLOUDFLARE|TUNNEL|cloudflared/i, 'Production Compose must not reference Cloudflare');
assert.doesNotMatch(production, /\/srv\b/, 'Production Compose must not depend on a VPS path');
assert.doesNotMatch(production, /network_mode:\s*host/i, 'Production Compose must not use host networking');
assert.doesNotMatch(production, /^\s+env_file:/m, 'Production secrets must not be blanket-injected from a file');
assert.doesNotMatch(production, /(^|\n)\s*-\s*[^\n]*:3306[^\n]*$/m, 'MySQL must not publish port 3306');
assert.equal(
  [...production.matchAll(/^ {4}ports:$/gm)].length,
  1,
  'Only the gateway may publish a host port in production',
);
assert.match(
  production,
  /^ {2}gateway:\n[\s\S]*?^ {4}ports:\n {6}- "\$\{GATEWAY_HOST:-127\.0\.0\.1\}:\$\{GATEWAY_PORT:-8000\}:80"$/m,
  'The production entrypoint must default to the loopback contract 127.0.0.1:8000',
);

// Production must not mount source code: the images are immutable artifacts.
assert.doesNotMatch(
  production,
  /^\s+- \.\/(?:api|web|docs|infra)\b/m,
  'Production Compose must not bind mount repository source',
);

// Persistent units the deployment depends on.
for (const volume of ['mysql_data', 'api_private_media', 'api_public_media']) {
  assert.match(production, new RegExp(`^ {2}${volume}: \\{\\}$`, 'm'), `Missing production volume ${volume}`);
}

// MySQL is an official image pinned to an exact patch version, never `latest`
// and never a custom build.
const mysqlImage = production.match(/^ {4}image: (mysql:[^\s]+)$/m)?.[1];
assert.ok(mysqlImage, 'Production MySQL must declare an official image');
assert.match(mysqlImage, /^mysql:\d+\.\d+\.\d+$/, 'Production MySQL must be pinned to a patch version');

// Debug output must never be enabled for a production container.
assert.match(production, /^ {6}APP_DEBUG: "false"$/m, 'APP_DEBUG must be false in production');
assert.doesNotMatch(production, /^ {6}APP_ENV: (?!production$)/m, 'APP_ENV must be production');

// Secrets arrive from the environment and are never defaulted to a value.
for (const secret of ['APP_KEY', 'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD']) {
  assert.match(
    production,
    new RegExp(`\\$\\{${secret}:\\?`),
    `${secret} must be a required runtime value without a default`,
  );
}

const productionExample = read('.env.production.example');
assert.doesNotMatch(productionExample, /CLOUDFLARE|TUNNEL/i);
assert.doesNotMatch(productionExample, /\/srv\b/);
for (const variable of ['APP_KEY', 'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD']) {
  assert.match(
    productionExample,
    new RegExp(`^${variable}=.*replace-with-`, 'm'),
    `${variable} must stay a placeholder in .env.production.example`,
  );
}
// Public analytics values are not secrets, and an absent value means OFF.
for (const variable of ['UMAMI_TRACKER_URL', 'UMAMI_WEBSITE_ID']) {
  assert.match(productionExample, new RegExp(`^${variable}=$`, 'm'), `${variable} must ship empty`);
}

const productionCaddy = read('infra/caddy/Caddyfile.production');
assert.doesNotMatch(productionCaddy, /cloudflare|trusted_proxies\s+\S*cloudflare/i, 'Gateway must not own Cloudflare configuration');
assert.doesNotMatch(productionCaddy, /livewire-[0-9a-f]{8,}/i, 'Caddy must not copy a generated Livewire hash');
assert.doesNotMatch(
  productionCaddy,
  /(?:^|\n)\s*(?:root|file_server)\b/m,
  'Gateway must not serve application files directly',
);
// Development-only Vite paths must not survive into the production routing.
for (const devPath of ['/@vite/', '/@react-refresh', '/src/', '/node_modules/']) {
  assert.ok(!productionCaddy.includes(devPath), `Production gateway must not expose ${devPath}`);
}
assert.match(productionCaddy, /\/es.*308/);
assert.match(productionCaddy, /\/en\/.*308/);
assert.match(productionCaddy, /rewrite[^\n]*\/en\/index\.html/);
assert.match(productionCaddy, /Cache-Control[^\n]*no-store/);
assert.match(productionCaddy, /@livewireTechnical/);
assert.match(productionCaddy, /@livewireAssets/);
assert.match(productionCaddy, /copy_response 404/);
assert.match(productionCaddy, /^\t@backendPublic path[^\n]*\/cv\/\*/m, 'The public backend matcher must cover /cv/*');

// The static web runtime keeps the same runtime-config contract the Vite dev
// server enforces: never cacheable, present or absent.
const webRuntimeCaddy = read('infra/docker/web/Caddyfile');
assert.match(webRuntimeCaddy, /handle \/runtime-config\.json/);
assert.equal(
  [...webRuntimeCaddy.matchAll(/Cache-Control "no-store"/g)].length,
  2,
  'Both the present and the absent runtime configuration must be no-store',
);
assert.match(webRuntimeCaddy, /status 404/, 'Missing frontend files must return a real 404');

// The materialization never writes a partial configuration and never bakes the
// values into the image.
const webEntrypoint = read('infra/docker/web/entrypoint.sh');
assert.match(webEntrypoint, /UMAMI_TRACKER_URL/);
assert.match(webEntrypoint, /UMAMI_WEBSITE_ID/);
assert.match(webEntrypoint, /rm -f "\$target"/, 'An incomplete configuration must remove the file');
assert.match(webEntrypoint, /mv "\$temporary" "\$target"/, 'The configuration must be written atomically');

const webProductionDockerfile = read('infra/docker/web/Dockerfile');
assert.match(webProductionDockerfile, /pnpm build/, 'The production web image must build the SPA');
assert.doesNotMatch(webProductionDockerfile, /pnpm dev/, 'The production web image must not run a dev server');

const apiProductionDockerfile = read('infra/docker/api/Dockerfile.production');
assert.match(apiProductionDockerfile, /--no-dev/, 'Production Composer install must exclude dev dependencies');
assert.doesNotMatch(apiProductionDockerfile, /\/srv\b/);

const apiProductionEntrypoint = read('infra/docker/api/production-entrypoint.sh');
assert.doesNotMatch(
  apiProductionEntrypoint,
  /artisan\s+(?:migrate|db:seed|portfolio:import-initial-content|portfolio:bootstrap-admin)/,
  'Container start must never run migrations, seeds, the initial import or the admin bootstrap',
);

// CI and release automation must never reach the VPS.
for (const workflow of ['.github/workflows/ci.yml', '.github/workflows/release.yml']) {
  assert.ok(existsSync(resolve(root, workflow)), `Missing ${workflow}`);
  const content = read(workflow);
  assert.doesNotMatch(content, /\bssh\b|scp |rsync|known_hosts|SSH_PRIVATE_KEY/i, `${workflow} must not access a server`);
  assert.doesNotMatch(content, /CLOUDFLARE/i, `${workflow} must not use Cloudflare credentials`);
  assert.doesNotMatch(content, /\/srv\b/, `${workflow} must not reference VPS paths`);
}

console.log('Repository and environment contract pass.');
