/**
 * Footer landmark (design spec §19).
 *
 * Renders ONLY optional CMS identity/link data plus structural markup. It has
 * NO duplicated biography, NO hardcoded professional URL, and NO invented copy.
 * In Task 5 nothing is passed, so it renders a bare `<footer>` landmark; Task 10
 * forwards the validated `site.professional_links` subset and the Profile name.
 */

export type SiteFooterLink = {
  key: string;
  label: string;
  href: string;
};

type SiteFooterProps = {
  name?: string;
  links?: readonly SiteFooterLink[];
};

export function SiteFooter({name, links}: SiteFooterProps) {
  const hasLinks = Array.isArray(links) && links.length > 0;

  return (
    <footer className="site-footer">
      <div className="site-footer__inner site-container">
        {name ? <p className="site-footer__identity">{name}</p> : null}
        {hasLinks ? (
          <ul className="site-footer__links">
            {links!.map((link) => (
              <li key={link.key}>
                <a href={link.href}>{link.label}</a>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
    </footer>
  );
}
