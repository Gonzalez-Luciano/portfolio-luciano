import { memo } from 'react'
import { RegionStatus } from '@/components/RegionStatus'
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { RegionState } from '@/hooks/useRegion'
import type { Technology, TechnologyGroup } from '@/lib/api'
import { groupTechnologies } from '@/lib/grouping'

type Props = { ui: UiCopy; technologies: RegionState<Technology[]>; groups: TechnologyGroup[]; onRetry: () => void }

// The scroll scene above publishes a new progress value up to 60 times a second (see App.tsx);
// memoizing this section stops it from re-rendering on every tick when its own props are unchanged.
export const StackSection = memo(function StackSection({ ui, technologies, groups, onRetry }: Props) {
  return (
    <Section id="stack" label={ui.sections.stack.nav} title={ui.sections.stack.title}>
      {technologies.status !== 'ready' ? (
        <RegionStatus ui={ui} status={technologies.status} onRetry={onRetry} />
      ) : (
        <ul className="grid grid-cols-2 gap-x-8 gap-y-12 lg:grid-cols-4">
          {groupTechnologies(technologies.data, groups).map((column) => (
            <li key={column.key}>
              <h3 className="label text-accent">{column.label}</h3>
              <ul className="mt-4 space-y-2">
                {column.names.map((name) => (
                  <li key={name} className="text-lg">
                    {name}
                  </li>
                ))}
              </ul>
            </li>
          ))}
        </ul>
      )}
    </Section>
  )
})
