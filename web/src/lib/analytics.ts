export type RuntimeConfig = Readonly<{
  umami: Readonly<{
    trackerUrl: string
    websiteId: string
  }>
}>

export type AnalyticsStatus = 'enabled' | 'off'

export type AnalyticsDependencies = Readonly<{
  loadConfig: () => Promise<RuntimeConfig | null>
  mountTracker: (config: RuntimeConfig['umami']) => Promise<void>
}>

const NIL_UUID = Array.from({ length: 32 }, () => '0')
  .join('')
  .replace(/^(.{8})(.{4})(.{4})(.{4})(.{12})$/, '$1-$2-$3-$4-$5')
const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i
const TRACKER_MARKER = 'data-portfolio-analytics'

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function isTrackerUrl(value: unknown): value is string {
  if (typeof value !== 'string') return false

  try {
    const url = new URL(value)
    return (url.protocol === 'http:' || url.protocol === 'https:') && url.username === '' && url.password === ''
  } catch {
    return false
  }
}

function isWebsiteId(value: unknown): value is string {
  return typeof value === 'string' && value !== NIL_UUID && UUID_PATTERN.test(value)
}

export function parseRuntimeConfig(value: unknown): RuntimeConfig | null {
  if (!isRecord(value) || !isRecord(value.umami)) return null
  const { trackerUrl, websiteId } = value.umami
  if (!isTrackerUrl(trackerUrl) || !isWebsiteId(websiteId)) return null

  return { umami: { trackerUrl, websiteId } }
}

export async function loadRuntimeConfig(fetcher: typeof fetch = fetch): Promise<RuntimeConfig | null> {
  try {
    const response = await fetcher('/runtime-config.json', { cache: 'no-store', credentials: 'omit' })
    if (!response.ok) return null
    return parseRuntimeConfig(await response.json())
  } catch {
    return null
  }
}

export function trackerAttributes(config: RuntimeConfig['umami']): Readonly<Record<string, string>> {
  return {
    'data-website-id': config.websiteId,
    'data-domains': 'lucianogonzalez.dev',
    'data-do-not-track': 'true',
    'data-exclude-search': 'true',
    'data-exclude-hash': 'true',
  }
}

function mountTracker(config: RuntimeConfig['umami']): Promise<void> {
  if (document.head.querySelector(`script[${TRACKER_MARKER}="umami"]`)) return Promise.resolve()

  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.async = true
    script.defer = true
    script.src = config.trackerUrl
    script.setAttribute(TRACKER_MARKER, 'umami')
    for (const [name, value] of Object.entries(trackerAttributes(config))) script.setAttribute(name, value)
    script.addEventListener('load', () => resolve(), { once: true })
    script.addEventListener(
      'error',
      () => {
        script.remove()
        reject(new Error('Umami tracker failed to load.'))
      },
      { once: true },
    )
    document.head.append(script)
  })
}

export function createAnalyticsBootstrap(dependencies: AnalyticsDependencies): () => Promise<AnalyticsStatus> {
  let result: Promise<AnalyticsStatus> | null = null

  return () => {
    if (result) return result
    result = (async () => {
      try {
        const config = await dependencies.loadConfig()
        if (!config) return 'off'
        await dependencies.mountTracker(config.umami)
        return 'enabled'
      } catch {
        return 'off'
      }
    })()
    return result
  }
}

export const bootstrapAnalytics = createAnalyticsBootstrap({ loadConfig: loadRuntimeConfig, mountTracker })
