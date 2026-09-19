import { NAV_LIGHT_THRESHOLD } from '@/lib/section-opacity'

export const SCROLL_VIDEO_SRC = '/media/scroll/ink-tree-network-v3.mp4'
export const SCROLL_POSTER_SRC = '/media/scroll/ink-tree-network-v3-poster.webp'

/** Darkest opacity of both veils (#1A1411), measured to keep beat 3 and the nav above AA. */
export const VEIL_OPACITY = 0.45

const clamp01 = (value: number) => Math.min(1, Math.max(0, value))

export function ramp(p: number, from: number, to: number): number {
  return clamp01((p - from) / (to - from))
}

/** Low-contrast blend of the video (frames ≈44–60): triangle 0.55 → 0.65 → 0.75. */
export function correctionEnvelope(p: number): number {
  if (p <= 0.55 || p >= 0.75) return 0
  return p <= 0.65 ? (p - 0.55) / 0.1 : (0.75 - p) / 0.1
}

export function correctionFilter(p: number): string {
  const envelope = correctionEnvelope(p)
  if (envelope === 0) return 'none'
  const value = (amount: number) => Number(amount.toFixed(3))
  return `contrast(${value(1 + 0.35 * envelope)}) saturate(${value(1 + 0.25 * envelope)}) brightness(${value(1 - 0.06 * envelope)})`
}

const veilFadeOut = (p: number) => VEIL_OPACITY * (1 - ramp(p, 0.78, 0.84))

export function columnVeilOpacity(p: number): number {
  if (p < 0.66) return 0
  if (p < 0.7) return VEIL_OPACITY * ramp(p, 0.66, 0.7)
  return veilFadeOut(p)
}

/** True once the nav switches to light text (spec §7.3): drives both the nav text color and its veil. */
export function navLightText(p: number): boolean {
  return p >= NAV_LIGHT_THRESHOLD
}

/** Appears together with the light nav text (a 150 ms CSS fade smooths the step). */
export function navVeilOpacity(p: number): number {
  return navLightText(p) ? veilFadeOut(p) : 0
}

export function landscapeOpacity(p: number): number {
  return 1 - ramp(p, 0.5, 0.75)
}

export function starsOpacity(p: number): number {
  return ramp(p, 0.5, 0.75)
}

export function linksOpacity(p: number): number {
  return ramp(p, 0.75, 0.9)
}
