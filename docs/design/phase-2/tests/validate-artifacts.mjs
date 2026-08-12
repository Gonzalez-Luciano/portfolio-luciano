import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../../../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');
const required = [
  'docs/design/phase-2/SITEMAP.md',
  'docs/design/phase-2/WIREFRAMES.md',
  'docs/design/phase-2/DESIGN_TOKENS.md',
  'docs/design/phase-2/RESPONSIVE_SPEC.md',
];

for (const path of required) assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);

const sitemap = read(required[0]);
for (const id of ['top', 'work', 'expertise', 'projects', 'approach', 'contact']) {
  assert.match(sitemap, new RegExp(`\\| \\x60${id}\\x60 \\|`), `Missing anchor ${id}`);
}

const wireframes = read(required[1]);
for (const heading of [
  'Hero', 'Professional introduction', 'Work cases', 'Professional experience',
  'Specializations', 'Projects', 'Technologies', 'Approach', 'Contact',
  'Mobile menu', 'Loading state', 'Recoverable section error', 'Broad site error'
]) assert.ok(wireframes.includes(`## ${heading}`), `Missing wireframe ${heading}`);

const tokens = read(required[2]);
for (const value of [
  '#EEE9DE', '#F6F2E9', '#1B1E1C', '#565B56', '#A94322', '#817B70', '#5F635E', '#E4D8C9', '#8B2E24',
  '#171918', '#202321', '#F1ECE1', '#B8B3A9', '#F07A4B', '#777A74', '#96958F', '#2B2926', '#FF9B8A',
]) assert.ok(tokens.includes(value), `Missing approved color ${value}`);

for (const literal of ['Instrument Sans', 'IBM Plex Mono', '64rem', '90rem', '44 × 44px', '3px', '2px']) {
  assert.ok(tokens.includes(literal), `Missing token contract ${literal}`);
}

const responsive = read(required[3]);
for (const literal of ['320px', '360px', '390px', '768px', '1024px', '1440px', '48rem', '64rem', '80rem', '90rem']) {
  assert.ok(responsive.includes(literal), `Missing responsive contract ${literal}`);
}

const prototypeFiles = [
  'docs/prototypes/phase-2/README.md',
  'docs/prototypes/phase-2/index.html',
  'docs/prototypes/phase-2/es/index.html',
  'docs/prototypes/phase-2/en/index.html',
  'docs/prototypes/phase-2/styles/tokens.css',
  'docs/prototypes/phase-2/styles/base.css',
  'docs/prototypes/phase-2/styles/layout.css',
  'docs/prototypes/phase-2/styles/header.css',
  'docs/prototypes/phase-2/styles/work.css',
  'docs/prototypes/phase-2/styles/content.css',
  'docs/prototypes/phase-2/scripts/theme.js',
  'docs/prototypes/phase-2/scripts/main.js',
  'docs/prototypes/phase-2/scripts/menu.js',
  'docs/prototypes/phase-2/scripts/navigation.js',
  'docs/prototypes/phase-2/scripts/work-tabs.js',
];

for (const path of prototypeFiles) assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);

const stateFiles = [
  'docs/prototypes/phase-2/states/loading.html',
  'docs/prototypes/phase-2/states/section-error.html',
  'docs/prototypes/phase-2/states/site-error.html',
  'docs/prototypes/phase-2/states/missing-cv.html',
  'docs/prototypes/phase-2/states/projects-populated.html',
  'docs/prototypes/phase-2/styles/states.css',
];

for (const path of stateFiles) assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);
const prototypeStates = read(stateFiles[5]);
const stateTokens = read('docs/prototypes/phase-2/styles/tokens.css');

