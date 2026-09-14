import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type CSSProperties,
  type ReactNode,
} from 'react'
import { ArrowDown, ArrowRight, ChevronUp, Info, X } from 'lucide-react'
import { resolveLocale, uiCopy, type UiCopy } from '@/content'
import { loadPublicContent } from '@/lib/api'
import { buildScene, type SceneContent } from '@/lib/scene'
import {
  NAV_LIGHT_THRESHOLD,
  STAGGER_THRESHOLD,
  sectionOneOpacity,
  sectionThreeOpacity,
  sectionTwoOpacity,
} from '@/lib/section-opacity'
import { useVideoScrub } from '@/useVideoScrub'

const DARK = '#1D3045'
const VIDEO_SRC =
  'https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260821_114821_a8ca298f-be2c-4613-a4dd-51b69e16bbde.mp4'

const ENTRANCE_EASE = 'cubic-bezier(0.16,1,0.3,1)'
const MENU_EASE = 'cubic-bezier(0.4,0,0.2,1)'

// Section 1 holds fully through 0.20, section 2 through 0.40-0.55, section 3 from 0.75.
const SECTION_TWO_PROGRESS = 0.45
const SECTION_THREE_PROGRESS = 0.8

type SceneState = { status: 'loading' } | { status: 'ready'; scene: SceneContent } | { status: 'error' }

type NavItem = { key: string; label: string; href: string; active: boolean; external: boolean }

// Only the fully transparent section leaves the accessibility tree and tab order.
const inertWhenHidden = (opacity: number) => (opacity === 0 ? { inert: '' } : {})

const externalProps = (external: boolean) => (external ? { target: '_blank', rel: 'noopener noreferrer' } : {})

function navItems(scene: SceneContent | null): NavItem[] {
  if (!scene) return []
  return [
    { key: 'home', label: scene.name, href: '#', active: true, external: false },
    ...scene.links.map((link) => ({
      key: link.key,
      label: link.label,
      href: link.href,
      active: false,
      external: link.key !== 'email',
    })),
  ]
}

function Stagger({
  visible,
  delay,
  className,
  children,
}: {
  visible: boolean
  delay: number
  className?: string
  children: ReactNode
}) {
  const [ready, setReady] = useState(false)

  useEffect(() => {
    let second = 0
    const first = requestAnimationFrame(() => {
      second = requestAnimationFrame(() => setReady(true))
    })
    return () => {
      cancelAnimationFrame(first)
      cancelAnimationFrame(second)
    }
  }, [])

  const shown = ready && visible

  return (
    <div
      className={className}
      style={{
        opacity: shown ? 1 : 0,
        transform: shown ? 'translateY(0)' : 'translateY(24px)',
        transition: `opacity 0.8s ${ENTRANCE_EASE} ${delay}ms, transform 0.8s ${ENTRANCE_EASE} ${delay}ms`,
      }}
    >
      {children}
    </div>
  )
}

function entrance(mounted: boolean, delay: number): CSSProperties {
  return {
    opacity: mounted ? 1 : 0,
    transform: mounted ? 'translateY(0)' : 'translateY(-12px)',
    transition: `opacity 0.6s ${ENTRANCE_EASE} ${delay}ms, transform 0.6s ${ENTRANCE_EASE} ${delay}ms`,
  }
}

