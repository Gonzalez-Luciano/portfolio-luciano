import Image from 'next/image';
import type {EndpointResult, Project} from '@/lib/api/types';
import {RegionalFailure} from '@/components/ui/content-state';
import {TechnologyList} from './work-cases-section';

/**
 * Projects — `#projects` (design spec §16 explicit exception, §22, §20, §8).
 *
 * Server Component. It receives an already-validated
 * `EndpointResult<Project[]>` and never fetches. `<section id="projects">`
 * ALWAYS renders on a structurally valid page, with a VISIBLE
 * `<h2 id="projects-heading">`.
 *
 * Body matrix:
 *   ok + non-empty -> one dossier per project, IN ARRAY ORDER (Laravel order;
 *                     never re-sorted, never grouped by `featured`). `featured`
 *                     toggles the `project--featured` modifier class only — it
 *                     changes presentation emphasis, never order, never content.
 *   ok + []        -> ONLY `<p>{emptyMessage}</p>` (the CMS
 *                     `site.projects_empty_message`). This is the §16 explicit
 *                     exception: never NeutralEmptyState, never RegionalFailure,
 *                     never a Retry button.
 *   failure (any,  -> <RegionalFailure> — regional copy + Retry — inside
 *   incl malformed)   `#projects`. The anchor and heading are retained.
 *
 * Per dossier, nullable surfaces are omitted cleanly:
 *   image === null          -> no <figure>, no <img>, no reserved frame
 *   demo_url === null       -> no demo anchor
 *   repository_url === null  -> no repository anchor
 * A present `image` renders through `next/image` with the CMS `url` and `alt`
 * used verbatim inside a stable aspect-ratio container. `demo_url` /
 * `repository_url` are same-context navigation: normal anchors, no forced
 * new tab (they are not the LinkedIn / GitHub profile links of §23).
 */

const PROJECTS_HEADING_ID = 'projects-heading';

export type ProjectsSectionLabels = {
  /** `Portfolio.sections.projects` — the visible section heading. */
  sectionTitle: string;
  /** `Portfolio.fields.problem`. */
  problem: string;
  /** `Portfolio.fields.solution`. */
  solution: string;
  /** `Portfolio.state.regionalFailure`. */
  regionalFailure: string;
};

type RetryCopy = {
  label: string;
  pendingLabel?: string;
};

type ProjectsSectionProps = {
  projects: EndpointResult<Project[]>;
  /** `site.projects_empty_message` — the only copy shown for `ok + []`. */
  emptyMessage: string;
  labels: ProjectsSectionLabels;
  retry: RetryCopy;
};

export function ProjectsSection({
  projects,
  emptyMessage,
  labels,
  retry,
}: ProjectsSectionProps) {
  return (
    <section
      id="projects"
      tabIndex={-1}
      aria-labelledby={PROJECTS_HEADING_ID}
      className="projects"
    >
      <h2 id={PROJECTS_HEADING_ID} className="projects__heading">
        {labels.sectionTitle}
      </h2>
      {renderBody({projects, emptyMessage, labels, retry})}
    </section>
  );
}

function renderBody({
  projects,
  emptyMessage,
  labels,
  retry,
}: ProjectsSectionProps) {
  if (!projects.ok) {
    return <RegionalFailure message={labels.regionalFailure} retry={retry} />;
  }

  if (projects.data.length === 0) {
    return <p className="projects__empty">{emptyMessage}</p>;
  }

  return (
    <div className="projects__list">
      {projects.data.map((project) => (
        <ProjectDossier key={project.key} project={project} labels={labels} />
      ))}
    </div>
  );
}

type ProjectDossierProps = {
  project: Project;
  labels: ProjectsSectionLabels;
};

function ProjectDossier({project, labels}: ProjectDossierProps) {
  const className = [
    'project',
    project.image ? 'project--with-media' : null,
    project.featured ? 'project--featured' : null,
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <article className={className}>
      {project.image ? (
        <figure className="project__media">
          <Image
            className="project__image"
            src={project.image.url}
            alt={project.image.alt}
            fill
            sizes="(min-width: 48rem) 22rem, 100vw"
          />
        </figure>
      ) : null}
      <div className="project__body">
        <h3 className="project__title">{project.title}</h3>
        <p className="project__summary">{project.summary}</p>
        <dl className="project__fields">
          <div className="project__field">
            <dt className="project__label">{labels.problem}</dt>
            <dd className="project__value">{project.problem}</dd>
          </div>
          <div className="project__field">
            <dt className="project__label">{labels.solution}</dt>
            <dd className="project__value">{project.solution}</dd>
          </div>
        </dl>
        <TechnologyList technologies={project.technologies} />
        {project.demo_url !== null || project.repository_url !== null ? (
          <p className="project__actions">
            {project.demo_url !== null ? (
              <a className="project__action" href={project.demo_url}>
                {project.demo_url}
              </a>
            ) : null}
            {project.repository_url !== null ? (
              <a className="project__action" href={project.repository_url}>
                {project.repository_url}
              </a>
            ) : null}
          </p>
        ) : null}
      </div>
    </article>
  );
}
