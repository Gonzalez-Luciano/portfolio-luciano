import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { SceneBackdrop } from '@/components/SceneBackdrop'

describe('SceneBackdrop', () => {
  it('renders every cloud with an explicit visible paint source', () => {
    const markup = renderToStaticMarkup(createElement(SceneBackdrop, { progress: 0.25, reducedMotion: false }))
    const clouds = markup.match(/<path(?=[^>]*data-scene-cloud)[^>]*>/g) ?? []

    expect(clouds).toHaveLength(5)
    expect(clouds.every((cloud) => /(?:stroke|fill)="[^"]+"/.test(cloud))).toBe(true)
  })
})