function Navbar({
  ui,
  items,
  cv,
  isLight,
  menuOpen,
  onOpenMenu,
}: {
  ui: UiCopy
  items: NavItem[]
  cv: SceneContent['cv']
  isLight: boolean
  menuOpen: boolean
  onOpenMenu: () => void
}) {
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    const id = window.setTimeout(() => setMounted(true), 200)
    return () => window.clearTimeout(id)
  }, [])

  const navColor = isLight ? '#FFFFFF' : DARK
  const inverseColor = isLight ? DARK : '#FFFFFF'

  return (
    <nav
      aria-label={ui.navLabel}
      className="pointer-events-auto absolute left-0 right-0 top-0 z-50 flex items-center justify-between px-6 pb-6 pt-8 transition-colors duration-500 sm:px-8 sm:pt-12 md:px-12"
      style={{ color: navColor }}
    >
      <ul className="hidden items-center gap-8 lg:flex xl:gap-10">
        {items.map((item, i) => (
          <li key={item.key} style={entrance(mounted, i * 80 + 100)}>
            <a
              href={item.href}
              aria-current={item.active ? 'page' : undefined}
              className="relative text-xs font-medium uppercase tracking-[0.15em] transition-opacity hover:opacity-70"
              {...externalProps(item.external)}
            >
              {item.label}
              {item.active && (
                <span aria-hidden="true" className="absolute -bottom-3 left-0 h-[2px] w-full bg-current" />
              )}
            </a>
          </li>
        ))}
      </ul>

      <button
        type="button"
        onClick={onOpenMenu}
        aria-label={ui.menu.label}
        aria-expanded={menuOpen}
        className="flex flex-col gap-[5px] lg:hidden"
        style={entrance(mounted, 100)}
      >
        <span className="block h-[2px] w-6 bg-current" />
        <span className="block h-[2px] w-6 bg-current" />
        <span className="block h-[2px] w-4 bg-current" />
      </button>

      <div className="hidden items-center gap-8 sm:flex" style={entrance(mounted, 500)}>
        {cv && (
          <a
            href={cv.url}
            aria-label={cv.label}
            className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] transition-opacity hover:opacity-70"
          >
            CV
            <span
              aria-hidden="true"
              className="flex h-5 w-5 items-center justify-center rounded-full transition-colors duration-500"
              style={{ backgroundColor: navColor, color: inverseColor }}
            >
              <Info size={10} />
            </span>
          </a>
        )}
        <span className="hidden text-xs font-medium uppercase tracking-[0.2em] lg:inline">{ui.menu.label}</span>
        <button
          type="button"
          onClick={onOpenMenu}
          aria-expanded={menuOpen}
          className="text-xs font-medium uppercase tracking-[0.2em] transition-opacity hover:opacity-70 lg:hidden"
        >
          {ui.menu.label}
        </button>
      </div>
    </nav>
  )
}

function MobileMenu({
  ui,
  items,
  scene,
  open,
  onClose,
}: {
  ui: UiCopy
  items: NavItem[]
  scene: SceneContent | null
  open: boolean
  onClose: () => void
}) {
  const closeRef = useRef<HTMLButtonElement>(null)

  useEffect(() => {
    if (!open) return
    const previousOverflow = document.body.style.overflow
    const previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null
    document.body.style.overflow = 'hidden'
    closeRef.current?.focus()

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKeyDown)

    return () => {
      document.body.style.overflow = previousOverflow
      window.removeEventListener('keydown', onKeyDown)
      previousFocus?.focus()
    }
  }, [open, onClose])

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={ui.menu.title}
      className={`fixed inset-0 z-[100] transition-all duration-500 ${open ? 'visible opacity-100' : 'invisible opacity-0'}`}
      style={{ backgroundColor: DARK, transitionTimingFunction: MENU_EASE }}
    >
      <div
        className={`flex h-full flex-col transition-transform duration-500 ${open ? 'translate-y-0' : '-translate-y-8'}`}
        style={{ transitionTimingFunction: MENU_EASE }}
      >
        <div className="flex justify-end px-6 pt-8 sm:px-8 sm:pt-12">
          <button
            ref={closeRef}
            type="button"
            onClick={onClose}
            aria-label={ui.menu.close}
            className="flex h-10 w-10 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:border-white"
          >
            <X size={18} />
          </button>
        </div>

        <ul className="flex flex-1 flex-col justify-center px-8 sm:px-12">
          {items.map((item, i) => (
            <li
              key={item.key}
              style={{
                opacity: open ? 1 : 0,
                transform: open ? 'translateY(0)' : 'translateY(20px)',
                transition: `opacity 500ms ${MENU_EASE} ${i * 60}ms, transform 500ms ${MENU_EASE} ${i * 60}ms`,
              }}
            >
              <a
                href={item.href}
                onClick={onClose}
                aria-current={item.active ? 'page' : undefined}
                className={`block py-3 text-2xl font-light uppercase tracking-wide transition-colors sm:text-3xl ${
                  item.active ? 'text-white' : 'text-white/60 hover:text-white'
                }`}
                {...externalProps(item.external)}
              >
                {item.label}
              </a>
            </li>
          ))}
        </ul>

        <div className="flex items-center gap-8 px-8 pb-10 text-xs uppercase tracking-[0.2em] text-white/60 sm:px-12">
          {scene?.cv && (
            <a href={scene.cv.url} aria-label={scene.cv.label} className="transition-colors hover:text-white">
              CV
            </a>
          )}
          {scene?.email && (
            <a href={scene.email.href} className="transition-colors hover:text-white">
              {ui.contact}
            </a>
          )}
        </div>
      </div>
    </div>
  )
}

