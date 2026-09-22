import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { ProjectDossier } from '@/components/ProjectDossier'
import { uiCopy } from '@/content'
import type { Project } from '@/lib/api'

const project: Project = {
  key: 'mobile-grid',
  kind: 'personal',
  client_name: null,
  title: 'Mobile grid',
  role: 'Developer',
  status: 'in_development',
  summary: 'A project fixture.',
  problem: 'Problem',
  solution: 'Solution',
  result: 'Result',
  featured: false,
  images: [],
  demo_url: 'https://demo.example.test',
  repository_url: 'https://github.com/example/project',
  technologies: [],
}

describe('ProjectDossier', () => {
  it('constrains its mobile grid track while retaining the desktop dossier columns', () => {
    const markup = renderToStaticMarkup(createElement(ProjectDossier, { ui: uiCopy('en'), project }))
    const article = markup.match(/<article[^>]*>/)?.[0]

    expect(article).toContain('grid-cols-[minmax(0,1fr)]')
    expect(article).toContain('lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]')
  })

  it('leaves project demo and repository links uninstrumented', () => {
    const markup = renderToStaticMarkup(createElement(ProjectDossier, { ui: uiCopy('en'), project }))

    expect(markup).toContain('https://demo.example.test')
    expect(markup).toContain('https://github.com/example/project')
    expect(markup).not.toContain('data-umami-event=')
  })
})
