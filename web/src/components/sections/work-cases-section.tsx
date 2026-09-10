import Image from 'next/image';
import type {Technology, WorkCase} from '@/lib/api/types';
import {IndexedWorkCases} from './indexed-work-cases';

/**
 * Work Cases base content (design spec §20, §22).
 *
 * Server Component. Every validated Work Case is rendered as a full dossier, in
 * Laravel/array order (never re-sorted), with all six required narratives
 * present — `Context`, `Problem`, `Contribution`, `Technical approach`,
 * `Outcome`. None is omitted: they are required public content. The field
 * labels are localized UI copy passed in by the caller so this module stays
 * provider-agnostic.
 *
 * The dossiers are handed to {@link IndexedWorkCases} as server-rendered
 * children. That wrapper is progressive enhancement only: the output here is
 * fully readable with zero JavaScript and below `64rem` (sequential dossiers).
 */

export type WorkCaseFieldLabels = {
  context: string;
  problem: string;
  contribution: string;
  technicalApproach: string;
  outcome: string;
};

type WorkCaseDossierProps = {
  workCase: WorkCase;
  labels: WorkCaseFieldLabels;
};

type WorkCasesSectionProps = {
  workCases: WorkCase[];
  labels: WorkCaseFieldLabels;
};

/**
 * A supporting technology taxonomy: names are always shown verbatim; the icon
 * is decorative (`alt=""`, `aria-hidden`) and its absence never removes a name.
 * An empty array renders nothing at all — no empty list.
 */
export function TechnologyList({technologies}: {technologies: Technology[]}) {
  if (technologies.length === 0) {
    return null;
  }

  return (
    <ul className="tech-tags">
      {technologies.map((technology) => (
        <li key={technology.key} className="tech-tags__item">
          {technology.icon ? (
            <Image
              className="tech-tags__icon"
              src={technology.icon.url}
              alt=""
              aria-hidden
              width={18}
              height={18}
            />
          ) : null}
          <span className="tech-tags__name">{technology.name}</span>
        </li>
      ))}
    </ul>
  );
}

const FIELD_ORDER: ReadonlyArray<keyof WorkCaseFieldLabels> = [
  'context',
  'problem',
  'contribution',
  'technicalApproach',
  'outcome',
];

const FIELD_VALUE: Record<keyof WorkCaseFieldLabels, keyof WorkCase> = {
  context: 'context',
  problem: 'problem',
  contribution: 'contribution',
  technicalApproach: 'technical_approach',
  outcome: 'outcome',
};

/**
 * A single Work Case rendered as a self-contained dossier. Comprehensible with
 * no JavaScript; used both sequentially and inside a desktop tab panel.
 */
export function WorkCaseDossier({workCase, labels}: WorkCaseDossierProps) {
  return (
    <article className="work-case">
      <h4 className="work-case__title">{workCase.title}</h4>
      <dl className="work-case__fields">
        {FIELD_ORDER.map((field) => (
          <div key={field} className="work-case__field">
            <dt className="work-case__label">{labels[field]}</dt>
            <dd className="work-case__value">
              {workCase[FIELD_VALUE[field]] as string}
            </dd>
          </div>
        ))}
      </dl>
      <TechnologyList technologies={workCase.technologies} />
    </article>
  );
}

export function WorkCasesSection({workCases, labels}: WorkCasesSectionProps) {
  return (
    <IndexedWorkCases
      titles={workCases.map((workCase) => workCase.title)}
      caseKeys={workCases.map((workCase) => workCase.key)}
    >
      {workCases.map((workCase) => (
        <WorkCaseDossier
          key={workCase.key}
          workCase={workCase}
          labels={labels}
        />
      ))}
    </IndexedWorkCases>
  );
}
