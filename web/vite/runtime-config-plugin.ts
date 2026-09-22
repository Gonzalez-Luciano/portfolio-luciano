import { existsSync, readFileSync, statSync } from 'node:fs'
import { resolve } from 'node:path'
import type { Plugin } from 'vite'

const frontendAssetPrefixes = ['/assets/', '/media/', '/social/']

const isFrontendAssetPath = (pathname: string) =>
  frontendAssetPrefixes.some((prefix) => pathname.startsWith(prefix))

const isRegularFile = (path: string) => existsSync(path) && statSync(path).isFile()

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

      server.middlewares.use((request, response, next) => {
        const pathname = new URL(request.url ?? '/', 'http://vite.local').pathname

        if (!isFrontendAssetPath(pathname)) return next()
        if (isRegularFile(resolve(server.config.publicDir, pathname.slice(1)))) return next()

        response.statusCode = 404
        response.setHeader('Content-Type', 'text/html; charset=utf-8')
        response.setHeader('X-Robots-Tag', 'noindex, nofollow')
        response.end(request.method === 'HEAD' ? undefined : readFileSync(notFoundPage))
      })
    },
  }
}
