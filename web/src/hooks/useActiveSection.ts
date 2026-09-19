import { useEffect, useState } from 'react'
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
        for (const entry of entries) {
          if (entry.isIntersecting) setActive(entry.target.id as SectionId)
        }
      },
      { rootMargin: '-40% 0px -55% 0px' },
    )
    elements.forEach((element) => observer.observe(element))

    return () => observer.disconnect()
  }, [key])

  return active
}
