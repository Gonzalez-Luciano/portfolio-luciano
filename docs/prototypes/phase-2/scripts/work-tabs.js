export function initWorkTabs() {
  const workTabs = document.querySelector('[data-work-tabs]');
  if (!workTabs) return;

  const tablist = workTabs.querySelector('[role="tablist"]');
  const tabs = [...workTabs.querySelectorAll('[data-work-tab]')];
  const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
  const desktopQuery = matchMedia('(min-width: 64rem)');
  let selectedIndex = 0;

  const select = (index, focus = false) => {
    selectedIndex = index;
    tabs.forEach((tab, tabIndex) => {
      const selected = tabIndex === selectedIndex;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      panels[tabIndex].hidden = !selected;
    });
    if (focus) tabs[selectedIndex].focus();
  };

  const setDesktopMode = () => {
    if (desktopQuery.matches) {
      workTabs.dataset.workEnhanced = '';
      tablist.setAttribute('aria-orientation', 'vertical');
      tabs.forEach((tab) => tab.setAttribute('role', 'tab'));
      panels.forEach((panel) => panel.setAttribute('role', 'tabpanel'));
      select(selectedIndex);
      return;
    }

    delete workTabs.dataset.workEnhanced;
    tablist.removeAttribute('aria-orientation');
    tabs.forEach((tab) => {
      tab.removeAttribute('role');
      tab.removeAttribute('aria-selected');
      tab.tabIndex = 0;
    });
    panels.forEach((panel) => {
      panel.removeAttribute('role');
      panel.hidden = false;
    });
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => {
      if (desktopQuery.matches) select(index);
    });
    tab.addEventListener('keydown', (event) => {
      if (!desktopQuery.matches) return;
      const keyToIndex = {
        ArrowUp: (index + tabs.length - 1) % tabs.length,
        ArrowDown: (index + 1) % tabs.length,
        Home: 0,
        End: tabs.length - 1,
      };
      if (event.key in keyToIndex) {
        event.preventDefault();
        tabs[keyToIndex[event.key]].focus();
      }
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        select(index);
      }
    });
  });

  setDesktopMode();
  desktopQuery.addEventListener('change', setDesktopMode);
}
