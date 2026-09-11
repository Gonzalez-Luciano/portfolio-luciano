import type {Experience, EndpointResult, WorkCase} from '@/lib/api/types';
import {
  NeutralEmptyState,
  RegionalFailure,
} from '@/components/ui/content-state';
import {WorkCasesSection, type WorkCaseFieldLabels} from './work-cases-section';
import {ExperienceSection} from './experience-section';

/**
 * Work group — `#work` (design spec §16, §20, §22).
 *
 * Server Component. It is only mounted on a structurally valid page (Profile and
 * Site loaded), so `<section id="work">` ALWAYS renders, with a visually hidden
 * group `<h2>` taken from the primary navigation label. Its granular
 * subsections render Work Cases first, then Experience.
 *
 * Per granular collection:
 *   ok + non-empty  -> `<section id>` + `<h3>` + content
 *   ok + []         -> omitted entirely (no section, no heading, no anchor)
 *   failure (any)   -> `<section id>` + `<h3>` + <RegionalFailure> (anchor +
 *                      heading + Retry retained; never neutral copy)
 *
 * When BOTH granular collections are successful-but-empty, `#work` stays and
 * shows the neutral copy, with no granular anchors. A failed sibling means the
 * group is not "both empty".
 */

const WORK_HEADING_ID = 'work-heading';
const WORK_CASES_HEADING_ID = 'work-cases-heading';
const EXPERIENCE_HEADING_ID = 'experience-heading';

export type WorkGroupLabels = {
  /** `Portfolio.nav.work` — the visually hidden group name. */
  group: string;
  /** `Portfolio.sections.workCases`. */
  workCases: string;
  /** `Portfolio.sections.experience`. */
  experience: string;
  /** Work Case field labels (`Portfolio.fields.*`). */
  fields: WorkCaseFieldLabels;
  /** `Portfolio.fields.currentExperienceEnd`. */
  currentExperienceEnd: string;
  /** `Portfolio.state.neutralEmpty`. */
  neutralEmpty: string;
  /** `Portfolio.state.regionalFailure`. */
  regionalFailure: string;
};

type RetryCopy = {
  label: string;
  pendingLabel?: string;
};

type WorkGroupProps = {
  workCases: EndpointResult<WorkCase[]>;
  experiences: EndpointResult<Experience[]>;
  locale: string;
  labels: WorkGroupLabels;
  retry: RetryCopy;
};

export function WorkGroup({
  workCases,
  experiences,
  locale,
  labels,
  retry,
}: WorkGroupProps) {
  const workCasesEmptySuccess = workCases.ok && workCases.data.length === 0;
  const experiencesEmptySuccess =
    experiences.ok && experiences.data.length === 0;
  const bothEmptySuccess = workCasesEmptySuccess && experiencesEmptySuccess;

  const workCasesNode = renderWorkCases({workCases, labels, retry});
  const experienceNode = renderExperience({
    experiences,
    locale,
    labels,
    retry,
  });

  return (
    <section
      id="work"
      tabIndex={-1}
      aria-labelledby={WORK_HEADING_ID}
      className="work-group"
    >
      <h2 id={WORK_HEADING_ID} className="visually-hidden">
        {labels.group}
      </h2>
      {workCasesNode}
      {experienceNode}
      {bothEmptySuccess ? (
        <NeutralEmptyState message={labels.neutralEmpty} />
      ) : null}
    </section>
  );
}

function renderWorkCases({
  workCases,
  labels,
  retry,
}: Pick<WorkGroupProps, 'workCases' | 'labels' | 'retry'>) {
  if (workCases.ok && workCases.data.length === 0) {
    return null;
  }

  return (
    <section
      id="work-cases"
      tabIndex={-1}
      aria-labelledby={WORK_CASES_HEADING_ID}
      className="work-group__region"
    >
      <h3 id={WORK_CASES_HEADING_ID} className="work-group__region-heading">
        {labels.workCases}
      </h3>
      {workCases.ok ? (
        <WorkCasesSection workCases={workCases.data} labels={labels.fields} />
      ) : (
        <RegionalFailure message={labels.regionalFailure} retry={retry} />
      )}
    </section>
  );
}

function renderExperience({
  experiences,
  locale,
  labels,
  retry,
}: Pick<WorkGroupProps, 'experiences' | 'locale' | 'labels' | 'retry'>) {
  if (experiences.ok && experiences.data.length === 0) {
    return null;
  }

  return (
    <section
      id="experience"
      tabIndex={-1}
      aria-labelledby={EXPERIENCE_HEADING_ID}
      className="work-group__region"
    >
      <h3 id={EXPERIENCE_HEADING_ID} className="work-group__region-heading">
        {labels.experience}
      </h3>
      {experiences.ok ? (
        <ExperienceSection
          experiences={experiences.data}
          locale={locale}
          labels={{currentExperienceEnd: labels.currentExperienceEnd}}
        />
      ) : (
        <RegionalFailure message={labels.regionalFailure} retry={retry} />
      )}
    </section>
  );
}
