import type { Metadata } from "next";
import { buildSeoMetadata } from "@/lib/seo/metadata";
import { buildPageJsonLd } from "@/lib/seo/schema";
import { CalculatorPageLayout } from "@/components/layout/CalculatorPageLayout";
import { CalculatorHero } from "@/components/sections/CalculatorHero";
import { SalaryCalculator } from "@/components/calculator/SalaryCalculator";
import { ExplanationSection } from "@/components/sections/ExplanationSection";
import { FaqSection } from "@/components/sections/FaqSection";
import { RelatedPagesSection } from "@/components/sections/RelatedPagesSection";
import { Breadcrumbs } from "@/components/navigation/Breadcrumbs";
import { MethodologySection } from "@/components/sections/MethodologySection";
import { DisclaimerSection } from "@/components/sections/DisclaimerSection";
import { LastUpdatedSection } from "@/components/sections/LastUpdatedSection";
import {
  loadCountries,
  loadProfessions,
  loadCities
} from "@/lib/data/loaders";
import {
  getContentBlocksForContext,
  getFaqTemplatesForContext
} from "@/lib/data/selectors";
import { getRelatedForProfessionPage } from "@/lib/data/related";
import { renderFaqTemplates, renderContentBlocksToParagraphs } from "@/lib/content/compose";
import { getTrustConfigForPageType } from "@/lib/data/trust";
import { getCalculatorPresetForRoute } from "@/lib/data/presets";
import {
  getLocaleForCountry,
  getCurrencyForCountry,
  getCurrencySymbolForCountry
} from "@/lib/data/country-context";
import { buildSeoDecisionForRoute } from "@/lib/seo/decisions";
import { scoreFromPageQualitySignals } from "@/lib/data/quality";
import { logPageAuditInDev } from "@/lib/seo/page-audit";

export async function generateStaticParams() {
  const professions = await loadProfessions();
  return professions.map((profession) => ({
    professionSlug: profession.slug
  }));
}

export async function generateMetadata({
  params
}: {
  params: { professionSlug: string };
}): Promise<Metadata> {
  const [countries, professions, cities] = await Promise.all([
    loadCountries(),
    loadProfessions(),
    loadCities()
  ]);

  const profession = professions.find((p) => p.slug === params.professionSlug);
  if (!profession) return {};

  const primaryCountry = countries[0];
  if (!primaryCountry) return {};

  const contentBlocks = await getContentBlocksForContext({
    pageType: "profession",
    countryId: primaryCountry.id,
    professionId: profession.id,
    professionCategory: profession.category
  });

  const faqTemplates = await getFaqTemplatesForContext({
    pageType: "profession",
    countryId: primaryCountry.id,
    professionId: profession.id,
    professionCategory: profession.category
  });
  const faqItems = renderFaqTemplates(faqTemplates, {
    countryName: primaryCountry.name,
    professionName: profession.name,
    professionCategory: profession.category,
    year: new Date().getFullYear()
  });

  const relevantCities = cities.filter((city) =>
    city.supportedProfessionIds.includes(profession.id)
  );

  const relatedForQuality = getRelatedForProfessionPage({
    country: primaryCountry,
    profession,
    cities: relevantCities
  });

  const qualityScore = scoreFromPageQualitySignals({
    explanationBlocks: contentBlocks,
    faqCount: faqItems.length,
    relatedCount: relatedForQuality.length
  });

  const seoResult = buildSeoMetadata({
    pageType: "profession",
    country: {
      id: primaryCountry.id,
      slug: primaryCountry.slug,
      name: primaryCountry.name,
      isoCode: primaryCountry.isoCode,
      currencyCode: primaryCountry.currencyCode
    },
    profession: {
      id: profession.id,
      slug: profession.slug,
      name: profession.name,
      category: profession.category
    },
    ...( { pathname: `/salary-calculator/profession/${profession.slug}` } as any)
  });
  const metadata = seoResult.metadata;

  const decision = buildSeoDecisionForRoute({
    routeType: "profession",
    qualityScore
  });

  return {
    ...metadata,
    robots: decision.noindex
      ? {
          index: false,
          follow: true
        }
      : metadata.robots,
    other: {
      ...(metadata as any).other,
      ...(decision.includeJsonLd && {
        "ld+json": buildPageJsonLd({
          pageType: "profession",
          breadcrumbs: [
            { name: "Главная", url: "https://www.example-salary-calculator.com/" },
            {
              name: "Калькулятор зарплаты",
              url: "https://www.example-salary-calculator.com/salary-calculator"
            },
            {
              name: `Зарплата ${profession.name}`,
              url: `https://www.example-salary-calculator.com/salary-calculator/profession/${profession.slug}`
            }
          ],
          faqItems
        })
      })
    }
  };
}

