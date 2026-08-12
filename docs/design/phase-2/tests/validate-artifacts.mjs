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

console.log('Phase 2 artifact contracts pass.');
