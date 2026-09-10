import {afterEach, describe, expect, it} from 'vitest';
import {cleanup, render, screen, within} from '@testing-library/react';
import type {Profile} from '@/lib/api/types';
import {HeroSection} from './hero-section';
import {AboutSection} from './about-section';

/**
 * Synthetic Profile data only. No real name, headline, or professional claim
 * appears here — these fixtures exist purely to exercise the render contract of
 * the two Profile-owned sections (design spec §20/§21).
 */
const baseProfile: Profile = {
  name: 'Synthetic Test Name',
  headline: 'Synthetic tagline for the prominent headline slot',
  short_summary: 'Synthetic short summary sentence shown only inside the hero.',
  introduction:
    'Synthetic introduction first paragraph.\n\nSynthetic introduction second paragraph.',
  availability: 'Synthetic availability statement.',
  cta: 'Synthetic call to action',
  photo: null,
};

const profileWithPhoto: Profile = {
  ...baseProfile,
  photo: {
    url: '/storage/synthetic-test/portrait.jpg',
    alt: 'Synthetic portrait alt text',
  },
};

const ABOUT_HEADING = 'Professional introduction';

afterEach(cleanup);

describe('HeroSection', () => {
  it('renders profile.name as the single h1 on the page', () => {
    render(<HeroSection profile={baseProfile} />);

    const level1 = screen.getAllByRole('heading', {level: 1});
    expect(level1).toHaveLength(1);
    expect(level1[0]).toHaveTextContent(baseProfile.name);
  });

  it('renders the headline, summary and availability exactly, with no extra heading or eyebrow', () => {
    render(<HeroSection profile={baseProfile} />);

    const hero = screen.getByRole('region', {name: baseProfile.name});
    expect(screen.getByText(baseProfile.headline)).toBeInTheDocument();
    expect(screen.getByText(baseProfile.short_summary)).toBeInTheDocument();
    expect(screen.getByText(baseProfile.availability)).toBeInTheDocument();

    // The prominent headline is not a second <h1> and the hero has exactly one
    // heading total — no invented eyebrow / positioning claim.
    expect(within(hero).getAllByRole('heading')).toHaveLength(1);
  });

  it('renders the CTA as a real link to the start of the Work sequence', () => {
    render(<HeroSection profile={baseProfile} />);

    const cta = screen.getByRole('link', {name: baseProfile.cta});
    expect(cta).toHaveAttribute('href', '#work');
  });

  it('is a landmark region named by the h1 and anchored at #top', () => {
    const {container} = render(<HeroSection profile={baseProfile} />);

    expect(
      screen.getByRole('region', {name: baseProfile.name}),
    ).toBeInTheDocument();
    expect(container.querySelector('section#top')).not.toBeNull();
  });

  it('renders an intentional text/photo composition when photo is present', () => {
    const {container} = render(<HeroSection profile={profileWithPhoto} />);

    const image = screen.getByRole('img', {name: profileWithPhoto.photo!.alt});
    expect(image).toHaveAttribute('alt', profileWithPhoto.photo!.alt);
    // next/image may rewrite the src through /_next/image?url=… — the encoded
    // CMS reference must still be present, verbatim, with nothing prepended.
    expect(image.getAttribute('src')).toContain(
      encodeURIComponent(profileWithPhoto.photo!.url),
    );

    expect(container.querySelector('.hero__media')).not.toBeNull();
    expect(container.querySelector('.hero--no-photo')).toBeNull();
  });

  it('renders an intentional text-only composition when photo is null', () => {
    const {container} = render(<HeroSection profile={baseProfile} />);

    expect(screen.queryByRole('img')).toBeNull();
    expect(container.querySelector('img')).toBeNull();
    expect(container.querySelector('.hero__media')).toBeNull();

    // The media column collapses via the explicit modifier.
    expect(container.querySelector('.hero--no-photo')).not.toBeNull();

    const markup = container.innerHTML;
    expect(markup).not.toMatch(/initials|avatar|placeholder/i);
    expect(markup).not.toMatch(/web\/public|\/prototype|fallback/i);
  });
});

describe('AboutSection', () => {
  it('renders the introduction as its own paragraphs', () => {
    render(
      <AboutSection
        introduction={baseProfile.introduction}
        heading={ABOUT_HEADING}
      />,
    );

    expect(
      screen.getByText('Synthetic introduction first paragraph.'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('Synthetic introduction second paragraph.'),
    ).toBeInTheDocument();
  });

  it('renders a single-paragraph introduction without splitting artefacts', () => {
    render(
      <AboutSection
        introduction="One synthetic paragraph."
        heading={ABOUT_HEADING}
      />,
    );

    expect(screen.getByText('One synthetic paragraph.')).toBeInTheDocument();
  });

  it('does not duplicate the hero short summary', () => {
    render(
      <AboutSection
        introduction={baseProfile.introduction}
        heading={ABOUT_HEADING}
      />,
    );

    expect(screen.queryByText(baseProfile.short_summary)).toBeNull();
  });

  it('exposes the localized heading as a visible h2 and names the region', () => {
    render(
      <AboutSection
        introduction={baseProfile.introduction}
        heading={ABOUT_HEADING}
      />,
    );

    expect(
      screen.getByRole('heading', {level: 2, name: ABOUT_HEADING}),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('region', {name: ABOUT_HEADING}),
    ).toBeInTheDocument();
  });

  it('carries the #about id and a programmatic focus target', () => {
    const {container} = render(
      <AboutSection
        introduction={baseProfile.introduction}
        heading={ABOUT_HEADING}
      />,
    );

    const section = container.querySelector('section#about');
    expect(section).not.toBeNull();
    expect(section).toHaveAttribute('tabindex', '-1');
  });
});
