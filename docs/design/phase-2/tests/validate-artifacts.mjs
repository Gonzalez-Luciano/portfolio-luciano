import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../../../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');
const required = [
  'docs/design/phase-2/SITEMAP.md',
  'docs/design/phase-2/WIREFRAMES.md',
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

console.log('Phase 2 artifact contracts pass.');
