import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { Profile, Site } from '@/lib/api'
import { compact, formatYears } from '@/lib/format'
import { aboutStatement } from '@/lib/sections'

export function AboutSection({ ui, profile, site }: { ui: UiCopy; profile: Profile; site: Site }) {
  const statement = aboutStatement(site)
  const place = compact([profile.location, ...profile.work_modes.map((mode) => ui.about.modes[mode])])

  return (
    <Section id="about" label={ui.sections.about.nav} title={ui.sections.about.title}>
      <div className="grid gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-20">
        {statement ? (
          <blockquote className="font-display text-[clamp(1.75rem,3vw,2.75rem)] font-light leading-snug">“{statement}”</blockquote>
        ) : (
          <div aria-hidden="true" />
        )}

        <dl className="space-y-8">
          {site.education.length > 0 && (
            <div>
              <dt className="label text-accent">{ui.about.education}</dt>
              <dd className="mt-3">
                <ul className="space-y-4">
                  {site.education.map((entry) => (
                    <li key={entry.key}>
                      <p className="font-semibold">{entry.program}</p>
                      <p className="text-muted">
                        {compact([entry.institution, formatYears(entry.start_year, entry.end_year), entry.detail]).join(' · ')}
                      </p>
                    </li>
                  ))}
                </ul>
              </dd>
            </div>
          )}

          {site.languages.length > 0 && (
            <div>
              <dt className="label text-accent">{ui.about.languages}</dt>
              <dd className="mt-3 text-muted">
                {site.languages.map((language) => `${language.name} ${ui.about.levels[language.level]}`).join(' · ')}
              </dd>
            </div>
          )}

          {place.length > 0 && (
            <div>
              <dt className="label text-accent">{ui.about.location}</dt>
              <dd className="mt-3 text-muted">{place.join(' · ')}</dd>
            </div>
          )}
        </dl>
      </div>
    </Section>
  )
}
