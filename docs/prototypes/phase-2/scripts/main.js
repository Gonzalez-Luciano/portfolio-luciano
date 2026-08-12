import { initTheme } from './theme.js';
import { initMenu } from './menu.js';
import { initNavigation } from './navigation.js';
import { initWorkTabs } from './work-tabs.js';

document.documentElement.classList.add('js');
initTheme();
initMenu();
initNavigation();
initWorkTabs();
