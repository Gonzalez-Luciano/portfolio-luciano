import { existsSync, readFileSync, realpathSync, statSync } from 'node:fs'
import type { IncomingMessage, ServerResponse } from 'node:http'
import { isAbsolute, relative, resolve } from 'node:path'
import type { Plugin } from 'vite'

const frontendPublicAssetPrefixes = ['/assets/', '/media/', '/social/']
const vitePnpmModulePrefix = '/node_modules/.pnpm/'

const isFrontendAssetPath = (pathname: string) =>
  frontendPublicAssetPrefixes.some((prefix) => pathname.startsWith(prefix))

const isRegularFile = (path: string) => existsSync(path) && statSync(path).isFile()

const isInside = (root: string, candidate: string) => {
  const relativePath = relative(root, candidate)

  return relativePath !== ''
    && relativePath !== '..'
    && !relativePath.startsWith('../')
    && !relativePath.startsWith('..\\')
    && !isAbsolute(relativePath)
}

export function isVitePnpmModuleFile(pathname: string, projectRoot: string): boolean {
  if (!pathname.startsWith(vitePnpmModulePrefix)) return false

  try {
    const decodedPathname = decodeURIComponent(pathname)
    if (!decodedPathname.startsWith(vitePnpmModulePrefix)) return false

    const nodeModulesRoot = resolve(projectRoot, 'node_modules')
    const candidate = resolve(projectRoot, decodedPathname.slice(1))

    return isInside(nodeModulesRoot, candidate)
      && isRegularFile(candidate)
      && isInside(realpathSync(nodeModulesRoot), realpathSync(candidate))
  } catch {
    return false
  }
}

export function runtimeConfigContractPlugin(): Plugin {
  return {
    name: 'runtime-config-contract',
    configureServer(server) {
      server.middlewares.use((request, response, next) => {
        const pathname = new URL(request.url ?? '/', 'http://vite.local').pathname

        if (pathname !== '/runtime-config.json') return next()

        response.setHeader('Cache-Control', 'no-store')
        response.setHeader('X-Robots-Tag', 'noindex, nofollow')

        if (!existsSync(resolve(server.config.publicDir, 'runtime-config.json'))) {
          response.statusCode = 404
          response.end()
          return
        }

        next()
      })
    },
  }
}

export function frontendAsset404ContractPlugin(): Plugin {
  return {
    name: 'frontend-asset-404-contract',
    configureServer(server) {
      const notFoundPage = resolve(server.config.publicDir, '404.html')

      const sendNotFound = (request: IncomingMessage, response: ServerResponse) => {
        response.statusCode = 404
        response.setHeader('Content-Type', 'text/html; charset=utf-8')
        response.setHeader('X-Robots-Tag', 'noindex, nofollow')
        response.end(request.method === 'HEAD' ? undefined : readFileSync(notFoundPage))
      }

      server.middlewares.use((request, response, next) => {
        const pathname = new URL(request.url ?? '/', 'http://vite.local').pathname

        if (isFrontendAssetPath(pathname)) {
          if (isRegularFile(resolve(server.config.publicDir, pathname.slice(1)))) return next()
          return sendNotFound(request, response)
        }

        if (pathname.startsWith(vitePnpmModulePrefix)) {
          if (isVitePnpmModuleFile(pathname, server.config.root)) return next()
          return sendNotFound(request, response)
        }

        return next()
      })
    },
  }
}
