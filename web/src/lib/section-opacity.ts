/** Children of a section stagger in once its opacity passes this value. */
export const STAGGER_THRESHOLD = 0.3

/** The navbar switches from navy to white once the scene reaches the dark frames. */
export const NAV_LIGHT_THRESHOLD = 0.55

export function sectionOneOpacity(p: number): number {
  if (p < 0.2) return 1
  return Math.max(0, 1 - (p - 0.2) / 0.08)
}

export function sectionTwoOpacity(p: number): number {
  if (p < 0.32) return 0
  if (p < 0.4) return (p - 0.32) / 0.08
  if (p < 0.55) return 1
  return Math.max(0, 1 - (p - 0.55) / 0.08)
}

export function sectionThreeOpacity(p: number): number {
  if (p < 0.67) return 0
  if (p < 0.75) return (p - 0.67) / 0.08
  return 1
}
