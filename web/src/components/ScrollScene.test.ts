import { createElement, createRef } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { afterAll, beforeAll, describe, expect, it, vi } from 'vitest'
import { ScrollScene } from '@/components/ScrollScene'
import { uiCopy } from '@/content'
import type { useVideoScrub } from '@/useVideoScrub'

beforeAll(() => {
  vi.stubGlobal('window', {
    matchMedia: () => ({
      matches: false,
      addEventListener: () => undefined,
      removeEventListener: () => undefined,
    }),
  })
})

afterAll(() => vi.unstubAllGlobals())

describe('ScrollScene', () => {
  it('extends the solid column veil through the left outer gutter', () => {
    const scrub = {
      containerRef: createRef<HTMLDivElement>(),
      videoRef: createRef<HTMLVideoElement>(),
      canvasRef: createRef<HTMLCanvasElement>(),
      scrollProgress: 0.7,
      canvasLive: false,
    } as ReturnType<typeof useVideoScrub>

    const markup = renderToStaticMarkup(
      createElement(ScrollScene, {
        scrub,
        scene: null,
        status: 'loading',
        ui: uiCopy('es'),
        onRetry: () => undefined,
      }),
    )

    const gutter = markup.match(/<div(?=[^>]*data-scene-veil-gutter)[^>]*>/)?.[0]

    expect(gutter).toContain('right-full')
    expect(gutter).toContain('width:max(0px, calc((100vw - 90rem) / 2))')
  })
})
