import type {
  EndpointResult,
  SiteConfiguration,
  Technology,
} from '@/lib/api/types';
import {
  NeutralEmptyState,
  RegionalFailure,
} from '@/components/ui/content-state';
import {TechnologyList} from './work-cases-section';

/**
 * Expertise group — `#expertise` (design spec §22, §20, §16, §11).
 *
 * Server Component. It is only mounted on a structurally valid page (Profile and
 * Site loaded), so `<section id="expertise">` ALWAYS renders, with a visually
 * hidden group `<h2>` taken from the primary navigation label. Its granular
 * subsections render Specialties first, then Technologies.
 *
 * Specialties (`#specialties`):
 *   areas non-empty -> `<section id>` + `<h3>` + one entry per area (title always,
 *                      description element omitted entirely when `null`)
 *   areas []        -> omitted entirely (no section, no heading, no anchor)
 *
 * Technologies (`#technologies`):
 *   ok + non-empty  -> `<section id>` + `<h3>`; then, FOR EACH validated
 *                      `site.technology_groups` entry IN ORDER, the technologies
 *                      whose `category` equals that group key (endpoint order
 *                      preserved). A group with zero members is skipped. The
 *                      `<h4>` text is ALWAYS `group.label` from Site — never a
 *                      derived or translated category word.
 *   ok + []         -> omitted entirely (no section, no heading, no anchor)
 *   failure (any,   -> `<section id>` + `<h3>` + <RegionalFailure> (anchor +
 *   incl malformed)     heading + Retry retained; never neutral copy)
 *
 * When BOTH granular subsections are successful-but-empty (`areas` is `[]` AND
 * technologies is `{ok:true, data:[]}`), `#expertise` stays and shows the
 * neutral copy, with no granular anchors. A failed Technologies endpoint means
 * the group is NOT "both empty" and never yields neutral copy.
 */

const EXPERTISE_HEADING_ID = 'expertise-heading';
const SPECIALTIES_HEADING_ID = 'specialties-heading';
const TECHNOLOGIES_HEADING_ID = 'technologies-heading';

export type ExpertiseGroupLabels = {
  /** `Portfolio.nav.expertise` — the visually hidden group name. */
  group: string;
  /** `Portfolio.sections.specialties`. */
  specialties: string;
  /** `Portfolio.sections.technologies`. */
  technologies: string;
  /** `Portfolio.state.neutralEmpty`. */
  neutralEmpty: string;
  /** `Portfolio.state.regionalFailure`. */
  regionalFailure: string;
};

type RetryCopy = {
  label: string;
  pendingLabel?: string;
};

type ExpertiseGroupProps = {
  areas: SiteConfiguration['expertise_areas'];
  groups: SiteConfiguration['technology_groups'];
  technologies: EndpointResult<Technology[]>;
  labels: ExpertiseGroupLabels;
  retry: RetryCopy;
};

export function ExpertiseGroup({
  areas,
  groups,
  technologies,
  labels,
  retry,
}: ExpertiseGroupProps) {
  const areasEmptySuccess = areas.length === 0;
  const technologiesEmptySuccess =
    technologies.ok && technologies.data.length === 0;
  const bothEmptySuccess = areasEmptySuccess && technologiesEmptySuccess;

  return (
    <section
      id="expertise"
      tabIndex={-1}
      aria-labelledby={EXPERTISE_HEADING_ID}
      className="expertise-group"
    >
      <h2 id={EXPERTISE_HEADING_ID} className="visually-hidden">
        {labels.group}
      </h2>
      {renderSpecialties({areas, labels})}
      {renderTechnologies({groups, technologies, labels, retry})}
      {bothEmptySuccess ? (
        <NeutralEmptyState message={labels.neutralEmpty} />
      ) : null}
    </section>
  );
}

function renderSpecialties({
  areas,
  labels,
}: Pick<ExpertiseGroupProps, 'areas' | 'labels'>) {
  if (areas.length === 0) {
    return null;
  }

  return (
    <section
      id="specialties"
      tabIndex={-1}
      aria-labelledby={SPECIALTIES_HEADING_ID}
      className="expertise-group__region"
    >
      <h3
        id={SPECIALTIES_HEADING_ID}
        className="expertise-group__region-heading"
      >
        {labels.specialties}
      </h3>
      <div className="specialties">
        {areas.map((area) => (
          <article key={area.key} className="specialties__item">
            <h4 className="specialties__title">{area.title}</h4>
            {area.description !== null ? (
              <p className="specialties__description">{area.description}</p>
            ) : null}
          </article>
        ))}
      </div>
    </section>
  );
}

function renderTechnologies({
  groups,
  technologies,
  labels,
  retry,
}: Pick<ExpertiseGroupProps, 'groups' | 'technologies' | 'labels' | 'retry'>) {
  if (technologies.ok && technologies.data.length === 0) {
    return null;
  }

  return (
    <section
      id="technologies"
      tabIndex={-1}
      aria-labelledby={TECHNOLOGIES_HEADING_ID}
      className="expertise-group__region"
    >
      <h3
        id={TECHNOLOGIES_HEADING_ID}
        className="expertise-group__region-heading"
      >
        {labels.technologies}
      </h3>
      {technologies.ok ? (
        <div className="tech-groups">
          {groups.map((group) => {
            const members = technologies.data.filter(
              (technology) => technology.category === group.key,
            );

            if (members.length === 0) {
              return null;
            }

            return (
              <div key={group.key} className="tech-groups__group">
                <h4 className="tech-groups__label">{group.label}</h4>
                <TechnologyList technologies={members} />
              </div>
            );
          })}
        </div>
      ) : (
        <RegionalFailure message={labels.regionalFailure} retry={retry} />
      )}
    </section>
  );
}