export default async function ProfessionSalaryCalculatorPage({
  params
}: {
  params: { professionSlug: string };
}) {
  const [countries, professions, cities] = await Promise.all([
    loadCountries(),
    loadProfessions(),
    loadCities()
  ]);

  const profession = professions.find((p) => p.slug === params.professionSlug);
  if (!profession) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const primaryCountry = countries[0];
  if (!primaryCountry) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const locale = getLocaleForCountry(countries, primaryCountry.id);
  const currencyCode = getCurrencyForCountry(countries, primaryCountry.id);
  const currencySymbol = getCurrencySymbolForCountry(countries, primaryCountry.id);

  const faqTemplates = await getFaqTemplatesForContext({
    pageType: "profession",
    countryId: primaryCountry.id,
    professionId: profession.id,
    professionCategory: profession.category
  });

  const contentBlocks = await getContentBlocksForContext({
    pageType: "profession",
    countryId: primaryCountry.id,
    professionId: profession.id,
    professionCategory: profession.category
  });

  const templateContext = {
    countryName: primaryCountry.name,
    professionName: profession.name,
    professionCategory: profession.category,
    year: new Date().getFullYear()
  };
  const explanationParagraphs = renderContentBlocksToParagraphs(contentBlocks, templateContext);
  const faqItems = renderFaqTemplates(faqTemplates, templateContext);

  const relevantCities = cities.filter((city) =>
    city.supportedProfessionIds.includes(profession.id)
  );

  const related = getRelatedForProfessionPage({
    country: primaryCountry,
    profession,
    cities: relevantCities
  });

  const trust = getTrustConfigForPageType("profession");
  const preset = getCalculatorPresetForRoute("profession");

  logPageAuditInDev(`profession-${profession.slug}`, {
    routeType: "profession",
    trustMethodology: trust.methodology,
    explanationParagraphs,
    relatedCount: related.length,
    locale,
    currencyCode,
    countryId: primaryCountry.id,
    countries
  });

  return (
    <CalculatorPageLayout
      breadcrumbsSlot={
        <Breadcrumbs
          items={[
            { label: "Главная", href: "/" },
            { label: "Калькулятор зарплаты", href: "/salary-calculator" },
            { label: profession.name }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          title={`Зарплата ${profession.name}`}
          subtitle={`Посчитайте gross и net зарплату для роли ${profession.name} и посмотрите, как меняется картина по городам.`}
          eyebrow={primaryCountry.name}
        />
      }
      calculatorSlot={
        <SalaryCalculator
          countryId={primaryCountry.id}
          contextLabel={profession.name}
          locale={locale}
          currencyCode={currencyCode}
          currencySymbol={currencySymbol}
          routeType="profession"
          defaultMode={preset.defaultMode}
          helperText={preset.helperText}
          comparisonHint={preset.comparisonHint}
        />
      }
      explanationSlot={
        <>
          <ExplanationSection paragraphs={explanationParagraphs} />
          <MethodologySection paragraphs={trust.methodology} />
          <DisclaimerSection paragraphs={trust.disclaimer} />
          <LastUpdatedSection value={trust.lastUpdated} />
        </>
      }
      faqSlot={<FaqSection items={faqItems} />}
      relatedSlot={<RelatedPagesSection items={related} />}
    />
  );
}

