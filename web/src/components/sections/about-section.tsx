import type {Profile} from '@/lib/api/types';

/**
 * Professional introduction / editorial bridge (design spec §20, §21).
 *
 * Server Component. It renders `profile.introduction` as its own paragraph(s)
 * and must NOT repeat `profile.short_summary` (the hero owns that).
 *
 * The heading text is passed in as `heading` so the component stays
 * provider-agnostic: the page supplies the localized `Portfolio.sections.about`
 * label ("Professional introduction" / "Presentación profesional").
 *
 * `#about` is in the cross-locale / focus allowlist, so the wrapper carries
 * `id="about"` and `tabIndex={-1}` — the same pattern Task 5 used for
 * `#main-content` — so `FragmentFocusManager` can move focus here without
 * adding a Tab stop.
 */

const ABOUT_HEADING_ID = 'about-heading';

type AboutSectionProps = {
  introduction: Profile['introduction'];
  heading: string;
};

function toParagraphs(introduction: string): string[] {
  const blocks = introduction
    .split(/\n{2,}/)
    .map((block) => block.trim())
    .filter((block) => block.length > 0);

  return blocks.length > 0 ? blocks : [introduction];
}

export function AboutSection({introduction, heading}: AboutSectionProps) {
  const paragraphs = toParagraphs(introduction);

  return (
    <section
      id="about"
      tabIndex={-1}
      aria-labelledby={ABOUT_HEADING_ID}
      className="about"
    >
      <div className="about__inner site-container">
        <h2 id={ABOUT_HEADING_ID} className="about__heading">
          {heading}
        </h2>
        {paragraphs.map((paragraph, index) => (
          <p
            key={`${index}-${paragraph.slice(0, 24)}`}
            className="about__paragraph"
          >
            {paragraph}
          </p>
        ))}
      </div>
    </section>
  );
}
