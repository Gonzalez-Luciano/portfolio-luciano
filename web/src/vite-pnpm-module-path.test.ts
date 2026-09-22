import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { afterEach, describe, expect, it } from 'vitest'
import { isVitePnpmModuleFile } from '../vite/runtime-config-plugin'

const temporaryRoots: string[] = []

const fixtureRoot = () => {
  const root = mkdtempSync(join(tmpdir(), 'portfolio-vite-pnpm-'))
  temporaryRoots.push(root)
  return root
}

afterEach(() => {
  for (const root of temporaryRoots.splice(0)) rmSync(root, { force: true, recursive: true })
})

describe('isVitePnpmModuleFile', () => {
  it('allows only regular pnpm module files inside the project node_modules root', () => {
    const root = fixtureRoot()
    const packageRoot = join(root, 'node_modules', '.pnpm', 'package@1.0.0', 'node_modules', 'package')
    const moduleFile = join(packageRoot, 'module.js')
    const outsideFile = join(root, 'private-module.js')

    mkdirSync(packageRoot, { recursive: true })
    writeFileSync(moduleFile, 'export {}')
    writeFileSync(outsideFile, 'private')

    expect(isVitePnpmModuleFile('/node_modules/.pnpm/package@1.0.0/node_modules/package/module.js', root)).toBe(true)
    expect(isVitePnpmModuleFile('/node_modules/.pnpm/package@1.0.0/node_modules/package/missing.js', root)).toBe(false)
    expect(isVitePnpmModuleFile('/node_modules/.pnpm/package@1.0.0/node_modules/package', root)).toBe(false)
    expect(isVitePnpmModuleFile('/node_modules/.pnpm/%2e%2e/%2e%2e/private-module.js', root)).toBe(false)
  })
})
