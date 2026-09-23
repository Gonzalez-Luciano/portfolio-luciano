import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { AboutSection } from '@/components/AboutSection'
import { ContactSection } from '@/components/ContactSection'
import { DawnTransition } from '@/components/DawnTransition'
import { ExperienceSection } from '@/components/ExperienceSection'
import { ProjectsSection } from '@/components/ProjectsSection'
import { ScrollScene } from '@/components/ScrollScene'
import { SiteNav } from '@/components/SiteNav'
import { StackSection } from '@/components/StackSection'
import { resolveLocale, uiCopy } from '@/content'
import { useActiveSection } from '@/hooks/useActiveSection'
import { useRegion } from '@/hooks/useRegion'
import { useTheme } from '@/hooks/useTheme'
import { loadStructuralContent, type StructuralContent } from '@/lib/api'
import { buildScene } from '@/lib/scene'
import { SCROLL_VIDEO_SRC, navLightText, navVeilOpacity, videoProgress } from '@/lib/scene-timeline'
import { combineStatus, hasAboutContent, hasContactContent, visibleSections, type SectionId } from '@/lib/sections'
import { useVideoScrub } from '@/useVideoScrub'

type StructuralState = { status: 'loading' } | { status: 'ready'; content: StructuralContent } | { status: 'error' }

export default function App() {
  const locale = useMemo(() => resolveLocale(window.location.pathname), [])
  const ui = uiCopy(locale)
  const scrub = useVideoScrub(SCROLL_VIDEO_SRC, videoProgress)
  const { theme, toggle: toggleTheme } = useTheme()
  const [structural, setStructural] = useState<StructuralState>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)

  const technologies = useRegion(locale, 'technologies')
  const experiences = useRegion(locale, 'experiences')
  const workCases = useRegion(locale, 'work-cases')
  const projects = useRegion(locale, 'projects')

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

  const scene = useMemo(
    () => (content ? buildScene({ ...content, technologies: technologies.state.status === 'ready' ? technologies.state.data : [] }) : null),
    [content, technologies.state],
  )

  const experienceStatus = combineStatus(experiences.state.status, workCases.state.status)
  const sections: SectionId[] = content
    ? visibleSections({
        experience: {
          status: experienceStatus,
          hasItems:
            experiences.state.status === 'ready' &&
            workCases.state.status === 'ready' &&
            experiences.state.data.length + workCases.state.data.length > 0,
        },
        stack: {
          status: technologies.state.status,
          hasItems: technologies.state.status === 'ready' && technologies.state.data.length > 0,
        },
        about: hasAboutContent(content.profile, content.site),
        contact: hasContactContent(content.site),
      })
    : []

  const activeSection = useActiveSection(sections)

  // Content renders after the first paint, so a deep link (#projects, or the anchor kept by the
  // language switch) is applied once its target exists.
  const deepLinkHandled = useRef(false)
  useEffect(() => {
    if (deepLinkHandled.current || !content) return
    const id = window.location.hash.slice(1)
    if (id === '') {
      deepLinkHandled.current = true
      return
    }
    const target = document.getElementById(id)
    if (target) {
      // Instant, not the global smooth `scroll-behavior`: an on-load deep link (or the anchor kept
      // by the language switch) should land directly on its section instead of scrubbing the whole
      // 500vh video scene on the way. Nav links and the mobile menu keep the smooth scroll.
      target.scrollIntoView({ behavior: 'instant' })
      deepLinkHandled.current = true
    }
  }, [content, sections, experiences.state, workCases.state, projects.state])

  const retryStructural = useCallback(() => setAttempt((value) => value + 1), [])
  const retryExperience = useCallback(() => {
    experiences.retry()
    workCases.retry()
  }, [experiences.retry, workCases.retry])

  const progress = scrub.scrollProgress

  return (
    <>
      <SiteNav
        ui={ui}
        locale={locale}
        name={content?.profile.name ?? null}
        avatar={content?.profile.photo ?? null}
        cv={content?.site.cv ?? null}
        sections={sections}
        activeSection={activeSection}
        overScene={progress < 1}
        lightText={navLightText(progress)}
        veilOpacity={navVeilOpacity(progress)}
        theme={theme}
        onToggleTheme={toggleTheme}
      />

      <main id="content" tabIndex={-1} className="outline-none" aria-busy={structural.status === 'loading'}>
        <ScrollScene scrub={scrub} scene={scene} status={structural.status} ui={ui} onRetry={retryStructural} />

        {content && (
          <>
            <DawnTransition />
            {sections.includes('experience') && (
              <ExperienceSection ui={ui} locale={locale} experiences={experiences.state} workCases={workCases.state} onRetry={retryExperience} />
            )}
            {sections.includes('projects') && (
              <ProjectsSection ui={ui} projects={projects.state} emptyMessage={content.site.projects_empty_message} onRetry={projects.retry} />
            )}
            {sections.includes('stack') && (
              <StackSection ui={ui} technologies={technologies.state} groups={content.site.technology_groups} onRetry={technologies.retry} />
            )}
            {sections.includes('about') && <AboutSection ui={ui} profile={content.profile} site={content.site} />}
            {sections.includes('contact') && <ContactSection ui={ui} site={content.site} />}
          </>
        )}
      </main>
    </>
  )
}
