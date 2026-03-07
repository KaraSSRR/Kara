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
  loadCities,
  loadCountries,
  loadProfessions
} from "@/lib/data/loaders";
import {
  getFaqTemplatesForContext,
  getContentBlocksForContext
} from "@/lib/data/selectors";
import { getRelatedForCityPage } from "@/lib/data/related";
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
  const cities = await loadCities();
  return cities.map((city) => ({
    citySlug: city.slug
  }));
}

export async function generateMetadata({
  params
}: {
  params: { citySlug: string };
}): Promise<Metadata> {
  const [cities, countries, professions] = await Promise.all([
    loadCities(),
    loadCountries(),
    loadProfessions()
  ]);
  const city = cities.find((c) => c.slug === params.citySlug);
  if (!city) return {};

  const country = countries.find((c) => c.id === city.countryId);
  if (!country) return {};

  const contentBlocks = await getContentBlocksForContext({
    pageType: "city",
    countryId: country.id,
    cityId: city.id
  });

  const supportedProfessions = professions.filter((p) =>
    city.supportedProfessionIds.includes(p.id)
  );

  const relatedForQuality = getRelatedForCityPage({
    country,
    city,
    professions: supportedProfessions
  });

  const faqTemplates = await getFaqTemplatesForContext({
    pageType: "city",
    countryId: country.id,
    cityId: city.id
  });
  const faqItems = renderFaqTemplates(faqTemplates, {
    countryName: country.name,
    cityName: city.name,
    year: new Date().getFullYear()
  });

  const qualityScore = scoreFromPageQualitySignals({
    explanationBlocks: contentBlocks,
    faqCount: faqItems.length,
    relatedCount: relatedForQuality.length
  });

  const seoResult = buildSeoMetadata({
    pageType: "city",
    country: {
      id: country.id,
      slug: country.slug,
      name: country.name,
      isoCode: country.isoCode,
      currencyCode: country.currencyCode
    },
    city: {
      id: city.id,
      slug: city.slug,
      name: city.name
    },
    ...( { pathname: `/salary-calculator/${city.slug}` } as any)
  });
  const metadata = seoResult.metadata;

  const decision = buildSeoDecisionForRoute({
    routeType: "city",
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
          pageType: "city",
          breadcrumbs: [
            { name: "Главная", url: "https://www.example-salary-calculator.com/" },
            {
              name: "Калькулятор зарплаты",
              url: "https://www.example-salary-calculator.com/salary-calculator"
            },
            {
              name: `Калькулятор в ${city.name}`,
              url: `https://www.example-salary-calculator.com/salary-calculator/${city.slug}`
            }
          ],
          faqItems
        })
      })
    }
  };
}

export default async function CitySalaryCalculatorPage({
  params
}: {
  params: { citySlug: string };
}) {
  const [cities, countries, professions] = await Promise.all([
    loadCities(),
    loadCountries(),
    loadProfessions()
  ]);

  const city = cities.find((c) => c.slug === params.citySlug);
  if (!city) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const country = countries.find((c) => c.id === city.countryId);
  if (!country) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const locale = getLocaleForCountry(countries, country.id);
  const currencyCode = getCurrencyForCountry(countries, country.id);
  const currencySymbol = getCurrencySymbolForCountry(countries, country.id);

  const faqTemplates = await getFaqTemplatesForContext({
    pageType: "city",
    countryId: country.id,
    cityId: city.id
  });

  const contentBlocks = await getContentBlocksForContext({
    pageType: "city",
    countryId: country.id,
    cityId: city.id
  });

  const templateContext = {
    countryName: country.name,
    cityName: city.name,
    year: new Date().getFullYear()
  };
  const explanationParagraphs = renderContentBlocksToParagraphs(contentBlocks, templateContext);
  const faqItems = renderFaqTemplates(faqTemplates, templateContext);

  const supportedProfessions = professions.filter((p) =>
    city.supportedProfessionIds.includes(p.id)
  );

  const related = getRelatedForCityPage({
    country,
    city,
    professions: supportedProfessions
  });

  const trust = getTrustConfigForPageType("city");
  const preset = getCalculatorPresetForRoute("city");

  logPageAuditInDev(`city-${city.slug}`, {
    routeType: "city",
    trustMethodology: trust.methodology,
    explanationParagraphs,
    relatedCount: related.length,
    locale,
    currencyCode,
    countryId: country.id,
    countries
  });

  return (
    <CalculatorPageLayout
      breadcrumbsSlot={
        <Breadcrumbs
          items={[
            { label: "Главная", href: "/" },
            { label: "Калькулятор зарплаты", href: "/salary-calculator" },
            { label: city.name }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          title={`Калькулятор зарплаты в ${city.name}`}
          subtitle={`Оцените gross и net зарплату в ${city.name} с учётом локальных налогов и стоимости жизни.`}
          eyebrow={country.name}
        />
      }
      calculatorSlot={
        <SalaryCalculator
          countryId={country.id}
          contextLabel={city.name}
          locale={locale}
          currencyCode={currencyCode}
          currencySymbol={currencySymbol}
          routeType="city"
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

