/**
 * One coordinated route shell (design spec §17).
 *
 * The localized layout owns the persistent header / `<main>` / footer chrome, so
 * this file supplies only neutral skeleton placeholders for the main region.
 * There is NO per-endpoint Suspense, NO six-stage streaming, and NO invented
 * name, role, experience, technology, project, or contact claim — the skeleton
 * is purely visual (no readable text).
 */
export default function Loading() {
  return (
    <div className="route-skeleton" aria-hidden="true">
      <div className="route-skeleton__hero">
        <div
          className="route-skeleton__block route-skeleton__block--display"
          data-skeleton
        />
        <div
          className="route-skeleton__block route-skeleton__block--lead"
          data-skeleton
        />
        <div className="route-skeleton__block" data-skeleton />
      </div>
      <div className="route-skeleton__section">
        <div
          className="route-skeleton__block route-skeleton__block--heading"
          data-skeleton
        />
        <div className="route-skeleton__block" data-skeleton />
        <div className="route-skeleton__block" data-skeleton />
        <div
          className="route-skeleton__block route-skeleton__block--wide"
          data-skeleton
        />
      </div>
      <div className="route-skeleton__section">
        <div
          className="route-skeleton__block route-skeleton__block--heading"
          data-skeleton
        />
        <div className="route-skeleton__block" data-skeleton />
        <div className="route-skeleton__block" data-skeleton />
      </div>
    </div>
  );
}
