import type { Technology } from '@/lib/api'

export function TechnologyChips({ label, technologies }: { label: string; technologies: Technology[] }) {
  if (technologies.length === 0) return null

  return (
    <ul aria-label={label} className="mt-6 flex flex-wrap gap-2">
      {technologies.map((technology) => (
        <li key={technology.key} className="rounded-full border border-line px-3 py-1 font-mono text-xs">
          {technology.name}
        </li>
      ))}
    </ul>
  )
}
