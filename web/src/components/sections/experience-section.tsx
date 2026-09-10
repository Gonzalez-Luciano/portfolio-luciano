import type {Experience} from '@/lib/api/types';
import {TechnologyList} from './work-cases-section';

/**
 * Experience — the second half of the Work evidence sequence (design spec §20,
 * §22). Server Component.
 *
 * Entries render in Laravel/array order. Per entry:
 *   - `organization` is shown ONLY when non-null; a null organization is
 *     omitted cleanly — never inferred, never a placeholder;
 *   - `role` is always shown;
 *   - `start` / `end` (`YYYY-MM`) are formatted with `Intl.DateTimeFormat` for
 *     the route `locale` as `{ year: 'numeric', month: 'long' }`;
 *   - `end: null` renders the exact localized "Present" / "Actualidad" copy
 *     passed in as `labels.currentExperienceEnd`;
 *   - `summary` is always shown;
 *   - `highlights` render as a list only when non-empty;
 *   - technology names render only when non-empty.
 *
 * No date, organization, or other field is ever inferred.
 */

export type ExperienceLabels = {
  /** `Portfolio.fields.currentExperienceEnd` — "Present" / "Actualidad". */
  currentExperienceEnd: string;
};

type ExperienceSectionProps = {
  experiences: Experience[];
  locale: string;
  labels: ExperienceLabels;
};

/** Format a validated `YYYY-MM` month for the route locale. */
function formatMonth(value: string, locale: string): string {
  const [year, month] = value.split('-').map((part) => Number.parseInt(part, 10));
  const date = new Date(Date.UTC(year, month - 1, 1));

  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'long',
    timeZone: 'UTC',
  }).format(date);
}

export function ExperienceSection({
  experiences,
  locale,
  labels,
}: ExperienceSectionProps) {
  return (
    <ol className="experience">
      {experiences.map((experience) => {
        const startLabel = formatMonth(experience.start, locale);
        const endLabel =
          experience.end === null
            ? labels.currentExperienceEnd
            : formatMonth(experience.end, locale);

        return (
          <li key={experience.key} className="experience__entry">
            <h4 className="experience__role">{experience.role}</h4>
            <p className="experience__meta">
              {experience.organization !== null ? (
                <span className="experience__org">
                  {experience.organization}
                </span>
              ) : null}
              <span className="experience__dates">
                {startLabel} – {endLabel}
              </span>
            </p>
            <p className="experience__summary">{experience.summary}</p>
            {experience.highlights.length > 0 ? (
              <ul className="experience__highlights">
                {experience.highlights.map((highlight, index) => (
                  <li
                    key={`${experience.key}-highlight-${index}`}
                    className="experience__highlight"
                  >
                    {highlight}
                  </li>
                ))}
              </ul>
            ) : null}
            <TechnologyList technologies={experience.technologies} />
          </li>
        );
      })}
    </ol>
  );
}
