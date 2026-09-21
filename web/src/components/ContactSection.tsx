import { ArrowRight, Download } from 'lucide-react'
import { memo } from 'react'
import { ExternalLink } from '@/components/ExternalLink'
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { ProfessionalLink, ProfessionalLinkKey, Site } from '@/lib/api'
import { emailAddress } from '@/lib/format'

const ORDER: readonly ProfessionalLinkKey[] = ['email', 'linkedin', 'github']
const LINK_CLASS = 'flex min-h-14 items-center justify-between gap-6 py-3 transition-colors hover:text-accent'

// See StackSection for why this is memoized: the scroll scene publishes progress up to 60 times a
// second and this section's own props do not change on those ticks.
export const ContactSection = memo(function ContactSection({ ui, site }: { ui: UiCopy; site: Site }) {
  const links = ORDER.map((key) => site.professional_links.find((link) => link.key === key)).filter(
    (link): link is ProfessionalLink => link !== undefined,
  )

  return (
    <Section id="contact" label={ui.sections.contact.nav} title={ui.sections.contact.title}>
      <div className="grid gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-20">
        <p className="max-w-2xl font-display text-[clamp(1.5rem,2.6vw,2.25rem)] font-light leading-snug">{site.contact_intro}</p>

        <div>
          <ul>
            {links.map((link) => (
              <li key={link.key} className="border-b border-line first:border-t">
                {link.key === 'email' ? (
                  <a href={link.href} className={LINK_CLASS}>
                    <span>
                      <span className="block text-lg">{link.label}</span>
                      <span className="mt-1 block font-mono text-sm text-muted">{emailAddress(link.href)}</span>
                    </span>
                    <ArrowRight size={18} aria-hidden="true" />
                  </a>
                ) : (
                  <ExternalLink href={link.href} label={link.label} newTab={ui.contact.newTab} className={LINK_CLASS} />
                )}
              </li>
            ))}
          </ul>

          {site.cv && (
            <a
              href={site.cv.url}
              className="mt-8 inline-flex min-h-11 items-center gap-2 rounded-sm border border-accent px-4 font-mono text-xs font-medium uppercase tracking-[0.18em] text-accent transition-colors hover:bg-accent hover:text-canvas"
            >
              <Download size={14} aria-hidden="true" />
              {site.cv.label}
            </a>
          )}
        </div>
      </div>
    </Section>
  )
})
