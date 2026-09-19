import type { ReactNode } from 'react'
import { sectionNumber, type SectionId } from '@/lib/sections'

export function Section({ id, label, title, children }: { id: SectionId; label: string; title: string; children: ReactNode }) {
  return (
    <section id={id} aria-labelledby={`${id}-title`} className="scroll-mt-16 px-6 py-24 sm:px-10 lg:px-16 lg:py-32">
      <div className="mx-auto max-w-content">
        <p className="label text-accent">
          {sectionNumber(id)} · {label}
        </p>
        <h2 id={`${id}-title`} className="mt-4 font-display text-[clamp(2.25rem,4.5vw,4rem)] font-light leading-[1.05]">
          {title}
        </h2>
        <div className="mt-12 lg:mt-16">{children}</div>
      </div>
    </section>
  )
}
