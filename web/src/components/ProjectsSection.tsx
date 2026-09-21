import { memo, useMemo, useState } from 'react'
import { flushSync } from 'react-dom'
import { ProjectDossier } from '@/components/ProjectDossier'
import { RegionStatus } from '@/components/RegionStatus'
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { RegionState } from '@/hooks/useRegion'
import type { Project, ProjectKind } from '@/lib/api'
import { PROJECT_PREVIEW_LIMIT, groupProjects, previewItems } from '@/lib/grouping'

type Props = { ui: UiCopy; projects: RegionState<Project[]>; emptyMessage: string; onRetry: () => void }

const KINDS: readonly ProjectKind[] = ['client', 'personal']

function ProjectGroup({ ui, kind, projects }: { ui: UiCopy; kind: ProjectKind; projects: Project[] }) {
  const [expanded, setExpanded] = useState(false)
  const { visible, hiddenCount } = previewItems(projects, expanded)

  // Expand synchronously, then move focus once to the first revealed project so keyboard
  // users continue from there. Firing this from the click (not an effect keyed on `expanded`)
  // means it runs exactly once per expansion instead of on every later re-render.
  const showMore = () => {
    flushSync(() => setExpanded(true))
    document.getElementById(`project-${projects[PROJECT_PREVIEW_LIMIT]?.key}`)?.focus()
  }

  return (
    <section id={`${kind}-projects`} aria-label={ui.projects.groups[kind]} className="scroll-mt-20">
      <p className="label text-muted">{ui.projects.groups[kind]}</p>
      <div className="mt-10 space-y-24">
        {visible.map((project) => (
          <ProjectDossier key={project.key} ui={ui} project={project} />
        ))}
      </div>
      {hiddenCount > 0 && (
        <button
          type="button"
          onClick={showMore}
          className="mt-14 min-h-11 rounded-full border border-line px-6 text-sm font-semibold transition-colors hover:border-accent"
        >
          {ui.projects.showMore}
        </button>
      )}
    </section>
  )
}

function ProjectGroups({ ui, projects, emptyMessage }: { ui: UiCopy; projects: Project[]; emptyMessage: string }) {
  const groups = useMemo(() => groupProjects(projects), [projects])
  const kinds = KINDS.filter((kind) => groups[kind].length > 0)

  if (kinds.length === 0) return <p className="max-w-2xl text-lg text-muted">{emptyMessage}</p>

  return (
    <div className="space-y-28">
      {kinds.map((kind) => (
        <ProjectGroup key={kind} ui={ui} kind={kind} projects={groups[kind]} />
      ))}
    </div>
  )
}

// See StackSection for why this is memoized: the scroll scene publishes progress up to 60 times a
// second and this section's own props do not change on those ticks.
export const ProjectsSection = memo(function ProjectsSection({ ui, projects, emptyMessage, onRetry }: Props) {
  return (
    <Section id="projects" label={ui.sections.projects.nav} title={ui.sections.projects.title}>
      {projects.status !== 'ready' ? (
        <RegionStatus ui={ui} status={projects.status} onRetry={onRetry} />
      ) : (
        <ProjectGroups ui={ui} projects={projects.data} emptyMessage={emptyMessage} />
      )}
    </Section>
  )
})
