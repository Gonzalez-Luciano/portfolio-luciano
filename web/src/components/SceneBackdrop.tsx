import { BACKDROP_HEIGHT, BACKDROP_WIDTH, LINKS, STARS } from '@/lib/constellation'
import { landscapeOpacity, linksOpacity, ramp, starsOpacity } from '@/lib/scene-timeline'

/** Sketched landscape by day that gives way to a light constellation by night; same in both themes. */
export function SceneBackdrop({ progress, reducedMotion }: { progress: number; reducedMotion: boolean }) {
  const drift = reducedMotion ? 0 : -progress * 40
  const sunDrop = ramp(progress, 0.5, 0.75) * 140

  return (
    <svg
      aria-hidden="true"
      className="pointer-events-none absolute inset-0 h-full w-full"
      viewBox={`0 0 ${BACKDROP_WIDTH} ${BACKDROP_HEIGHT}`}
      preserveAspectRatio="xMidYMid slice"
    >
      <defs>
        <filter id="scene-star-halo" x="-300%" y="-300%" width="700%" height="700%">
          <feGaussianBlur stdDeviation="4" />
        </filter>
      </defs>

      <g style={{ opacity: landscapeOpacity(progress) }} transform={`translate(0 ${drift})`} fill="none" strokeLinecap="round">
        <g stroke="#665244" strokeOpacity={0.24} strokeWidth={1.5}>
          <path d="M0 700 C 220 640 420 660 620 700 S 1000 760 1200 690 S 1500 640 1600 670" />
          <path d="M0 760 C 260 720 520 740 760 770 S 1180 800 1600 740" />
          <path d="M1080 210 c 30 -24 78 -24 102 0 c 26 -12 62 2 64 28 h -190 c -6 -16 6 -28 24 -28 z" />
          <path d="M380 170 c 24 -18 60 -18 80 0 c 20 -9 48 2 50 22 h -150 c -4 -12 4 -22 20 -22 z" />
        </g>
        <circle cx={1320} cy={260 + sunDrop} r={46} stroke="#9A4E2A" strokeOpacity={0.22} strokeWidth={1.5} />
      </g>

      <g style={{ opacity: linksOpacity(progress) }} stroke="#E38B50" strokeOpacity={0.2} strokeWidth={1} strokeDasharray="2 6">
        {LINKS.map(([from, to]) => (
          <line key={`${from}-${to}`} x1={STARS[from].x} y1={STARS[from].y} x2={STARS[to].x} y2={STARS[to].y} />
        ))}
      </g>

      <g style={{ opacity: starsOpacity(progress) }} fill="#E38B50">
        {STARS.map((star) => (
          <g key={`${star.x}-${star.y}`}>
            <circle cx={star.x} cy={star.y} r={star.r * 3.5} fillOpacity={0.32} filter="url(#scene-star-halo)" />
            <circle cx={star.x} cy={star.y} r={star.r} fillOpacity={0.75} />
          </g>
        ))}
      </g>
    </svg>
  )
}
