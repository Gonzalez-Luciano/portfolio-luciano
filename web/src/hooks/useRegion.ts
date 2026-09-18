import { useCallback, useEffect, useState } from 'react'
import { loadRegion, type Locale, type Region, type RegionData } from '@/lib/api'

export type RegionState<T> = { status: 'loading' } | { status: 'ready'; data: T } | { status: 'error' }

/** Loads one regional collection; its failure never affects the rest of the page. */
export function useRegion<R extends Region>(locale: Locale, region: R): { state: RegionState<RegionData[R]>; retry: () => void } {
  const [state, setState] = useState<RegionState<RegionData[R]>>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    setState({ status: 'loading' })
    loadRegion(locale, region, controller.signal)
      .then((data) => setState({ status: 'ready', data }))
      .catch(() => {
        if (!controller.signal.aborted) setState({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, region, attempt])

  const retry = useCallback(() => setAttempt((value) => value + 1), [])

  return { state, retry }
}