export default function App() {
  const locale = useMemo(() => resolveLocale(window.location.pathname), [])
  const ui = uiCopy(locale)
  const { containerRef, videoRef, canvasRef, scrollProgress, canvasLive } = useVideoScrub(VIDEO_SRC)
  const [menuOpen, setMenuOpen] = useState(false)
  const [state, setState] = useState<SceneState>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)

  useEffect(() => {
    document.documentElement.lang = locale
  }, [locale])

  useEffect(() => {
    const controller = new AbortController()
    setState({ status: 'loading' })
    loadPublicContent(locale, controller.signal)
      .then((content) => setState({ status: 'ready', scene: buildScene(content) }))
      .catch(() => {
        if (!controller.signal.aborted) setState({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, attempt])

  const openMenu = useCallback(() => setMenuOpen(true), [])
  const closeMenu = useCallback(() => setMenuOpen(false), [])

  const scene = state.status === 'ready' ? state.scene : null
  const items = navItems(scene)

  const scrollToProgress = (p: number) => {
    const container = containerRef.current
    if (!container) return
    window.scrollTo({ top: p * (container.offsetHeight - window.innerHeight) })
  }

  const s1Opacity = sectionOneOpacity(scrollProgress)
  const s2Opacity = sectionTwoOpacity(scrollProgress)
  const s3Opacity = sectionThreeOpacity(scrollProgress)
  const s1Visible = s1Opacity > STAGGER_THRESHOLD
  const s2Visible = s2Opacity > STAGGER_THRESHOLD && scene !== null
  const s3Visible = s3Opacity > STAGGER_THRESHOLD && scene !== null
  const isLight = scrollProgress > NAV_LIGHT_THRESHOLD

  return (
    <>
      <div ref={containerRef} className="relative h-[500vh]">
        <div className="sticky top-0 h-screen w-full overflow-hidden">
          <video
            ref={videoRef}
            src={VIDEO_SRC}
            muted
            playsInline
            preload="auto"
            aria-hidden="true"
            className="absolute inset-0 h-full w-full object-cover"
          />
          <canvas
            ref={canvasRef}
            width={1920}
            height={1080}
            aria-hidden="true"
            className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-300 ${
              canvasLive ? 'opacity-100' : 'opacity-0'
            }`}
          />

          <div className="pointer-events-none absolute inset-0">
            <Navbar
              ui={ui}
              items={items}
              cv={scene?.cv ?? null}
              isLight={isLight}
              menuOpen={menuOpen}
              onOpenMenu={openMenu}
            />

            <main aria-busy={state.status === 'loading'}>
              <section
                {...inertWhenHidden(s1Opacity)}
                className="absolute inset-0 flex items-center px-6 sm:px-8 md:px-20 lg:px-32"
                style={{ opacity: s1Opacity, transition: 'opacity 0.1s ease-out' }}
              >
                {state.status === 'error' && (
                  <div role="alert" className="flex flex-col items-start gap-6" style={{ color: DARK }}>
                    <p className="text-sm uppercase tracking-[0.3em]">{ui.failure}</p>
                    <button
                      type="button"
                      onClick={() => setAttempt((value) => value + 1)}
                      className={`rounded-full border px-6 py-3 text-xs uppercase tracking-[0.2em] transition-opacity hover:opacity-70 ${
                        s1Visible ? 'pointer-events-auto' : ''
                      }`}
                      style={{ borderColor: '#1D304580' }}
                    >
                      {ui.retry}
                    </button>
                  </div>
                )}

                {scene && (
                  <>
                    <div>
                      <Stagger visible={s1Visible} delay={0}>
                        <h1
                          className="font-light uppercase leading-[1.2]"
                          style={{ fontSize: 'clamp(2rem,5vw,5rem)', color: DARK }}
                        >
                          {scene.headline}
                        </h1>
                      </Stagger>
                      <Stagger visible={s1Visible} delay={150}>
                        <p className="mt-6 text-sm uppercase tracking-[0.3em]" style={{ color: '#1D304590' }}>
                          {scene.name}
                        </p>
                      </Stagger>
                    </div>
                    <Stagger
                      visible={s1Visible}
                      delay={300}
                      className="absolute bottom-12 right-6 sm:right-8 md:right-12"
                    >
                      <button
                        type="button"
                        aria-label={ui.next}
                        onClick={() => scrollToProgress(SECTION_TWO_PROGRESS)}
                        className={`flex h-12 w-12 items-center justify-center rounded-full border transition-opacity hover:opacity-70 ${
                          s1Visible ? 'pointer-events-auto' : ''
                        }`}
                        style={{ borderColor: '#1D304580', color: DARK }}
                      >
                        <ArrowRight size={18} />
                      </button>
                    </Stagger>
                  </>
                )}
              </section>

              <section
                {...inertWhenHidden(s2Opacity)}
                className="absolute inset-0 flex items-center justify-center px-6 sm:px-8"
                style={{ opacity: s2Opacity, transition: 'opacity 0.1s ease-out' }}
              >
                {scene && (
                  <>
                    <Stagger visible={s2Visible} delay={0} className="max-w-[900px]">
                      <h2
                        className="text-center font-extralight uppercase leading-[1.3] tracking-wide"
                        style={{ fontSize: 'clamp(1.5rem,4.5vw,4.5rem)', color: DARK }}
                      >
                        {scene.statement ? (
                          <>
                            {scene.statement.lead}{' '}
                            <span style={{ color: '#1D3045CC' }}>{scene.statement.emphasis}</span>{' '}
                            <span style={{ color: '#1D304580' }}>{scene.statement.tail}</span>
                          </>
                        ) : (
                          scene.summary
                        )}
                      </h2>
                    </Stagger>

                    <div className="absolute bottom-16 right-6 flex flex-col items-center gap-4 sm:right-8 md:right-12">
                      <Stagger visible={s2Visible} delay={200}>
                        <button
                          type="button"
                          aria-label={ui.down}
                          onClick={() => scrollToProgress(SECTION_THREE_PROGRESS)}
                          className={`flex h-12 w-12 items-center justify-center rounded-full border transition-opacity hover:opacity-70 ${
                            s2Visible ? 'pointer-events-auto' : ''
                          }`}
                          style={{ borderColor: '#1D304566', color: DARK }}
                        >
                          <ArrowDown size={18} />
                        </button>
                      </Stagger>
                      <Stagger visible={s2Visible} delay={350} className="mt-4">
                        <div aria-hidden="true" className="flex flex-col items-center gap-2">
                          <span className="block h-2 w-2 rounded-full" style={{ backgroundColor: DARK }} />
                          <span className="block h-1.5 w-1.5 rounded-full" style={{ backgroundColor: '#1D304566' }} />
                          <span className="block h-1.5 w-1.5 rounded-full" style={{ backgroundColor: '#1D304566' }} />
                        </div>
                      </Stagger>
                      <Stagger visible={s2Visible} delay={500} className="mt-2">
                        <button
                          type="button"
                          aria-label={ui.up}
                          onClick={() => scrollToProgress(0)}
                          className={`flex h-10 w-10 items-center justify-center rounded-full border transition-opacity hover:opacity-70 ${
                            s2Visible ? 'pointer-events-auto' : ''
                          }`}
                          style={{ borderColor: '#1D30454D', color: '#1D3045CC' }}
                        >
                          <ChevronUp size={16} />
                        </button>
                      </Stagger>
                    </div>
                  </>
                )}
              </section>

              <section
                {...inertWhenHidden(s3Opacity)}
                className="absolute inset-0 flex items-center justify-end px-6 sm:px-8 md:px-20 lg:px-32"
                style={{ opacity: s3Opacity, transition: 'opacity 0.1s ease-out' }}
              >
                {scene && (
                  <div className="max-w-2xl text-left">
                    {scene.eyebrow && (
                      <Stagger visible={s3Visible} delay={0}>
                        <p className="mb-4 text-lg tracking-wide text-white/60">{scene.eyebrow}</p>
                      </Stagger>
                    )}
                    <Stagger visible={s3Visible} delay={150}>
                      <h2
                        className="mb-8 font-light uppercase leading-[1.2] tracking-wide text-white"
                        style={{ fontSize: 'clamp(2rem,4vw,4rem)' }}
                      >
                        {scene.closing.lineOne}
                        {scene.closing.lineTwo && (
                          <>
                            <br />
                            {scene.closing.lineTwo}
                          </>
                        )}
                      </h2>
                    </Stagger>
                    {scene.email && (
                      <Stagger visible={s3Visible} delay={300}>
                        <a
                          href={scene.email.href}
                          className={`inline-flex items-center gap-4 ${s3Visible ? 'pointer-events-auto' : ''}`}
                        >
                          <span className="text-sm uppercase tracking-[0.3em] text-white/80">{scene.email.label}</span>
                          <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white transition-transform duration-300 hover:scale-110">
                            <ArrowRight size={16} className="text-gray-800" />
                          </span>
                        </a>
                      </Stagger>
                    )}
                  </div>
                )}
              </section>
            </main>
          </div>
        </div>
      </div>

      <MobileMenu ui={ui} items={items} scene={scene} open={menuOpen} onClose={closeMenu} />
    </>
  )
}
