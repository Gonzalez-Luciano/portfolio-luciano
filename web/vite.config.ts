/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// Inside Docker the browser reaches Vite through the portfolio gateway, so HMR
// must connect back to the published gateway port instead of the internal 5173.
const hmrClientPort = Number(process.env.VITE_HMR_CLIENT_PORT) || undefined
const usePolling = process.env.VITE_USE_POLLING === 'true'

export default defineConfig({
  base: '/',
  plugins: [react()],
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
