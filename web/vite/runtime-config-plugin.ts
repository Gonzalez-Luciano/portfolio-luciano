import { existsSync } from 'node:fs'
import { resolve } from 'node:path'
import type { Plugin } from 'vite'

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
