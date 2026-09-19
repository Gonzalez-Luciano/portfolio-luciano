import { Accordion } from '@/components/Accordion'
import { RegionStatus } from '@/components/RegionStatus'
import { Section } from '@/components/Section'
import { TechnologyChips } from '@/components/TechnologyChips'
import type { Locale, UiCopy } from '@/content'
import type { RegionState } from '@/hooks/useRegion'
import type { Experience, WorkCase } from '@/lib/api'
import { formatPeriod } from '@/lib/format'
import { groupExperience } from '@/lib/grouping'

type Props = {
  ui: UiCopy
  locale: Locale
  experiences: RegionState<Experience[]>
  workCases: RegionState<WorkCase[]>
  onRetry: () => void
}

function CaseDetails({ ui, workCase }: { ui: UiCopy; workCase: WorkCase }) {
  const labels = ui.experience.caseLabels
  const blocks = [
    [labels.problem, workCase.problem],
    [labels.contribution, workCase.contribution],
    [labels.approach, workCase.technical_approach],
    [labels.outcome, workCase.outcome],
  ] as const

  return (
    <>
      <p className="label text-accent">{labels.context}</p>
      <p className="mt-2 max-w-3xl text-muted">{workCase.context}</p>
      <dl className="mt-6 grid gap-6 md:grid-cols-2">
        {blocks.map(([label, text]) => (
          <div key={label}>
            <dt className="label text-accent">{label}</dt>
            <dd className="mt-2">{text}</dd>
          </div>
        ))}
      </dl>
      <TechnologyChips label={labels.technologies} technologies={workCase.technologies} />
    </>
  )
}

export function ExperienceSection({ ui, locale, experiences, workCases, onRetry }: Props) {
  return (
    <Section id="experience" label={ui.sections.experience.nav} title={ui.sections.experience.title}>
      {experiences.status === 'ready' && workCases.status === 'ready' ? (
        <Timeline ui={ui} locale={locale} experiences={experiences.data} workCases={workCases.data} />
      ) : (
        <RegionStatus ui={ui} status={experiences.status === 'error' || workCases.status === 'error' ? 'error' : 'loading'} onRetry={onRetry} />
      )}
    </Section>
  )
}

function Timeline({ ui, locale, experiences, workCases }: { ui: UiCopy; locale: Locale; experiences: Experience[]; workCases: WorkCase[] }) {
  const { organizations, unlinkedCases } = groupExperience(experiences, workCases)

  return (
    <>
      <ol className="relative space-y-16 border-l border-node pl-8 lg:pl-12">
        {organizations.map((group) => (
          <li key={group.key}>
            {group.organization && <h3 className="font-display text-3xl font-light">{group.organization}</h3>}
            <ol className="mt-8 space-y-14">
              {group.roles.map(({ experience, cases }) => (
                <li key={experience.key} className="relative">
                  <span aria-hidden="true" className="timeline-node absolute -left-[calc(2rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full bg-node lg:-left-[calc(3rem+5px)]" />
                  <p className="label text-muted">{formatPeriod(experience.start, experience.end, locale)}</p>
                  <h4 className="mt-2 font-display text-2xl font-light">{experience.role}</h4>
                  <p className="mt-3 max-w-3xl text-muted">{experience.summary}</p>
                  {experience.highlights.length > 0 && (
                    <ul className="mt-4 max-w-3xl list-disc space-y-2 pl-5 marker:text-accent">
                      {experience.highlights.map((highlight) => (
                        <li key={highlight}>{highlight}</li>
                      ))}
                    </ul>
                  )}
                  {cases.length > 0 && (
                    <div className="mt-8 max-w-4xl">
                      {cases.map((workCase) => (
                        <Accordion key={workCase.key} title={workCase.title}>
                          <CaseDetails ui={ui} workCase={workCase} />
                        </Accordion>
                      ))}
                    </div>
                  )}
                </li>
              ))}
            </ol>
          </li>
        ))}
      </ol>

      {unlinkedCases.length > 0 && (
        <div className="mt-20 max-w-4xl">
          <h3 className="font-display text-2xl font-light">{ui.experience.otherCases}</h3>
          <div className="mt-6">
            {unlinkedCases.map((workCase) => (
              <Accordion key={workCase.key} title={workCase.title}>
                <CaseDetails ui={ui} workCase={workCase} />
              </Accordion>
            ))}
          </div>
        </div>
      )}
    </>
  )
}
