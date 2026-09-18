/** Backdrop coordinate space; the SVG scales it with `xMidYMid slice`, like the video's `object-fit: cover`. */
export const BACKDROP_WIDTH = 1600
export const BACKDROP_HEIGHT = 900

export type Star = { x: number; y: number; r: number }

/** Night sky over the tree (right two thirds); the left third holds the text column. */
export const STARS: readonly Star[] = [
  { x: 180, y: 140, r: 1.6 },
  { x: 310, y: 90, r: 1.4 },
  { x: 420, y: 260, r: 1.2 },
  { x: 640, y: 120, r: 1.8 },
  { x: 760, y: 230, r: 1.3 },
  { x: 880, y: 80, r: 1.6 },
  { x: 1010, y: 170, r: 2 },
  { x: 1130, y: 95, r: 1.4 },
  { x: 1240, y: 210, r: 1.8 },
  { x: 1350, y: 120, r: 1.3 },
  { x: 1460, y: 250, r: 1.6 },
  { x: 1180, y: 320, r: 1.2 },
  { x: 960, y: 300, r: 1.4 },
  { x: 1400, y: 360, r: 1.2 },
  { x: 700, y: 340, r: 1.1 },
]

export const LINKS: readonly (readonly [number, number])[] = [
  [3, 5],
  [5, 6],
  [6, 7],
  [7, 8],
  [8, 9],
  [9, 10],
  [6, 12],
  [8, 11],
  [10, 13],
  [4, 12],
]
