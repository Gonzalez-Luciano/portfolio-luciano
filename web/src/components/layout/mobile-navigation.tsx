'use client';

import {
  useCallback,
  useEffect,
  useId,
  useRef,
  useState,
  type MouseEvent as ReactMouseEvent,
  type RefObject,
} from 'react';
import {LanguageSwitcher} from '@/components/language-switcher';
import type {Locale} from '@/i18n/routing';
import {
  PrimaryNavigation,
  type PrimaryDestinationLabels,
} from './primary-navigation';

/**
 * Native `<dialog>` coordinator for the below-64rem menu (design spec §19).
 *
 * It coordinates ONLY: showModal/close, open state, an accessible dialog name,
 * initial focus inside the dialog, native Escape (`cancel`) closure, an explicit
 * Close control, closure on destination/locale selection, focus restoration to
 * the trigger, body-scroll lock/restore, unmount cleanup, and a component-local
 * `(min-width: 64rem)` listener that closes the dialog and moves focus to the
 * visible identity link when the viewport crosses into desktop.
 *
 * It does NOT implement a manual focus trap or `inert` — native
 * `<dialog>.showModal()` owns modality. No dialog/focus-trap dependency is used.
 * jsdom cannot prove native modality; Task 15 real-browser QA owns that.
 */

const DESKTOP_QUERY = '(min-width: 64rem)';

type MobileNavigationLabels = {
  /** `Portfolio.menu.open` */
  open: string;
  /** `Portfolio.menu.close` */
  close: string;
  /** `Portfolio.menu.title` */
  title: string;
  /** `Portfolio.nav.primaryLabel` */
  navLabel: string;
  destinations: PrimaryDestinationLabels;
  language: {label: string; spanish: string; english: string};
};

type MobileNavigationProps = {
  locale: Locale;
  labels: MobileNavigationLabels;
  /**
   * Where focus goes when the viewport crosses into desktop while the dialog is
   * open (the Menu trigger becomes hidden). When omitted, the component falls
   * back to `#site-header-home`, then the first desktop primary-nav anchor.
   */
  desktopFocusRef?: RefObject<HTMLElement | null>;
  desktopFocusId?: string;
};

export function MobileNavigation({
  locale,
  labels,
  desktopFocusRef,
  desktopFocusId = 'site-header-home',
}: MobileNavigationProps) {
  const [open, setOpen] = useState(false);
  const dialogRef = useRef<HTMLDialogElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const previousOverflowRef = useRef<string>('');
  const crossingToDesktopRef = useRef(false);
  const titleId = useId();

  const focusDesktopTarget = useCallback(() => {
    const explicit = desktopFocusRef?.current;

    if (explicit) {
      explicit.focus();
      return;
    }

    const identity = document.getElementById(desktopFocusId);

    if (identity) {
      identity.focus();
      return;
    }

    document
      .querySelector<HTMLElement>('#primary-navigation-desktop a')
      ?.focus();
  }, [desktopFocusRef, desktopFocusId]);

  // Drives the imperative dialog: open -> showModal + scroll lock + initial
  // focus; any transition back to closed OR unmount -> restore scroll + close.
  useEffect(() => {
    if (!open) {
      return;
    }

    const dialog = dialogRef.current;

    if (!dialog) {
      return;
    }

    if (!dialog.open) {
      dialog.showModal();
    }

    previousOverflowRef.current = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    closeButtonRef.current?.focus();

    return () => {
      document.body.style.overflow = previousOverflowRef.current;

      if (dialog.open) {
        dialog.close();
      }
    };
  }, [open]);

  // Single close-event sink: fires for the explicit Close, for native Escape
  // (`cancel` -> `close`), and for the breakpoint-cross close. It owns state
  // syncing and focus restoration.
  useEffect(() => {
    const dialog = dialogRef.current;

    if (!dialog) {
      return;
    }

    const handleCancel = () => {
      // Let the browser close the dialog; calling close() keeps jsdom and
      // browsers that skip the default in sync. No preventDefault.
      dialog.close();
    };

    const handleClose = () => {
      setOpen(false);

      if (crossingToDesktopRef.current) {
        crossingToDesktopRef.current = false;
        focusDesktopTarget();
        return;
      }

      triggerRef.current?.focus();
    };

    dialog.addEventListener('cancel', handleCancel);
    dialog.addEventListener('close', handleClose);

    return () => {
      dialog.removeEventListener('cancel', handleCancel);
      dialog.removeEventListener('close', handleClose);
    };
  }, [focusDesktopTarget]);

  // Component-local desktop media listener. No global breakpoint store.
  useEffect(() => {
    if (
      typeof window === 'undefined' ||
      typeof window.matchMedia !== 'function'
    ) {
      return;
    }

    const query = window.matchMedia(DESKTOP_QUERY);

    const handleChange = (event: MediaQueryListEvent) => {
      if (event.matches && dialogRef.current?.open) {
        crossingToDesktopRef.current = true;
        dialogRef.current.close();
      }
    };

    query.addEventListener('change', handleChange);

    return () => {
      query.removeEventListener('change', handleChange);
    };
  }, []);

  const closeFromSelection = useCallback(() => {
    dialogRef.current?.close();
  }, []);

  const handleDialogClick = useCallback(
    (event: ReactMouseEvent<HTMLDivElement>) => {
      const anchor = (event.target as HTMLElement).closest('a');

      if (anchor) {
        closeFromSelection();
      }
    },
    [closeFromSelection],
  );

  return (
    <div className="mobile-navigation">
      <button
        ref={triggerRef}
        type="button"
        className="mobile-navigation__trigger js-only"
        aria-haspopup="dialog"
        aria-expanded={open}
        onClick={() => setOpen(true)}
      >
        {labels.open}
      </button>

      <dialog
        ref={dialogRef}
        className="mobile-navigation__dialog"
        aria-labelledby={titleId}
      >
        <div className="mobile-navigation__panel">
          <div className="mobile-navigation__header">
            <p id={titleId} className="mobile-navigation__title">
              {labels.title}
            </p>
            <button
              ref={closeButtonRef}
              type="button"
              className="mobile-navigation__close"
              onClick={closeFromSelection}
            >
              {labels.close}
            </button>
          </div>

          {/* Event delegation closes the dialog on any destination-anchor
              activation; PrimaryNavigation stays a Server Component. The
              delegated click only augments real anchor activation (mouse and
              keyboard alike already dispatch click on <a>). */}
          <div className="mobile-navigation__links" onClick={handleDialogClick}>
            <PrimaryNavigation
              id="primary-navigation-dialog"
              navLabel={labels.navLabel}
              labels={labels.destinations}
              variant="stack"
            />
          </div>

          <LanguageSwitcher
            currentLocale={locale}
            label={labels.language.label}
            spanishLabel={labels.language.spanish}
            englishLabel={labels.language.english}
            onNavigate={closeFromSelection}
          />
        </div>
      </dialog>
    </div>
  );
}
