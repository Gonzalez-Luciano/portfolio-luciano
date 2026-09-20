import { BACKDROP_HEIGHT, BACKDROP_WIDTH, LINKS, STARS } from '@/lib/constellation'
import {
  cloudOffset,
  landscapeOpacity,
  linksOpacity,
  moonSkyPosition,
  starOpacity,
  sunSkyPosition,
} from '@/lib/scene-timeline'

const CLOUDS = [
  { path: 'M1080 210 c 30 -24 78 -24 102 0 c 26 -12 62 2 64 28 h -190 c -6 -16 6 -28 24 -28 z', rate: 28 },
  { path: 'M380 170 c 24 -18 60 -18 80 0 c 20 -9 48 2 50 22 h -150 c -4 -12 4 -22 20 -22 z', rate: 18 },
  { path: 'M690 300 c 20 -16 48 -18 66 -2 c 16 -10 44 -2 50 17 h -132 c -3 -8 4 -15 16 -15 z', rate: 16 },
  { path: 'M1310 390 c 18 -15 44 -16 60 -1 c 18 -8 40 2 44 18 h -118 c -4 -9 3 -17 14 -17 z', rate: 24 },
  { path: 'M180 430 c 16 -13 40 -14 54 -1 c 15 -7 36 1 40 16 h -108 c -3 -8 3 -15 14 -15 z', rate: 12 },
] as const

/** Sketched landscape by day that gives way to a light constellation by night; same in both themes. */
export function SceneBackdrop({ progress, reducedMotion }: { progress: number; reducedMotion: boolean }) {
  const landscapeDrift = reducedMotion ? 0 : -progress * 40
  const sun = sunSkyPosition(progress, reducedMotion)
  const moon = moonSkyPosition(progress, reducedMotion)

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

      <g style={{ opacity: landscapeOpacity(progress) }} transform={`translate(0 ${landscapeDrift})`} fill="none" strokeLinecap="round">
        <g stroke="#665244" strokeOpacity={0.24} strokeWidth={1.5}>
          <path d="M0 700 C 220 640 420 660 620 700 S 1000 760 1200 690 S 1500 640 1600 670" />
          <path d="M0 760 C 260 720 520 740 760 770 S 1180 800 1600 740" />
        </g>
        <circle cx={sun.x} cy={sun.y} r={46} stroke="#9A4E2A" strokeOpacity={0.22} strokeWidth={1.5} />
        {CLOUDS.map((cloud) => (
          <path
            key={cloud.path}
            data-scene-cloud
            d={cloud.path}
            stroke="#665244"
            strokeOpacity={0.24}
            strokeWidth={1.5}
            transform={`translate(${cloudOffset(progress, cloud.rate, reducedMotion)} 0)`}
          />
        ))}
      </g>

      <g style={{ opacity: moon.opacity }} fill="none" stroke="#E38B50" strokeOpacity={0.42} strokeWidth={1.5}>
        <path d={`M ${moon.x} ${moon.y - 34} a 34 34 0 1 0 0 68 a 25 25 0 1 1 0 -68`} />
      </g>

      <g style={{ opacity: linksOpacity(progress) }} stroke="#E38B50" strokeOpacity={0.2} strokeWidth={1} strokeDasharray="2 6">
        {LINKS.map(([from, to]) => (
          <line key={`${from}-${to}`} x1={STARS[from].x} y1={STARS[from].y} x2={STARS[to].x} y2={STARS[to].y} />
        ))}
      </g>

      <g fill="#E38B50">
        {STARS.map((star, index) => (
          <g key={`${star.x}-${star.y}`} style={{ opacity: starOpacity(progress, index) }}>
            <circle cx={star.x} cy={star.y} r={star.r * 3.5} fillOpacity={0.32} filter="url(#scene-star-halo)" />
            <circle cx={star.x} cy={star.y} r={star.r} fillOpacity={0.75} />
          </g>
        ))}
      </g>
    </svg>
  )
}
