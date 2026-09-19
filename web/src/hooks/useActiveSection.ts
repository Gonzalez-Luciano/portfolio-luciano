import { useEffect, useState } from 'react'
import { pickActiveId } from '@/lib/active-section'
import type { SectionId } from '@/lib/sections'

/** The section crossing a band just above the middle of the viewport. */
export function useActiveSection(ids: readonly SectionId[]): SectionId | null {
  const [active, setActive] = useState<SectionId | null>(null)
  const key = ids.join('|')

  useEffect(() => {
    const elements = key
      .split('|')
      .map((id) => document.getElementById(id))
      .filter((element): element is HTMLElement => element !== null)

    if (elements.length === 0) {
      setActive(null)
      return
    }

    const observer = new IntersectionObserver(
      (entries) => {
        // A scrollIntoView jump (e.g. from the mobile menu) can batch several threshold
        // crossings into one callback; entries() order is not guaranteed, so the
        // topmost intersecting section wins rather than whichever is last in the array.
        const candidates = entries
          .filter((entry) => entry.isIntersecting)
          .map((entry) => ({ id: entry.target.id, top: entry.boundingClientRect.top }))
        const next = pickActiveId(candidates)
        if (next !== null) setActive(next as SectionId)
      },
      { rootMargin: '-40% 0px -55% 0px' },
    )
    elements.forEach((element) => observer.observe(element))

    return () => observer.disconnect()
  }, [key])

  return active
}
