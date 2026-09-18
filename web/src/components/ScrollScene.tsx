import { ArrowRight } from 'lucide-react'
import type { UiCopy } from '@/content'
import { SceneBackdrop } from '@/components/SceneBackdrop'
import { Stagger } from '@/components/Stagger'
import { usePrefersReducedMotion } from '@/hooks/usePrefersReducedMotion'
import type { SceneContent } from '@/lib/scene'
import { SCROLL_POSTER_SRC, SCROLL_VIDEO_SRC, columnVeilOpacity, correctionFilter } from '@/lib/scene-timeline'
import { STAGGER_THRESHOLD, sectionOneOpacity, sectionThreeOpacity, sectionTwoOpacity } from '@/lib/section-opacity'
import type { useVideoScrub } from '@/useVideoScrub'

type Props = {
  scrub: ReturnType<typeof useVideoScrub>
  scene: SceneContent | null
  status: 'loading' | 'ready' | 'error'
  ui: UiCopy
  onRetry: () => void
}

// Fully transparent beats leave the tab order; their copy stays in the sr-only block.
const inertWhenHidden = (opacity: number) => (opacity === 0 ? { inert: '' } : {})

export function ScrollScene({ scrub, scene, status, ui, onRetry }: Props) {
  const { containerRef, videoRef, canvasRef, scrollProgress: p, canvasLive } = scrub
  const reducedMotion = usePrefersReducedMotion()

  const o1 = sectionOneOpacity(p)
  const o2 = sectionTwoOpacity(p)
  const o3 = sectionThreeOpacity(p)
  const media = 'absolute inset-0 h-full w-full object-cover object-[70%_50%] lg:object-center'

  return (
    <div ref={containerRef} id="top" className="relative h-[500vh]">
      <div className="sticky top-0 h-screen w-full overflow-hidden bg-[#F5EFE6]">
        <div className="absolute inset-0" style={{ filter: correctionFilter(p) }}>
          <video ref={videoRef} src={SCROLL_VIDEO_SRC} poster={SCROLL_POSTER_SRC} muted playsInline preload="auto" aria-hidden="true" className={media} />
          <canvas
            ref={canvasRef}
            width={848}
            height={480}
            aria-hidden="true"
            className={`${media} transition-opacity duration-300 ${canvasLive ? 'opacity-100' : 'opacity-0'}`}
          />
        </div>

        <SceneBackdrop progress={p} reducedMotion={reducedMotion} />

        {/*
          The solid stop tracks the text column's own right edge exactly: the column sits
          `--scene-gutter` in from this box's edge and is `--scene-col` of the remaining width
          (1 = full width below lg, 0.4 = the lg:max-w-[40%] column), so `gutter + col * (100% -
          2 * gutter)` is that edge in this element's own coordinate space, with a small buffer
          past it. Nesting in the same `mx-auto max-w-content` box as the text row (below) keeps
          both aligned at any viewport width, not just the 375/1440px points this was measured at.
        */}
        <div aria-hidden="true" className="pointer-events-none absolute inset-0 mx-auto max-w-content">
          <div
            className="h-full w-full [--scene-col:1] [--scene-gutter:1.5rem] sm:[--scene-gutter:2.5rem] lg:[--scene-col:0.4] lg:[--scene-gutter:4rem]"
            style={{
              opacity: columnVeilOpacity(p),
              background:
                'linear-gradient(to right, #1A1411 0%, #1A1411 calc(var(--scene-gutter) + var(--scene-col) * (100% - 2 * var(--scene-gutter)) + 0.5rem), rgba(26, 20, 17, 0) 100%)',
            }}
          />
        </div>

        {scene && (
          <div className="sr-only">
            <h1>{scene.headline}</h1>
            {scene.photo && <img src={scene.photo.url} alt={scene.photo.alt} />}
            <p>{scene.name}</p>
            <p>{scene.statement ? `${scene.statement.lead} ${scene.statement.emphasis} ${scene.statement.tail}` : scene.summary}</p>
            {scene.eyebrow && <p>{scene.eyebrow}</p>}
            <h2>{scene.closing.lineTwo ? `${scene.closing.lineOne} ${scene.closing.lineTwo}` : scene.closing.lineOne}</h2>
          </div>
        )}

        {!scene && <h1 className="sr-only">{status === 'error' ? ui.failure : ui.loading}</h1>}

        {scene?.email && (
          // A real, keyboard-focusable link (unlike the decorative one in beat 3): reveals
          // itself on focus instead of staying clipped, per WCAG 2.4.7.
          <a
            href={scene.email.href}
            className="sr-only focus:not-sr-only focus:absolute focus:left-6 focus:top-6 focus:z-50 focus:rounded-full focus:bg-scene-veil focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-scene-light"
          >
            {scene.email.label}
          </a>
        )}

        <div className="pointer-events-none relative mx-auto flex h-full max-w-content items-center px-6 sm:px-10 lg:px-16">
          <div className="grid w-full lg:max-w-[40%]">
            {status === 'loading' && (
              <p className="label text-scene-ink" style={{ gridArea: '1 / 1' }} role="status">
                {ui.loading}
              </p>
            )}

            {status === 'error' && (
              <div role="alert" className="pointer-events-auto flex flex-col items-start gap-6 text-scene-ink" style={{ gridArea: '1 / 1' }}>
                <p className="label">{ui.failure}</p>
                <button type="button" onClick={onRetry} className="min-h-11 rounded-full border border-scene-ink px-6 text-sm font-semibold">
                  {ui.retry}
                </button>
              </div>
            )}

            {scene && (
              <>
                <section aria-hidden="true" {...inertWhenHidden(o1)} style={{ gridArea: '1 / 1', opacity: o1 }}>
                  {scene.photo && (
                    <Stagger visible={o1 > STAGGER_THRESHOLD} delay={0}>
                      <img
                        src={scene.photo.url}
                        alt=""
                        width={128}
                        height={128}
                        decoding="async"
                        className="h-24 w-24 rounded-full border border-scene-accent object-cover lg:h-32 lg:w-32"
                      />
                    </Stagger>
                  )}
                  <Stagger visible={o1 > STAGGER_THRESHOLD} delay={120}>
                    <p className="label mt-6 text-scene-ink">{scene.name}</p>
                  </Stagger>
                  <Stagger visible={o1 > STAGGER_THRESHOLD} delay={240}>
                    <p className="mt-3 font-display text-[clamp(2.25rem,5vw,4.5rem)] font-light leading-[1.05] text-scene-ink">{scene.headline}</p>
                  </Stagger>
                </section>

                <section aria-hidden="true" {...inertWhenHidden(o2)} style={{ gridArea: '1 / 1', opacity: o2 }}>
                  <Stagger visible={o2 > STAGGER_THRESHOLD} delay={0}>
                    <p className="font-display text-[clamp(1.75rem,3.4vw,3.25rem)] font-light leading-tight text-scene-ink">
                      {scene.statement ? (
                        <>
                          {scene.statement.lead} <em className="italic text-scene-accent">{scene.statement.emphasis}</em>{' '}
                          <span className="text-scene-muted">{scene.statement.tail}</span>
                        </>
                      ) : (
                        scene.summary
                      )}
                    </p>
                  </Stagger>
                </section>

                <section aria-hidden="true" {...inertWhenHidden(o3)} style={{ gridArea: '1 / 1', opacity: o3 }}>
                  {scene.eyebrow && (
                    <Stagger visible={o3 > STAGGER_THRESHOLD} delay={0}>
                      <p className="label text-scene-light">{scene.eyebrow}</p>
                    </Stagger>
                  )}
                  <Stagger visible={o3 > STAGGER_THRESHOLD} delay={150}>
                    <p className="mt-4 font-display text-[clamp(2rem,4vw,3.75rem)] font-light leading-[1.1] text-scene-light">
                      {scene.closing.lineOne}
                      {scene.closing.lineTwo && (
                        <>
                          <br />
                          <span className="italic text-scene-glow">{scene.closing.lineTwo}</span>
                        </>
                      )}
                    </p>
                  </Stagger>
                  {scene.email && (
                    <Stagger visible={o3 > STAGGER_THRESHOLD} delay={300}>
                      {/* Not focusable: keyboard and screen reader users reach the same email in Contact. */}
                      <a
                        href={scene.email.href}
                        tabIndex={-1}
                        className={`mt-8 inline-flex min-h-11 items-center gap-3 font-mono text-xs font-medium uppercase tracking-[0.18em] text-scene-light ${o3 > STAGGER_THRESHOLD ? 'pointer-events-auto' : ''}`}
                      >
                        {scene.email.label}
                        <ArrowRight size={16} aria-hidden="true" />
                      </a>
                    </Stagger>
                  )}
                </section>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
