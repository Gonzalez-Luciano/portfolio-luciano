import { ArrowUpRight } from 'lucide-react'
import type { AnalyticsEventName } from '@/lib/analytics-events'

const DEFAULT_CLASS =
  'inline-flex min-h-11 items-center gap-2 rounded-full border border-line px-5 text-sm font-semibold transition-colors hover:border-accent'

/** A link that leaves the site: an arrow glyph plus an sr-only "opens in a new tab" hint. */
export function ExternalLink({
  href,
  label,
  newTab,
  className = DEFAULT_CLASS,
  analyticsEvent,
}: {
  href: string
  label: string
  newTab: string
  className?: string
  analyticsEvent?: AnalyticsEventName
}) {
  return (
    <a href={href} target="_blank" rel="noopener noreferrer" className={className} data-umami-event={analyticsEvent}>
      {label}
      <ArrowUpRight size={16} aria-hidden="true" />
      <span className="sr-only"> {newTab}</span>
    </a>
  )
}
