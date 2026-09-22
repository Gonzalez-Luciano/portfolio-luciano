/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'
import { runtimeConfigContractPlugin } from './vite/runtime-config-plugin.ts'
import { localizedSeoPlugin } from './vite/seo-plugin.ts'

// Inside Docker the browser reaches Vite through the portfolio gateway, so HMR
// must connect back to the published gateway port instead of the internal 5173.
const hmrClientPort = Number(process.env.VITE_HMR_CLIENT_PORT) || undefined
const usePolling = process.env.VITE_USE_POLLING === 'true'

export default defineConfig({
  base: '/',
  plugins: [react(), localizedSeoPlugin(), runtimeConfigContractPlugin()],
  build: {
    rollupOptions: {
      input: {
        index: fileURLToPath(new URL('./index.html', import.meta.url)),
        en: fileURLToPath(new URL('./en/index.html', import.meta.url)),
      },
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    hmr: hmrClientPort ? { clientPort: hmrClientPort } : undefined,
    watch: usePolling ? { usePolling: true, interval: 300 } : undefined,
  },
  test: {
    environment: 'node',
    include: ['src/**/*.test.ts'],
  },
})
