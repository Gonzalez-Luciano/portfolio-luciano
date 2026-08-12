import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../../../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');
const required = [
  'docs/design/phase-2/SITEMAP.md',
  'docs/design/phase-2/WIREFRAMES.md',
  'docs/design/phase-2/DESIGN_TOKENS.md',
  'docs/design/phase-2/RESPONSIVE_SPEC.md',
];

for (const path of required) assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);

const sitemap = read(required[0]);
for (const id of ['top', 'work', 'expertise', 'projects', 'approach', 'contact']) {
  assert.match(sitemap, new RegExp(`\\| \\x60${id}\\x60 \\|`), `Missing anchor ${id}`);
}

const wireframes = read(required[1]);
for (const heading of [
  'Hero', 'Professional introduction', 'Work cases', 'Professional experience',
  'Specializations', 'Projects', 'Technologies', 'Approach', 'Contact',
  'Mobile menu', 'Loading state', 'Recoverable section error', 'Broad site error'
]) assert.ok(wireframes.includes(`## ${heading}`), `Missing wireframe ${heading}`);

const tokens = read(required[2]);
for (const value of [
  '#EEE9DE', '#F6F2E9', '#1B1E1C', '#565B56', '#A94322', '#817B70', '#5F635E', '#E4D8C9', '#8B2E24',
  '#171918', '#202321', '#F1ECE1', '#B8B3A9', '#F07A4B', '#777A74', '#96958F', '#2B2926', '#FF9B8A',
]) assert.ok(tokens.includes(value), `Missing approved color ${value}`);

for (const literal of ['Instrument Sans', 'IBM Plex Mono', '64rem', '90rem', '44 × 44px', '3px', '2px']) {
  assert.ok(tokens.includes(literal), `Missing token contract ${literal}`);
}

const responsive = read(required[3]);
for (const literal of ['320px', '360px', '390px', '768px', '1024px', '1440px', '48rem', '64rem', '80rem', '90rem']) {
  assert.ok(responsive.includes(literal), `Missing responsive contract ${literal}`);
}

const prototypeFiles = [
  'docs/prototypes/phase-2/README.md',
  'docs/prototypes/phase-2/index.html',
  'docs/prototypes/phase-2/es/index.html',
  'docs/prototypes/phase-2/en/index.html',
  'docs/prototypes/phase-2/styles/tokens.css',
  'docs/prototypes/phase-2/styles/base.css',
  'docs/prototypes/phase-2/styles/layout.css',
  'docs/prototypes/phase-2/scripts/theme.js',
  'docs/prototypes/phase-2/scripts/main.js',
];

for (const path of prototypeFiles) assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);

const requiredContentKeys = [
  'hero.title', 'hero.value', 'hero.cta', 'hero.availability', 'intro.body',
  'work.case.integrations', 'work.case.education', 'work.case.data-automation', 'work.case.layers',
  'work.experience.primary', 'work.experience.secondary', 'expertise.list', 'projects.zero',
  'technologies.list', 'approach.body', 'contact.body', 'contact.linkedin', 'contact.github', 'contact.email',
];

const contentKeys = (html) => [...html.matchAll(/data-content-key="([^"]+)"/g)].map((match) => match[1]).sort();
const externalRuntimeReference = /(?:src|href)="https?:\/\//i;

for (const [locale, path] of [['es', prototypeFiles[2]], ['en', prototypeFiles[3]]]) {
  const page = read(path);
  assert.match(page, new RegExp(`<html[^>]+lang="${locale}"`, 'i'), `Missing ${locale} html language`);
  assert.equal((page.match(/<main\s+id="main-content"/gi) ?? []).length, 1, `Expected one main landmark in ${path}`);
  assert.equal((page.match(/<h1\b/gi) ?? []).length, 1, `Expected one H1 in ${path}`);
  assert.match(page, /<h1[^>]*>\s*Backend Developer \| PHP &(?:amp;)? Laravel\s*<\/h1>/i, `Missing hero title in ${path}`);
  for (const id of ['top', 'work', 'expertise', 'projects', 'approach', 'contact']) {
    assert.match(page, new RegExp(`id="${id}"`), `Missing shell ID ${id} in ${path}`);
  }
  assert.match(page, /<a(?=[^>]*href="#main-content")(?=[^>]*class="[^"]*skip-link)[^>]*>/, `Missing skip link in ${path}`);
  assert.ok(!externalRuntimeReference.test(page), `External runtime reference in ${path}`);
  assert.deepEqual(contentKeys(page), [...requiredContentKeys].sort(), `Unexpected content-key contract in ${path}`);
}

console.log('Phase 2 artifact contracts pass.');
