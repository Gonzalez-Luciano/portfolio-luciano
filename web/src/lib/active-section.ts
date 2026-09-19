export type ActiveCandidate = { id: string; top: number }

/**
 * Among the sections intersecting the observed band, the one nearest its top wins.
 * IntersectionObserver does not guarantee `entries()` order, and a `scrollIntoView`
 * jump can batch several threshold crossings into one callback, so picking "last in
 * the array" is nondeterministic; picking the smallest `boundingClientRect.top` is not.
 */
export function pickActiveId(candidates: readonly ActiveCandidate[]): string | null {
  if (candidates.length === 0) return null
  return candidates.reduce((topmost, candidate) => (candidate.top < topmost.top ? candidate : topmost)).id
}
