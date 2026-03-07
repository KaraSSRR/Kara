import type { Metadata } from "next";
import { buildSeoMetadata } from "@/lib/seo/metadata";
import { buildPageJsonLd } from "@/lib/seo/schema";
import { buildSeoDecisionForRoute } from "@/lib/seo/decisions";
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
  getContentBlocksForContext,
  getFaqTemplatesForContext
} from "@/lib/data/selectors";
import { getRelatedForProfessionCityPage } from "@/lib/data/related";
import {
  buildProfessionCityPageInputFromSlugs,
  generateProfessionCityRouteInputs
} from "@/lib/data/generators";
import { loadCities } from "@/lib/data/loaders";
import {
  getLocaleForCountry,
  getCurrencyForCountry,
  getCurrencySymbolForCountry
} from "@/lib/data/country-context";
import { renderFaqTemplates, renderContentBlocksToParagraphs } from "@/lib/content/compose";
import { evaluateProfessionCityQuality } from "@/lib/data/quality";
import { getTrustConfigForPageType } from "@/lib/data/trust";
import { getCalculatorPresetForRoute } from "@/lib/data/presets";
import { logPageAuditInDev } from "@/lib/seo/page-audit";

export async function generateStaticParams() {
  const inputs = await generateProfessionCityRouteInputs({ minQualityScore: 0.5 });

  return inputs
    .filter((input) => {
      const evaluation = evaluateProfessionCityQuality(input.qualityScore);
      return evaluation.level !== "weak";
    })
    .map((input) => ({
      professionSlug: input.profession.slug,
      citySlug: input.city.slug
    }));
}

export async function generateMetadata({
  params
}: {
  params: { professionSlug: string; citySlug: string };
}): Promise<Metadata> {
  const input = await buildProfessionCityPageInputFromSlugs({
    countrySlug: params.citySlug.split("-")[0] ?? "",
    citySlug: params.citySlug,
    professionSlug: params.professionSlug
  });
  if (!input) return {};

  const evaluation = evaluateProfessionCityQuality(input.qualityScore);
  const decision = buildSeoDecisionForRoute({
    routeType: "profession-city",
    qualityScore: input.qualityScore
  });

  const { metadata } = buildSeoMetadata({
    pageType: "profession-city",
    country: {
      id: input.country.id,
      slug: input.country.slug,
      name: input.country.name,
      isoCode: input.country.isoCode,
      currencyCode: input.country.currencyCode
    },
    city: {
      id: input.city.id,
      slug: input.city.slug,
      name: input.city.name
    },
    profession: {
      id: input.profession.id,
      slug: input.profession.slug,
      name: input.profession.name,
      category: input.profession.category
    },
    ...( {
      pathname: `/salary-calculator/${input.profession.slug}/${input.city.slug}`
    } as any)
  });

  const faqTemplates = await getFaqTemplatesForContext({
    pageType: "profession-city",
    countryId: input.country.id,
    cityId: input.city.id,
    professionId: input.profession.id,
    professionCategory: input.profession.category
  });
  const faqItems = renderFaqTemplates(faqTemplates, {
    countryName: input.country.name,
    cityName: input.city.name,
    professionName: input.profession.name,
    professionCategory: input.profession.category,
    year: new Date().getFullYear()
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
      "ld+json": buildPageJsonLd({
        pageType: "profession-city",
        breadcrumbs: [
          { name: "Главная", url: "https://www.example-salary-calculator.com/" },
          {
            name: "Калькулятор зарплаты",
            url: "https://www.example-salary-calculator.com/salary-calculator"
          },
          {
            name: input.profession.name,
            url: `https://www.example-salary-calculator.com/salary-calculator/profession/${input.profession.slug}`
          },
          {
            name: `${input.profession.name} в ${input.city.name}`,
            url: `https://www.example-salary-calculator.com/salary-calculator/${input.profession.slug}/${input.city.slug}`
          }
        ],
        faqItems
      })
    }
  };
}

export default async function ProfessionCitySalaryCalculatorPage({
  params
}: {
  params: { professionSlug: string; citySlug: string };
}) {
  const input = await buildProfessionCityPageInputFromSlugs({
    countrySlug: params.citySlug.split("-")[0] ?? "",
    citySlug: params.citySlug,
    professionSlug: params.professionSlug
  });
  if (!input) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const cities = await loadCities();

  const faqTemplates = await getFaqTemplatesForContext({
    pageType: "profession-city",
    countryId: input.country.id,
    cityId: input.city.id,
    professionId: input.profession.id,
    professionCategory: input.profession.category
  });

  const contentBlocks = await getContentBlocksForContext({
    pageType: "profession-city",
    countryId: input.country.id,
    cityId: input.city.id,
    professionId: input.profession.id,
    professionCategory: input.profession.category
  });

  const templateContext = {
    countryName: input.country.name,
    cityName: input.city.name,
    professionName: input.profession.name,
    professionCategory: input.profession.category,
    year: new Date().getFullYear()
  };
  const explanationParagraphs = renderContentBlocksToParagraphs(contentBlocks, templateContext);
  const faqItems = renderFaqTemplates(faqTemplates, templateContext);

  const siblingCities = cities.filter(
    (c) =>
      c.countryId === input.country.id &&
      c.id !== input.city.id &&
      c.supportedProfessionIds.includes(input.profession.id)
  );

  const related = getRelatedForProfessionCityPage({
    country: input.country,
    city: input.city,
    profession: input.profession,
    siblingCities
  });

  const trust = getTrustConfigForPageType("profession-city");
  const preset = getCalculatorPresetForRoute("profession-city");
  const locale = getLocaleForCountry([input.country], input.country.id);
  const currencyCode = getCurrencyForCountry([input.country], input.country.id);
  const currencySymbol = getCurrencySymbolForCountry([input.country], input.country.id);

  logPageAuditInDev(`profession-city-${input.profession.slug}-${input.city.slug}`, {
    routeType: "profession-city",
    trustMethodology: trust.methodology,
    explanationParagraphs,
    relatedCount: related.length,
    locale,
    currencyCode,
    countryId: input.country.id,
    countries: [input.country]
  });

  return (
    <CalculatorPageLayout
      breadcrumbsSlot={
        <Breadcrumbs
          items={[
            { label: "Главная", href: "/" },
            { label: "Калькулятор зарплаты", href: "/salary-calculator" },
            {
              label: input.profession.name,
              href: `/salary-calculator/profession/${input.profession.slug}`
            },
            { label: input.city.name }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          title={`${input.profession.name} в ${input.city.name}: gross ↔ net`}
          subtitle={`Узнайте, сколько получает ${input.profession.name} в ${input.city.name} после налогов и обязательных взносов, и сравните с другими городами.`}
          eyebrow={input.country.name}
        />
      }
      calculatorSlot={
        <SalaryCalculator
          countryId={input.country.id}
          contextLabel={`${input.profession.name} в ${input.city.name}`}
          locale={locale}
          currencyCode={currencyCode}
          currencySymbol={currencySymbol}
          routeType="profession-city"
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

