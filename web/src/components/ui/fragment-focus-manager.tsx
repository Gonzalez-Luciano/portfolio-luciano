'use client';

import {useEffect} from 'react';
import {isPreservedFragment} from '@/i18n/anchors';

/**
 * Minimal focus enhancement for allowlisted fragment navigation (design spec
 * section 24).
 *
 * On initial hydration and on every `hashchange`, if `window.location.hash` is
 * one of the eleven shared preserved fragment ids and the target element
 * exists, it moves focus there with `focus({preventScroll: true})`. That is the
 * entire behavior: no `scrollTo`, no offset math, no smooth scroll, no history
 * mutation, no IntersectionObserver / scroll-spy, no hash rewriting on scroll.
 * `#top`, unknown hashes, and missing targets do nothing.
 */
function focusPreservedFragmentTarget(): void {
  const {hash} = window.location;

  if (!isPreservedFragment(hash)) {
    return;
  }

  const id = hash.slice(1);
  document.getElementById(id)?.focus({preventScroll: true});
}

export function FragmentFocusManager() {
  useEffect(() => {
    focusPreservedFragmentTarget();
    window.addEventListener('hashchange', focusPreservedFragmentTarget);

    return () => {
      window.removeEventListener('hashchange', focusPreservedFragmentTarget);
    };
  }, []);

  return null;
}
