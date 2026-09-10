'use client';

import {
  Children,
  useCallback,
  useId,
  useRef,
  useState,
  useSyncExternalStore,
  type KeyboardEvent as ReactKeyboardEvent,
  type ReactNode,
} from 'react';

/**
 * Indexed Detail — progressive enhancement for the Work Cases dossiers
 * (design spec §22).
 *
 * The dossiers are server-rendered and passed as `children`, one per case. This
 * component NEVER fetches, animates, shares viewport state, or observes scroll.
 *
 *   initial / no-JS / < 64rem   -> every dossier visible and sequential
 *   count === 0                 -> render nothing (WorkGroup owns group-empty)
 *   count === 1                 -> a plain dossier, no tab semantics ever
 *   count >= 2 AND >= 64rem     -> accessible vertical tabs after hydration
 *
 * The desktop check is a component-LOCAL `(min-width: 64rem)` subscription via
 * `useSyncExternalStore` (no global breakpoint store, no shared viewport
 * state). The server snapshot is always `false`, so the initial HTML is the
 * sequential dossiers; the store reconciles both directions after hydration.
 * Crossing below `64rem` strips every tab role and reveals all dossiers;
 * crossing back re-applies tabs using the locally-remembered selected index, or
 * 0 when it is no longer valid.
 *
 * Keyboard model on the tablist (automatic activation, roving tabindex):
 *   ArrowDown / ArrowUp  -> move focus to the next / previous tab AND activate
 *                           it; clamped at the ends (no wrap)
 *   Home / End           -> focus + activate the first / last tab
 *   Tab                  -> enters / leaves the widget normally (only the active
 *                           tab is a tab stop)
 *   Enter / Space        -> not required for activation; no-op
 *
 * jsdom cannot prove real visual hiding, native Tab order, or the real media
 * query; Task 15 QA owns that.
 */

const DESKTOP_QUERY = '(min-width: 64rem)';

function hasMatchMedia(): boolean {
  return (
    typeof window !== 'undefined' && typeof window.matchMedia === 'function'
  );
}

function subscribeDesktop(onChange: () => void): () => void {
  if (!hasMatchMedia()) {
    return () => {};
  }

  const query = window.matchMedia(DESKTOP_QUERY);
  query.addEventListener('change', onChange);

  return () => {
    query.removeEventListener('change', onChange);
  };
}

function getDesktopSnapshot(): boolean {
  return hasMatchMedia() && window.matchMedia(DESKTOP_QUERY).matches;
}

function getServerDesktopSnapshot(): boolean {
  return false;
}

type IndexedWorkCasesProps = {
  /** One title per case, in Laravel order. Its length is the case count. */
  titles: string[];
  /** Optional stable keys per case, used for React list keys. */
  caseKeys?: string[];
  /** The server-rendered dossiers, one node per case. */
  children: ReactNode;
};

export function IndexedWorkCases({
  titles,
  caseKeys,
  children,
}: IndexedWorkCasesProps) {
  const baseId = useId();
  const count = titles.length;
  const panels = Children.toArray(children);

  const isDesktop = useSyncExternalStore(
    subscribeDesktop,
    getDesktopSnapshot,
    getServerDesktopSnapshot,
  );
  const [selectedIndex, setSelectedIndex] = useState(0);
  const tabRefs = useRef<Array<HTMLButtonElement | null>>([]);

  const tabId = (index: number) => `${baseId}-tab-${index}`;
  const panelId = (index: number) => `${baseId}-panel-${index}`;
  const keyFor = (index: number) => caseKeys?.[index] ?? `${baseId}-${index}`;

  const safeSelected =
    selectedIndex >= 0 && selectedIndex < count ? selectedIndex : 0;

  const activate = useCallback(
    (index: number) => {
      const clamped = Math.min(Math.max(index, 0), count - 1);
      setSelectedIndex(clamped);
      tabRefs.current[clamped]?.focus();
    },
    [count],
  );

  const handleTablistKeyDown = useCallback(
    (event: ReactKeyboardEvent<HTMLDivElement>) => {
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          activate(safeSelected + 1);
          break;
        case 'ArrowUp':
          event.preventDefault();
          activate(safeSelected - 1);
          break;
        case 'Home':
          event.preventDefault();
          activate(0);
          break;
        case 'End':
          event.preventDefault();
          activate(count - 1);
          break;
        default:
          break;
      }
    },
    [activate, count, safeSelected],
  );

  if (count === 0) {
    return null;
  }

  const promoted = isDesktop && count >= 2;

  if (!promoted) {
    return <div className="indexed-work-cases">{children}</div>;
  }

  return (
    <div className="indexed-work-cases indexed-work-cases--tabs">
      <div
        role="tablist"
        aria-orientation="vertical"
        className="indexed-work-cases__tablist"
        onKeyDown={handleTablistKeyDown}
      >
        {titles.map((title, index) => {
          const selected = index === safeSelected;

          return (
            <button
              key={keyFor(index)}
              ref={(node) => {
                tabRefs.current[index] = node;
              }}
              type="button"
              role="tab"
              id={tabId(index)}
              aria-controls={panelId(index)}
              aria-selected={selected}
              tabIndex={selected ? 0 : -1}
              className="indexed-work-cases__tab"
              onClick={() => activate(index)}
            >
              <span
                className="indexed-work-cases__tab-index"
                aria-hidden="true"
              >
                {String(index + 1).padStart(2, '0')}
              </span>
              <span className="indexed-work-cases__tab-label">{title}</span>
            </button>
          );
        })}
      </div>

      <div className="indexed-work-cases__panels">
        {panels.map((panel, index) => (
          <div
            key={keyFor(index)}
            role="tabpanel"
            id={panelId(index)}
            aria-labelledby={tabId(index)}
            tabIndex={0}
            hidden={index !== safeSelected}
            className="indexed-work-cases__panel"
          >
            {panel}
          </div>
        ))}
      </div>
    </div>
  );
}
