import { ExternalLink } from '@/components/ExternalLink'
import { ProjectGallery } from '@/components/ProjectGallery'
import { TechnologyChips } from '@/components/TechnologyChips'
import type { UiCopy } from '@/content'
import type { Project } from '@/lib/api'
import { compact } from '@/lib/format'

export function ProjectDossier({ ui, project }: { ui: UiCopy; project: Project }) {
  const labels = ui.projects.labels
  const meta = compact([ui.projects.kind[project.kind], project.client_name, project.role, ui.projects.status[project.status]])
  const blocks = [
    [labels.problem, project.problem],
    [labels.solution, project.solution],
    [labels.result, project.result],
  ] as const

  return (
    <article
      id={`project-${project.key}`}
      tabIndex={-1}
      aria-labelledby={`project-${project.key}-title`}
      className="grid grid-cols-[minmax(0,1fr)] scroll-mt-20 gap-10 outline-none lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-16"
    >
      <div>
        <p className="label text-muted">{meta.join(' · ')}</p>
        <h3 id={`project-${project.key}-title`} className="mt-3 font-display text-4xl font-light leading-tight">
          {project.title}
        </h3>
        <p className="mt-4 text-lg text-muted">{project.summary}</p>

        <dl className="mt-8 space-y-6">
          {blocks.map(([label, text]) => (
            <div key={label}>
              <dt className="label text-accent">{label}</dt>
              <dd className="mt-2">{text}</dd>
            </div>
          ))}
        </dl>

        <TechnologyChips label={labels.stack} technologies={project.technologies} />

        {(project.demo_url || project.repository_url) && (
          <div className="mt-8 flex flex-wrap gap-3">
            {project.demo_url && <ExternalLink href={project.demo_url} label={labels.demo} newTab={ui.contact.newTab} />}
            {project.repository_url && <ExternalLink href={project.repository_url} label={labels.repository} newTab={ui.contact.newTab} />}
          </div>
        )}
      </div>

      {project.images.length > 0 && <ProjectGallery ui={ui} title={project.title} images={project.images} />}
    </article>
  )
}