const loadingState = read(stateFiles[0]);
assert.match(loadingState, /aria-busy="true"/, 'Loading state must expose busy status');
assert.match(loadingState, /class="[^"]*skeleton/, 'Loading state needs skeleton blocks');
assert.match(loadingState, /<([a-z][\w-]*)\b[^>]*role="status"[^>]*>[\s\S]*?Loading portfolio content[\s\S]*?Cargando contenido del portfolio[\s\S]*?<\/\1>/i, 'Loading state must bind both localized messages to the same status element');
assert.match(prototypeStates, /\.skeleton\s*\{[^}]*animation:\s*none;[^}]*transition:\s*none;/s, 'Skeletons must be explicitly static by default');
assert.doesNotMatch(prototypeStates, /\bshimmer\b/i, 'State styles must not introduce shimmer');
const stateAnimationValues = [...prototypeStates.matchAll(/(?:^|[;{])\s*(?:animation|animation-name)\s*:\s*([^;]+)/g)]
  .map((match) => match[1].trim());
assert.ok(stateAnimationValues.every((value) => value === 'none'), 'State styles must not introduce an active animation');
for (const page of stateFiles.slice(0, 5)) {
  const statePage = read(page);
  assert.match(statePage, /href="\.\.\/styles\/states\.css"/, `${page} must import state styles`);
  assert.match(statePage, /const savedTheme = saved === 'light' \|\| saved === 'dark' \? saved : null;/, `${page} must validate a stored theme value`);
  assert.match(statePage, /document\.documentElement\.dataset\.theme = dark \? 'dark' : 'light';/, `${page} must initialize an explicit or system theme`);
}
assert.match(prototypeStates, /var\(--color-(?:text-primary|border-meaningful|error|surface-raised)\)/, 'State styles must use semantic color tokens');
assert.match(prototypeStates, /@media\s*\(prefers-reduced-motion:\s*reduce\)[\s\S]*?animation:\s*none;[\s\S]*?transition:\s*none;/, 'State styles must retain explicit reduced-motion static behavior');
assert.match(stateTokens, /--target-minimum:\s*44px;/, 'State interaction sizing requires the 44px minimum token contract');

const sectionErrorState = read(stateFiles[1]);
assert.match(sectionErrorState, /role="alert"/, 'Section error needs an alert');
assert.match(sectionErrorState, /couldn.t load|No pudimos cargar/i, 'Section error needs plain-language copy');
assert.match(sectionErrorState, /class="[^"]*retry-button/, 'Section error needs a retry button');
assert.match(prototypeStates, /\.retry-button\s*\{[^}]*min-height:\s*var\(--target-minimum\)/s, 'Retry button must use the 44px minimum target token');

