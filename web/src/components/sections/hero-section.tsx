import Image from 'next/image';
import type {Profile} from '@/lib/api/types';

/**
 * Hero / opening composition (design spec §20, §21).
 *
 * Server Component. It receives an already-validated {@link Profile} and never
 * fetches. Its exact content hierarchy is fixed by §21:
 *
 *   h1                 = profile.name   (the only h1 on the page)
 *   prominent headline = profile.headline
 *   summary            = profile.short_summary
 *   availability       = profile.availability
 *   CTA (real anchor)  = profile.cta -> #work
 *
 * No eyebrow, positioning claim, or invented copy is added.
 *
 * Photography is genuinely nullable:
 *
 *   photo object -> intentional text/photo split (next/image, CMS url + alt
 *                   used verbatim, stable aspect-ratio box, no layout shift)
 *   photo null   -> intentional text-only composition. No <img>, initials,
 *                   avatar, placeholder, prototype path, or reserved media
 *                   column: the `.hero--no-photo` modifier removes it entirely
 *                   while the deliberate reading measure is retained.
 *
 * `#top` is the hero's own anchor (used by the header identity link). It is
 * deliberately outside the FragmentFocusManager allowlist and uses native
 * browser positioning, so the section carries no `tabIndex`.
 */

const HERO_NAME_ID = 'hero-name';

type HeroSectionProps = {
  profile: Profile;
};

export function HeroSection({profile}: HeroSectionProps) {
  const {photo} = profile;

  return (
    <section
      id="top"
      aria-labelledby={HERO_NAME_ID}
      className={photo ? 'hero' : 'hero hero--no-photo'}
    >
      <div className="hero__inner site-container">
        <div className="hero__text">
          <h1 id={HERO_NAME_ID} className="hero__name">
            {profile.name}
          </h1>
          <p className="hero__headline">{profile.headline}</p>
          <p className="hero__summary">{profile.short_summary}</p>
          <p className="hero__availability">{profile.availability}</p>
          <a className="hero__cta" href="#work">
            {profile.cta}
          </a>
        </div>

        {photo ? (
          <figure className="hero__media">
            <Image
              className="hero__photo"
              src={photo.url}
              alt={photo.alt}
              fill
              priority
              sizes="(min-width: 64rem) 36rem, (min-width: 48rem) 22rem, 88vw"
            />
          </figure>
        ) : null}
      </div>
    </section>
  );
}
