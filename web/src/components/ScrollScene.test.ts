import { createElement, createRef } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { afterAll, beforeAll, describe, expect, it, vi } from 'vitest'
import { ScrollScene } from '@/components/ScrollScene'
import { uiCopy } from '@/content'
import type { useVideoScrub } from '@/useVideoScrub'
import type { SceneContent } from '@/lib/scene'

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

  it('instruments both real email anchors with the fixed email event', () => {
    const scrub = {
      containerRef: createRef<HTMLDivElement>(),
      videoRef: createRef<HTMLVideoElement>(),
      canvasRef: createRef<HTMLCanvasElement>(),
      scrollProgress: 1,
      canvasLive: false,
    } as ReturnType<typeof useVideoScrub>
    const scene: SceneContent = {
      name: 'Luciano González',
      headline: 'Backend Engineer',
      statement: null,
      summary: 'Backend developer.',
      closing: { lineOne: 'Available', lineTwo: null },
      eyebrow: null,
      photo: null,
      email: { key: 'email', label: 'Email Luciano', href: 'mailto:luciano@example.test' },
    }

    const markup = renderToStaticMarkup(
      createElement(ScrollScene, { scrub, scene, status: 'ready', ui: uiCopy('en'), onRetry: () => undefined }),
    )

    expect(markup.match(/data-umami-event="email-click"/g)).toHaveLength(2)
    expect(markup).not.toMatch(/data-umami-event-[^=]+=/)
  })
})
