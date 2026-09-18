/** Scroll progress through the track: clamp(0, 1, scrollY / (trackHeight - viewportHeight)). */
export function clampProgress(scrollY: number, span: number): number {
  if (!(span > 0)) return 0
  return Math.min(1, Math.max(0, scrollY / span))
}

/** Frame-rate independent exponential approach of `current` toward `target`, snapping when close. */
export function stepTowards(
  current: number,
  target: number,
  dt: number,
  tau: number,
  snap: number,
): number {
  const next = current + (target - current) * (1 - Math.exp(-dt * tau))
  return Math.abs(target - next) < snap ? target : next
}

/** Binary search over frames sorted by `ts` (microseconds) for the frame nearest to `seconds`. */
export function nearestIndex(frames: ReadonlyArray<{ ts: number }>, seconds: number): number {
  const count = frames.length
  if (count === 0) return -1

  const t = seconds * 1e6
  let lo = 0
  let hi = count - 1

  while (lo < hi) {
    const mid = (lo + hi) >> 1
    if (frames[mid].ts < t) lo = mid + 1
    else hi = mid
  }

  if (lo > 0 && t - frames[lo - 1].ts <= frames[lo].ts - t) return lo - 1
  return lo
}

/**
 * The two frames around `seconds` and how far between them it sits, so an
 * 81-frame bank can be drawn as a continuous cross-fade.
 */
export function blendFrames(
  frames: ReadonlyArray<{ ts: number }>,
  seconds: number,
): { index: number; next: number; weight: number } | null {
  const count = frames.length
  if (count === 0) return null

  const t = seconds * 1e6
  if (t <= frames[0].ts) return { index: 0, next: 0, weight: 0 }
  if (t >= frames[count - 1].ts) return { index: count - 1, next: count - 1, weight: 0 }

  let lo = 0
  let hi = count - 1
  while (hi - lo > 1) {
    const mid = (lo + hi) >> 1
    if (frames[mid].ts <= t) lo = mid
    else hi = mid
  }

  const span = frames[hi].ts - frames[lo].ts
  return { index: lo, next: hi, weight: span > 0 ? (t - frames[lo].ts) / span : 0 }
}
