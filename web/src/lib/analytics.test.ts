import { describe, expect, it, vi } from 'vitest'
import {
  createAnalyticsBootstrap,
  loadRuntimeConfig,
  parseRuntimeConfig,
  trackerAttributes,
  type RuntimeConfig,
} from '@/lib/analytics'

const validConfig: RuntimeConfig = {
  umami: {
    trackerUrl: 'https://analytics.example.test/script.js',
    websiteId: '11111111-1111-4111-8111-111111111111',
  },
}

describe('runtime analytics configuration', () => {
  it('accepts the complete public schema and ignores unknown properties', () => {
    expect(
      parseRuntimeConfig({
        umami: {
          ...validConfig.umami,
          ignored: true,
        },
      }),
    ).toEqual(validConfig)
  })

  it.each([
    { umami: { trackerUrl: 'javascript:alert(1)', websiteId: validConfig.umami.websiteId } },
    { umami: { trackerUrl: 'https://user:pass@analytics.example.test/script.js', websiteId: validConfig.umami.websiteId } },
    { umami: { trackerUrl: validConfig.umami.trackerUrl, websiteId: '00000000-0000-0000-0000-000000000000' } },
    { umami: { trackerUrl: validConfig.umami.trackerUrl } },
  ])('rejects invalid or partial config %#', (value) => {
    expect(parseRuntimeConfig(value)).toBeNull()
  })

  it('loads runtime config without caching or credentials', async () => {
    const fetcher = vi.fn().mockResolvedValue({ ok: true, json: async () => validConfig })

    await expect(loadRuntimeConfig(fetcher as unknown as typeof fetch)).resolves.toEqual(validConfig)
    expect(fetcher).toHaveBeenCalledWith('/runtime-config.json', { cache: 'no-store', credentials: 'omit' })
  })

  it.each([
    () => Promise.reject(new Error('offline')),
    () => Promise.resolve({ ok: false }),
    () => Promise.resolve({ ok: true, json: () => Promise.reject(new Error('bad json')) }),
    () => Promise.resolve({ ok: true, json: async () => ({ umami: { trackerUrl: validConfig.umami.trackerUrl } }) }),
  ])('turns config retrieval failures into off %#', async (factory) => {
    await expect(loadRuntimeConfig(vi.fn(factory) as unknown as typeof fetch)).resolves.toBeNull()
  })

  it('uses the exact privacy-preserving tracker attributes', () => {
    expect(trackerAttributes(validConfig.umami)).toEqual({
      'data-website-id': validConfig.umami.websiteId,
      'data-domains': 'lucianogonzalez.dev',
      'data-do-not-track': 'true',
      'data-exclude-search': 'true',
      'data-exclude-hash': 'true',
    })
  })

  it('shares one initialization and never retries after a tracker failure', async () => {
    let resolveConfig: ((value: RuntimeConfig | null) => void) | undefined
    const loadConfig = vi.fn(
      () =>
        new Promise<RuntimeConfig | null>((resolve) => {
          resolveConfig = resolve
        }),
    )
    const mountTracker = vi.fn().mockRejectedValue(new Error('tracker unavailable'))
    const bootstrap = createAnalyticsBootstrap({ loadConfig, mountTracker })

    const first = bootstrap()
    const second = bootstrap()
    expect(first).toBe(second)
    expect(loadConfig).toHaveBeenCalledOnce()

    resolveConfig?.(validConfig)
    await expect(first).resolves.toBe('off')
    await expect(bootstrap()).resolves.toBe('off')
    expect(mountTracker).toHaveBeenCalledOnce()
  })
})
