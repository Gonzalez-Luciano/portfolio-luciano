import type {ProfessionalLink, SiteConfiguration} from '@/lib/api/types';
import {NeutralEmptyState} from '@/components/ui/content-state';

/**
 * Contact and CV — `#contact` (design spec §23, §16, §20, §8).
 *
 * Server Component. It consumes ONLY `site.contact_intro`,
 * `site.professional_links`, and `site.cv`, all already validated, and never
 * fetches. `<section id="contact">` ALWAYS renders on a structurally valid
 * page, with a VISIBLE `<h2 id="contact-heading">`.
 *
 * Validity (spec §23 / §16): Contact renders normally when a presentable intro
 * OR any link OR a cv provides content — an intro alone is enough.
 * `<NeutralEmptyState>` appears ONLY when the intro is empty/whitespace AND
 * `links` is `[]` AND `cv` is `null`.
 *
 * Link new-tab matrix (Phase 2 prototype + spec §23):
 *   key === 'linkedin' | 'github' -> `target="_blank"`,
 *       `rel="me noopener noreferrer"`, and a visually-hidden localized
 *       "opens in a new tab" announcement inside the anchor. The visible text
 *       stays `link.label`.
 *   key === 'email'              -> normal anchor (href is already `mailto:`).
 *   any other future key         -> normal same-context anchor.
 *
 * CV (spec §23): `cv` object -> a normal anchor using `cv.url` VERBATIM and
 * `cv.label`; no new tab, no announcement. `cv === null` -> no CV action, no
 * placeholder. There is deliberately no hardcoded CV route, client PDF fetch,
 * or other-locale fallback here — Laravel/Caddy own the download route.
 */

const CONTACT_HEADING_ID = 'contact-heading';

/** The two link keys the Phase 2 contact prototype approved for a new tab. */
const NEW_TAB_KEYS: ReadonlySet<ProfessionalLink['key']> = new Set([
  'linkedin',
  'github',
]);

export type ContactSectionLabels = {
  /** `Portfolio.sections.contact` — the visible section heading. */
  sectionTitle: string;
  /** `Portfolio.state.neutralEmpty`. */
  neutralEmpty: string;
  /** `Portfolio.fields.opensNewTab`. */
  opensNewTab: string;
};

type ContactSectionProps = {
  intro: string;
  links: SiteConfiguration['professional_links'];
  cv: SiteConfiguration['cv'];
  labels: ContactSectionLabels;
};

/** Split a presentable intro into display paragraphs; `[]` when it is blank. */
function toParagraphs(intro: string): string[] {
  return intro
    .split(/\n{2,}/)
    .map((block) => block.trim())
    .filter((block) => block.length > 0);
}

export function ContactSection({
  intro,
  links,
  cv,
  labels,
}: ContactSectionProps) {
  const paragraphs = toParagraphs(intro);
  const hasContent = paragraphs.length > 0 || links.length > 0 || cv !== null;

  return (
    <section
      id="contact"
      tabIndex={-1}
      aria-labelledby={CONTACT_HEADING_ID}
      className="contact"
    >
      <h2 id={CONTACT_HEADING_ID} className="contact__heading">
        {labels.sectionTitle}
      </h2>

      {hasContent ? (
        <>
          {paragraphs.map((paragraph, index) => (
            <p
              key={`${index}-${paragraph.slice(0, 24)}`}
              className="contact__intro"
            >
              {paragraph}
            </p>
          ))}

          {links.length > 0 || cv !== null ? (
            <ul className="contact__actions">
              {links.map((link) => (
                <li key={link.key} className="contact__action-item">
                  <ContactLink link={link} opensNewTabLabel={labels.opensNewTab} />
                </li>
              ))}
              {cv !== null ? (
                <li className="contact__action-item">
                  <a className="contact__action" href={cv.url}>
                    {cv.label}
                  </a>
                </li>
              ) : null}
            </ul>
          ) : null}
        </>
      ) : (
        <NeutralEmptyState message={labels.neutralEmpty} />
      )}
    </section>
  );
}

type ContactLinkProps = {
  link: ProfessionalLink;
  opensNewTabLabel: string;
};

function ContactLink({link, opensNewTabLabel}: ContactLinkProps) {
  const opensNewTab = NEW_TAB_KEYS.has(link.key);

  if (opensNewTab) {
    return (
      <a
        className="contact__action"
        href={link.href}
        target="_blank"
        rel="me noopener noreferrer"
      >
        {link.label}
        <span className="visually-hidden"> ({opensNewTabLabel})</span>
      </a>
    );
  }

  return (
    <a className="contact__action" href={link.href}>
      {link.label}
    </a>
  );
}
