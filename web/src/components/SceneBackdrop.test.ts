import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { SceneBackdrop } from '@/components/SceneBackdrop'
import { moonSkyPosition } from '@/lib/scene-timeline'

describe('SceneBackdrop', () => {
  it('renders every cloud with an explicit visible paint source', () => {
    const markup = renderToStaticMarkup(createElement(SceneBackdrop, { progress: 0.25, reducedMotion: false }))
    const clouds = markup.match(/<path(?=[^>]*data-scene-cloud)[^>]*>/g) ?? []

    expect(clouds).toHaveLength(5)
    expect(clouds.every((cloud) => /(?:stroke|fill)="[^"]+"/.test(cloud))).toBe(true)
  })

  it('wires the moon path to moonSkyPosition at a night progress', () => {
    const progress = 0.85
    const expected = moonSkyPosition(progress, false)
    const markup = renderToStaticMarkup(createElement(SceneBackdrop, { progress, reducedMotion: false }))

    // The moon's wrapping <g> carries the opacity; its only child is the moon path itself,
    // so a swapped sun/moon position or a dropped opacity binding would fail this match.
    const match = markup.match(/<g style="opacity:([^"]+)"[^>]*><path[^>]*data-scene-moon[^>]*d="M ([-\d.]+) ([-\d.]+) a/)
    expect(match).not.toBeNull()

    const [, opacity, x, y] = match as RegExpMatchArray
    expect(Number(opacity)).toBeCloseTo(expected.opacity, 5)
    expect(Number(x)).toBeCloseTo(expected.x, 5)
    expect(Number(y) + 34).toBeCloseTo(expected.y, 5)
  })
})
