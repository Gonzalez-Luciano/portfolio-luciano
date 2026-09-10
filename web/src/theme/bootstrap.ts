/**
 * Pre-paint IIFE injected into `<head>` by the localized layout.
 *
 * Responsibilities, both before first paint:
 *  1. `document.documentElement.dataset.theme` — the existing theme authority
 *     (stored `portfolio_theme` preference, else system preference). No second
 *     cookie or storage key is introduced.
 *  2. `document.documentElement.dataset.js = 'ready'` — a single "JavaScript is
 *     present" marker (`data-js="ready"`). Server markup never contains it, so
 *     CSS can hide hydration-only controls (mobile menu trigger, theme toggle)
 *     when JavaScript is disabled and this marker is therefore absent.
 */
export const themeBootstrapSource =
  "(()=>{const e=document.documentElement;e.dataset.js='ready';let t=null;try{const r=window.localStorage.getItem('portfolio_theme');'light'===r||'dark'===r?t=r:null!==r&&window.localStorage.removeItem('portfolio_theme')}catch{}e.dataset.theme=t??(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')})();";
