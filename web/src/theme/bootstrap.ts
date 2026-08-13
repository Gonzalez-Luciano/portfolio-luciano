export const themeBootstrapSource =
  "(()=>{const e=document.documentElement;let t=null;try{const r=window.localStorage.getItem('portfolio_theme');'light'===r||'dark'===r?t=r:null!==r&&window.localStorage.removeItem('portfolio_theme')}catch{}e.dataset.theme=t??(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')})();";
