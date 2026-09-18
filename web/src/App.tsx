import { useCallback, useEffect, useMemo, useState } from 'react'
import { ScrollScene } from '@/components/ScrollScene'
import { resolveLocale, uiCopy } from '@/content'
import { useRegion } from '@/hooks/useRegion'
import { loadStructuralContent, type StructuralContent } from '@/lib/api'
import { buildScene } from '@/lib/scene'
import { SCROLL_VIDEO_SRC } from '@/lib/scene-timeline'
import { useVideoScrub } from '@/useVideoScrub'

type StructuralState = { status: 'loading' } | { status: 'ready'; content: StructuralContent } | { status: 'error' }

export default function App() {
  const locale = useMemo(() => resolveLocale(window.location.pathname), [])
  const ui = uiCopy(locale)
  const scrub = useVideoScrub(SCROLL_VIDEO_SRC)
  const [structural, setStructural] = useState<StructuralState>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)
  const technologies = useRegion(locale, 'technologies')

  useEffect(() => {
    document.documentElement.lang = locale
  }, [locale])

  useEffect(() => {
    const controller = new AbortController()
    setStructural({ status: 'loading' })
    loadStructuralContent(locale, controller.signal)
      .then((content) => setStructural({ status: 'ready', content }))
      .catch(() => {
        if (!controller.signal.aborted) setStructural({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, attempt])

  const content = structural.status === 'ready' ? structural.content : null

  useEffect(() => {
    if (content) document.title = `${content.profile.name} — ${content.profile.headline}`
  }, [content])

  const scene = useMemo(
    () =>
      content
        ? buildScene({ ...content, technologies: technologies.state.status === 'ready' ? technologies.state.data : [] })
        : null,
    [content, technologies.state],
  )

  const retryStructural = useCallback(() => setAttempt((value) => value + 1), [])

  return (
    <main id="content" tabIndex={-1} className="outline-none" aria-busy={structural.status === 'loading'}>
      <ScrollScene scrub={scrub} scene={scene} status={structural.status} ui={ui} onRetry={retryStructural} />
    </main>
  )
}