const siteErrorState = read(stateFiles[2]);
for (const literal of ['data-site-header', 'data-theme-toggle', 'linkedin.com', 'github.com', 'mailto:', 'href="../index.html"']) {
  assert.ok(siteErrorState.includes(literal), `Site error must retain ${literal}`);
}
assert.match(siteErrorState, /state-page--site-error/, 'Site error needs a state-specific shell hook');
assert.match(siteErrorState, /site-error__language/, 'Site error needs a dedicated visible mobile language control');
assert.match(siteErrorState, /We couldn't load the portfolio content|No pudimos cargar el contenido del portfolio/, 'Site error needs safe copy');
assert.doesNotMatch(siteErrorState, /stack trace|status code|api\/|endpoint|exception/i, 'Site error must not expose internal details');

const missingCvState = read(stateFiles[3]);
for (const literal of ['Spanish / Español', 'English / Inglés', 'A locale never receives the other locale\'s CV as fallback.']) {
  assert.ok(missingCvState.includes(literal), `Missing-CV state must include ${literal}`);
}
assert.match(missingCvState, /CV unavailable in Spanish/, 'Spanish CV must be explicitly unavailable');
assert.match(missingCvState, /Download CV in English/, 'English CV must retain its own action');

const populatedProjectsState = read(stateFiles[4]);
const fixtureBanner = `Structural prototype fixture ${String.fromCodePoint(0x2014)} not Luciano's public work`;
assert.ok(populatedProjectsState.includes(fixtureBanner), 'Populated fixture needs the exact safety banner');
assert.match(populatedProjectsState, /data-fixture="structural"/, 'Populated fixture needs its structural data marker');
assert.match(populatedProjectsState, /class="project-dossier"/, 'Populated fixture needs a dossier with media');
assert.match(populatedProjectsState, /class="project-dossier project-dossier--no-media"/, 'Populated fixture needs a no-media dossier');
for (const label of ['Prototype project record A', 'Prototype project record B', 'Problem', 'Backend solution', 'Technical decisions', 'Technologies', 'Demo link absent', 'Repository link absent']) {
  assert.ok(populatedProjectsState.includes(label), `Populated fixture must include ${label}`);
}
assert.doesNotMatch(populatedProjectsState, /<a\b[^>]*href=/i, 'Populated fixture must not contain real, demo, or repository links');
const populatedFixtureContent = populatedProjectsState.match(/<body\b[^>]*>[\s\S]*<\/body>/i)?.[0] ?? '';
assert.doesNotMatch(populatedFixtureContent, /\b(?:https?|mailto|ftp):/i, 'Populated fixture content must not contain a URL scheme');
assert.doesNotMatch(populatedFixtureContent, /\b(?:achievement|award|client|employer|production|deployed|users|transactions|revenue|increased|reduced|improved|successful|professional experience)\b/i, 'Populated fixture must not imply an achievement or professional claim');

const requiredContentKeys = [
  'hero.title', 'hero.value', 'hero.cta', 'hero.availability', 'intro.body',
  'work.case.integrations', 'work.case.education', 'work.case.data-automation', 'work.case.layers',
  'work.experience.primary', 'work.experience.secondary', 'expertise.list', 'projects.zero',
  'technologies.list', 'approach.body', 'contact.body', 'contact.linkedin', 'contact.github', 'contact.email',
];

const contentKeys = (html) => [...html.matchAll(/data-content-key="([^"]+)"/g)].map((match) => match[1]).sort();
const externalRuntimeReference = /src="https?:\/\//i;
const prototypeTokens = read('docs/prototypes/phase-2/styles/tokens.css');
const prototypeHeader = read('docs/prototypes/phase-2/styles/header.css');
const prototypeMain = read('docs/prototypes/phase-2/scripts/main.js');
const prototypeNavigation = read('docs/prototypes/phase-2/scripts/navigation.js');
const prototypeHero = read('docs/prototypes/phase-2/styles/hero.css');
const prototypeWork = read('docs/prototypes/phase-2/styles/work.css');
const prototypeContent = read('docs/prototypes/phase-2/styles/content.css');
const prototypeWorkTabs = read('docs/prototypes/phase-2/scripts/work-tabs.js');
const fontFaceBlocks = [...prototypeTokens.matchAll(/@font-face\s*\{([^}]*)\}/gis)].map((match) => match[1]);
const fontFaceSources = fontFaceBlocks.flatMap((block) => [...block.matchAll(/url\(\s*(?:"([^"]*)"|'([^']*)'|([^\s)]+))\s*\)/gi)]
  .map((match) => match[1] ?? match[2] ?? match[3]));

assert.ok(fontFaceSources.length > 0, 'Missing local prototype @font-face sources');
for (const source of fontFaceSources) {
  assert.ok(!/^https?:\/\//i.test(source), `External runtime font URL in tokens.css: ${source}`);
}

assert.match(prototypeTokens, /@media\s*\(prefers-color-scheme:\s*dark\)\s*\{\s*:root:not\(\[data-theme\]\)/s, 'Missing no-JS system dark-theme fallback');
assert.match(prototypeHeader, /\[data-theme-toggle\],\s*\[data-menu-open\]\s*\{\s*display:\s*none;/s, 'JS-dependent controls must be hidden without JavaScript');
assert.match(prototypeHeader, /html\.js\s+\[data-theme-toggle\],\s*html\.js\s+\[data-menu-open\]\s*\{\s*display:\s*inline-flex;/s, 'JS-dependent controls must reappear after enhancement');
assert.match(prototypeHeader, /@media\s*\(min-width:\s*64rem\)\s*\{[\s\S]*?html\.js\s+\.site-header__menu-button\s*\{\s*display:\s*none;/s, 'Desktop enhancement must keep the mobile menu trigger hidden');
assert.match(prototypeHeader, /@media\s*\(max-width:\s*63\.99rem\)\s*\{[\s\S]*?\.state-page--site-error\s+\.site-error__language\s*\{\s*display:\s*inline-flex;/s, 'Site error language control must remain visible below 64rem');
assert.match(prototypeMain, /document\.documentElement\.classList\.add\('js'\);/, 'Main enhancement must identify JavaScript availability');

assert.match(prototypeNavigation, /window\.addEventListener\('hashchange'/, 'Missing hashchange fragment-focus handling');
assert.match(prototypeNavigation, /scheduleFragmentFocus\(window\.location\.hash\)/, 'Missing initial fragment-focus handling');
assert.match(prototypeHero, /\.hero__copy\s*\{[^}]*min-inline-size:\s*0;/s, 'Hero copy must shrink within narrow grid gutters');
assert.match(prototypeHero, /\.hero__copy\s+h1\s*\{[^}]*overflow-wrap:\s*anywhere;/s, 'Hero title must wrap safely at narrow widths');
assert.match(prototypeWork, /@media\s*\(min-width:\s*64rem\)/, 'Work desktop detail must begin at 64rem');
assert.match(prototypeWork, /--color-selected/, 'Selected work detail must use the approved selected surface token');
assert.match(prototypeWorkTabs, /export function initWorkTabs\(\)/, 'Missing initWorkTabs export');
assert.match(prototypeWorkTabs, /matchMedia\('\(min-width: 64rem\)'\)/, 'Work tabs must use the approved desktop breakpoint');
assert.match(prototypeWorkTabs, /tablist\.setAttribute\('role', 'tablist'\)/, 'Desktop enhancement must introduce the tablist role');
assert.match(prototypeWorkTabs, /tablist\.removeAttribute\('role'\)/, 'Narrow mode must remove the tablist role');
assert.match(prototypeContent, /\.project-dossier/, 'Missing reusable project dossier contract');
assert.match(prototypeContent, /@media\s*\(min-width:\s*48rem\)/, 'Project dossiers must become horizontal at 48rem');

for (const [locale, path] of [['es', prototypeFiles[2]], ['en', prototypeFiles[3]]]) {
  const page = read(path);
  assert.match(page, new RegExp(`<html[^>]+lang="${locale}"`, 'i'), `Missing ${locale} html language`);
  assert.equal((page.match(/<main\s+id="main-content"/gi) ?? []).length, 1, `Expected one main landmark in ${path}`);
  assert.equal((page.match(/<h1\b/gi) ?? []).length, 1, `Expected one H1 in ${path}`);
  assert.match(page, /<h1[^>]*>\s*Backend Developer \| PHP &(?:amp;)? Laravel\s*<\/h1>/i, `Missing hero title in ${path}`);
  assert.match(page, /<section(?=[^>]*class="[^"]*hero)(?=[^>]*id="top")(?=[^>]*aria-labelledby="hero-title")[^>]*>/i, `Missing semantic hero section in ${path}`);
  assert.match(page, /<picture\s+class="hero__portrait">/i, `Missing art-directed hero picture in ${path}`);
  assert.match(page, /<source(?=[^>]*media="\(max-width: 63\.99rem\)")(?=[^>]*srcset="\.\.\/assets\/images\/profile-wide\.webp")[^>]*>/i, `Missing narrow hero source in ${path}`);
  assert.match(page, /<img(?=[^>]*src="\.\.\/assets\/images\/profile-portrait\.webp")(?=[^>]*width="900")(?=[^>]*height="1200")(?=[^>]*alt="[^"]+")[^>]*>/i, `Missing portrait hero fallback in ${path}`);
  const expectedAlt = locale === 'es'
    ? 'Retrato profesional de Luciano González sobre fondo naranja'
    : 'Professional portrait of Luciano González against an orange background';
  assert.ok(page.includes(`alt="${expectedAlt}"`), `Missing approved ${locale} portrait alt text in ${path}`);
  assert.match(page, /<div\s+class="hero__copy">/i, `Missing hero copy wrapper in ${path}`);
  assert.match(page, /<h1(?=[^>]*id="hero-title")(?=[^>]*data-content-key="hero\.title")[^>]*>/i, `Missing keyed hero title in ${path}`);
  assert.match(page, /<a(?=[^>]*class="[^"]*text-action)(?=[^>]*href="#work")(?=[^>]*data-content-key="hero\.cta")[^>]*>/i, `Missing hero Work CTA in ${path}`);
  assert.match(page, /<section(?=[^>]*class="[^"]*editorial-bridge)[^>]*aria-labelledby="intro-title"[^>]*>/i, `Missing editorial bridge in ${path}`);
  assert.match(page, /<[^>]+data-content-key="intro\.body"[^>]*>/i, `Missing keyed introduction in ${path}`);
  assert.match(page, /<section(?=[^>]*id="work")(?=[^>]*aria-labelledby="work-heading")[^>]*>/i, `Missing semantic Work section in ${path}`);
  assert.match(page, /<[^>]+data-work-tabs[^>]*>/i, `Missing Work tab container in ${path}`);
  assert.ok(!/<[^>]+role="tablist"[^>]*>/i.test(page), `Work source must not include a tablist role in ${path}`);
  const caseIds = ['case-integrations', 'case-education', 'case-data-automation', 'case-layers'];
  const caseKeys = ['work.case.integrations', 'work.case.education', 'work.case.data-automation', 'work.case.layers'];
  for (let index = 0; index < caseIds.length; index += 1) {
    const id = caseIds[index];
    const key = caseKeys[index];
    assert.match(page, new RegExp(`<button(?=[^>]*data-work-tab)(?=[^>]*id="${id}-tab")(?=[^>]*aria-controls="${id}-panel")[^>]*>`, 'i'), `Missing linked Work tab ${id} in ${path}`);
    assert.match(page, new RegExp(`<article(?=[^>]*data-work-panel)(?=[^>]*id="${id}-panel")(?=[^>]*aria-labelledby="${id}-tab")[^>]*>`, 'i'), `Missing linked Work panel ${id} in ${path}`);
    assert.match(page, new RegExp(`<[^>]+data-content-key="${key.replaceAll('.', '\\.') }"[^>]*>`, 'i'), `Missing Work content key ${key} in ${path}`);
  }
  assert.equal((page.match(/\bdata-work-tab(?=\s|>|=)/g) ?? []).length, 4, `Expected four Work tabs in ${path}`);
  assert.equal((page.match(/data-work-panel/g) ?? []).length, 4, `Expected four Work panels in ${path}`);
  const casesEnd = page.indexOf('</div><!-- /.work-cases -->');
  const experienceStart = page.indexOf('data-content-key="work.experience.primary"');
  assert.ok(casesEnd >= 0 && experienceStart > casesEnd, `Experience must follow Work cases in ${path}`);
  assert.ok(!/carousel|role="(?:scrollbar|region)"[^>]*horizontal/i.test(page), `Disallowed scrolling UI in ${path}`);
  assert.ok(!/\b\d+(?:[.,]\d+)?\s*(?:%|ms|x|users|transactions|institutions|clients)\b/i.test(page), `Invented numeric metric in ${path}`);
  assert.match(page, /<section(?=[^>]*id="expertise")(?=[^>]*aria-labelledby="expertise-heading")[^>]*>/i, `Missing semantic Expertise section in ${path}`);
  const specializationList = page.match(/<ul class="expertise__statements"[\s\S]*?<\/ul>/i)?.[0] ?? '';
  assert.equal((specializationList.match(/<li>/g) ?? []).length, 6, `Expected six specialization statements in ${path}`);
  assert.match(page, /class="expertise__technologies"/, `Missing technology groups in ${path}`);
  assert.match(page, /<section(?=[^>]*id="projects")(?=[^>]*aria-labelledby="projects-heading")[^>]*>/i, `Missing semantic Projects section in ${path}`);
  assert.equal((page.match(/class="projects-zero"/g) ?? []).length, 1, `Expected one zero-project state in ${path}`);
  const renderedPage = page.replace(/<!--[\s\S]*?-->/g, '');
  assert.equal((renderedPage.match(/class="project-dossier"/g) ?? []).length, 0, `Default page must not contain project dossiers in ${path}`);
  const zeroCopy = locale === 'es'
    ? 'Actualmente no hay proyectos publicados en el portfolio. Los próximos proyectos se incorporarán cuando cuenten con una presentación técnica y pública adecuada.'
    : 'There are currently no published projects in the portfolio. Future projects will be added when they have an appropriate technical and public presentation.';
  assert.ok(page.includes(zeroCopy), `Missing approved zero-project copy in ${path}`);
  const approachCopy = locale === 'es'
    ? 'Priorizo entender el problema, cuidar la consistencia de la lógica y dejar soluciones mantenibles. El trabajo se comunica con claridad y se adapta al contexto técnico de cada aplicación.'
    : 'I prioritize understanding the problem, maintaining logic consistency, and leaving maintainable solutions. Work is communicated clearly and adapted to the technical context of each application.';
  assert.ok(page.includes(approachCopy), `Missing approved approach copy in ${path}`);
  assert.match(page, /class="approach__beats"/, `Missing supported approach reading beats in ${path}`);
  assert.match(page, /href="https:\/\/www\.linkedin\.com\/in\/luciano-gonz(?:%C3%A1|á)lez-590350294"/, `Missing approved LinkedIn URL in ${path}`);
  assert.match(page, /href="https:\/\/github\.com\/Gonzalez-Luciano"/, `Missing approved GitHub URL in ${path}`);
  assert.match(page, /href="mailto:lucianogonzalez12004@gmail\.com"/, `Missing approved email URL in ${path}`);
  assert.match(page, /rel="me noopener noreferrer"/, `External links need safe relationship metadata in ${path}`);
  if (locale === 'es') {
    assert.ok(!/\.pdf(?:["?#]|$)/i.test(page), `Spanish page must omit the unavailable CV action in ${path}`);
  } else {
    assert.match(page, /href="[^"\n]*cv-en\.pdf"/, `English page needs its own CV PDF action in ${path}`);
    assert.match(page, /Download Luciano González's CV in English \(PDF\)/, `English CV action needs its explicit accessible label in ${path}`);
  }
  for (const id of ['top', 'work', 'expertise', 'projects', 'approach', 'contact']) {
    assert.match(page, new RegExp(`id="${id}"`), `Missing shell ID ${id} in ${path}`);
  }
  assert.match(page, /<a(?=[^>]*href="#main-content")(?=[^>]*class="[^"]*skip-link)[^>]*>/, `Missing skip link in ${path}`);
  for (const literal of [
    'data-site-header', 'href="#work"', 'href="#expertise"', 'href="#projects"',
    'href="#approach"', 'href="#contact"', 'data-theme-toggle', 'data-menu-open',
    '<dialog id="mobile-menu"', 'aria-labelledby="mobile-menu-title"', 'data-menu-close',
  ]) assert.ok(page.includes(literal), `Missing ${literal} in ${path}`);
  for (const index of ['01', '02', '03', '04', '05']) {
    assert.match(page, new RegExp(`>\\s*${index}\\s*<`), `Missing mobile menu index ${index} in ${path}`);
  }
  const otherLocale = locale === 'es' ? '../en/' : '../es/';
  assert.match(page, new RegExp(`href="${otherLocale}"`), `Missing ${otherLocale} language link in ${path}`);
  assert.ok(!externalRuntimeReference.test(page), `External runtime reference in ${path}`);
  assert.deepEqual(contentKeys(page), [...requiredContentKeys].sort(), `Unexpected content-key contract in ${path}`);
}

console.log('Phase 2 artifact contracts pass.');
