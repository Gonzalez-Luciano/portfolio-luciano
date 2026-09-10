import type {Metadata} from 'next';
import {hasLocale} from 'next-intl';
import {getTranslations, setRequestLocale} from 'next-intl/server';
import {notFound} from 'next/navigation';
import {AboutSection} from '@/components/sections/about-section';
import {ApproachSection} from '@/components/sections/approach-section';
import {ContactSection} from '@/components/sections/contact-section';
import {ExpertiseGroup} from '@/components/sections/expertise-group';
import {HeroSection} from '@/components/sections/hero-section';
import {ProjectsSection} from '@/components/sections/projects-section';
import {WorkGroup} from '@/components/sections/work-group';
import {StructuralFailure} from '@/components/ui/content-state';
import {loadPublicPortfolio} from '@/lib/api/load-public-portfolio';
import {locales, routing} from '@/i18n/routing';

/**
 * The single coordinated SSR decision point for the public portfolio route
 * (design spec §13–§17, §20, §28).
 *
 * It calls the shared request-scoped loader exactly once (the same `cache()`d
 * export consumed by {@link generateMetadata} and the localized layout) and
 * applies criticality here — not in the loader and not in the sections:
 *
 *   Profile OR Site failed/malformed -> structural failure: the localized
 *   technical surface only. No Hero, no professional section, no five primary
 *   landmarks (the layout already renders the header's structural-failure
 *   variant), no hardcoded professional/contact fallback.
 *
 *   otherwise -> every section renders and applies its OWN empty / regional /
 *   failure policy. Collection failures stay regional; the page still renders.
 *
 * There is no BFF, no per-endpoint Suspense, no six-stage streaming, and no
 * consolidated view model.
 */

// Spec §14: the localized portfolio route renders dynamically at request time;
// the production build must succeed while Laravel is unavailable and must not
// execute the loader at build time.
export const dynamic = 'force-dynamic';

type LocalePageProps = {
  params: Promise<{locale: string}>;
};

export function generateStaticParams() {
  return locales.map((locale) => ({locale}));
}

export async function generateMetadata({
  params,
}: LocalePageProps): Promise<Metadata> {
  const {locale} = await params;

  if (!hasLocale(routing.locales, locale)) {
    return {title: 'Portfolio unavailable'};
  }

  setRequestLocale(locale);
  const results = await loadPublicPortfolio(locale);

  if (results.profile.ok) {
    const {name, headline, short_summary} = results.profile.data;

    // Spec §28: name, space, EM DASH (U+2014), space, headline.
    return {title: `${name} — ${headline}`, description: short_summary};
  }

  return {
    title: locale === 'es' ? 'Portfolio no disponible' : 'Portfolio unavailable',
  };
}

export default async function LocalePage({params}: LocalePageProps) {
  const {locale} = await params;

  if (!hasLocale(routing.locales, locale)) {
    notFound();
  }

  setRequestLocale(locale);
  const t = await getTranslations({locale, namespace: 'Portfolio'});
  const results = await loadPublicPortfolio(locale);

  const retry = {label: t('state.retry'), pendingLabel: t('state.retryPending')};

  // Criticality (spec §15.1): Profile or Site failed/malformed => structural
  // failure. The header already renders its structural-failure variant from the
  // layout, so no professional landmarks or hardcoded fallback are emitted here.
  if (!results.profile.ok || !results.site.ok) {
    return (
      <div className="site-container">
        <StructuralFailure message={t('state.structuralFailure')} retry={retry} />
      </div>
    );
  }

  const profile = results.profile.data;
  const site = results.site.data;

  return (
    <div className="site-container">
      <HeroSection profile={profile} />
      <AboutSection
        introduction={profile.introduction}
        heading={t('sections.about')}
      />
      <WorkGroup
        workCases={results.workCases}
        experiences={results.experiences}
        locale={locale}
        labels={{
          group: t('nav.work'),
          workCases: t('sections.workCases'),
          experience: t('sections.experience'),
          fields: {
            context: t('fields.context'),
            problem: t('fields.problem'),
            contribution: t('fields.contribution'),
            technicalApproach: t('fields.technicalApproach'),
            outcome: t('fields.outcome'),
          },
          currentExperienceEnd: t('fields.currentExperienceEnd'),
          neutralEmpty: t('state.neutralEmpty'),
          regionalFailure: t('state.regionalFailure'),
        }}
        retry={retry}
      />
      <ExpertiseGroup
        areas={site.expertise_areas}
        groups={site.technology_groups}
        technologies={results.technologies}
        labels={{
          group: t('nav.expertise'),
          specialties: t('sections.specialties'),
          technologies: t('sections.technologies'),
          neutralEmpty: t('state.neutralEmpty'),
          regionalFailure: t('state.regionalFailure'),
        }}
        retry={retry}
      />
      <ProjectsSection
        projects={results.projects}
        emptyMessage={site.projects_empty_message}
        labels={{
          sectionTitle: t('sections.projects'),
          problem: t('fields.problem'),
          solution: t('fields.solution'),
          regionalFailure: t('state.regionalFailure'),
        }}
        retry={retry}
      />
      <ApproachSection
        principles={site.work_principles}
        labels={{
          sectionTitle: t('sections.approach'),
          neutralEmpty: t('state.neutralEmpty'),
        }}
      />
      <ContactSection
        intro={site.contact_intro}
        links={site.professional_links}
        cv={site.cv}
        labels={{
          sectionTitle: t('sections.contact'),
          neutralEmpty: t('state.neutralEmpty'),
          opensNewTab: t('fields.opensNewTab'),
        }}
      />
    </div>
  );
}
