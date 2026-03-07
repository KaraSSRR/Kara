import type { Metadata } from "next";
import { buildSeoMetadata } from "@/lib/seo/metadata";
import { buildPageJsonLd } from "@/lib/seo/schema";
import { buildSeoDecisionForRoute } from "@/lib/seo/decisions";
import { buildShareReadyMetadata } from "@/lib/seo/share-metadata";
import { CalculatorHero } from "@/components/sections/CalculatorHero";
import { ComparisonPageLayout } from "@/components/layout/ComparisonPageLayout";
import { ComparisonSnapshotCard } from "@/components/results/ComparisonSnapshotCard";
import { PrintableResultSection } from "@/components/results/PrintableResultSection";
import { ComparisonRefundShareBridge } from "@/components/results/ComparisonRefundShareBridge";
import { buildComparisonShareCard } from "@/lib/share/cards";
import { buildExportFormatsFromCard } from "@/lib/share/format";
import { RelatedPagesSection } from "@/components/sections/RelatedPagesSection";
import { Breadcrumbs } from "@/components/navigation/Breadcrumbs";
import { ExplanationSection } from "@/components/sections/ExplanationSection";
import { MethodologySection } from "@/components/sections/MethodologySection";
import { DisclaimerSection } from "@/components/sections/DisclaimerSection";
import { LastUpdatedSection } from "@/components/sections/LastUpdatedSection";
import { AssumptionsLimitationsSection } from "@/components/sections/AssumptionsLimitationsSection";
import { loadCities, loadCountries } from "@/lib/data/loaders";
import { getTrustConfigForPageType, getComparisonTrustNote } from "@/lib/data/trust";
import { getRelatedForCityPage } from "@/lib/data/related";
import { evaluateComparisonPageQuality } from "@/lib/data/quality";
import { getCalculatorPresetForRoute } from "@/lib/data/presets";
import { calculate, formatCurrency, formatPercent } from "@/lib/calculations";
import { getTaxConfigByCountryId } from "@/lib/calculations/config";
import { logPageAuditInDev } from "@/lib/seo/page-audit";
import { buildRefundComparisonSummary } from "@/lib/calculations/refund-config";
import {
  buildScenarioAwareSharePayload,
  pickDefaultRefundScenarioForCountry
} from "@/lib/calculations/refund-scenarios";

export async function generateStaticParams() {
  const [countries, cities] = await Promise.all([loadCountries(), loadCities()]);
  const topCities = cities
    .filter((city) => city.searchPriority >= 0.6)
    .sort((a, b) => b.searchPriority - a.searchPriority)
    .slice(0, 6);

  const params: { primarySlug: string; secondarySlug: string }[] = [];

  for (let i = 0; i < topCities.length; i++) {
    for (let j = i + 1; j < topCities.length; j++) {
      params.push({
        primarySlug: topCities[i].slug,
        secondarySlug: topCities[j].slug
      });
    }
  }

  return params;
}

export async function generateMetadata({
  params
}: {
  params: { primarySlug: string; secondarySlug: string };
}): Promise<Metadata> {
  const [countries, cities] = await Promise.all([loadCountries(), loadCities()]);

  const primaryCity = cities.find((c) => c.slug === params.primarySlug);
  const secondaryCity = cities.find((c) => c.slug === params.secondarySlug);
  if (!primaryCity || !secondaryCity) return {};

  const primaryCountry = countries.find((c) => c.id === primaryCity.countryId);
  const secondaryCountry = countries.find((c) => c.id === secondaryCity.countryId);
  if (!primaryCountry || !secondaryCountry) return {};

  const seo = buildSeoMetadata({
    pageType: "city",
    country: {
      id: primaryCountry.id,
      slug: primaryCountry.slug,
      name: primaryCountry.name,
      isoCode: primaryCountry.isoCode,
      currencyCode: primaryCountry.currencyCode
    },
    city: {
      id: primaryCity.id,
      slug: primaryCity.slug,
      name: primaryCity.name
    },
    ...( {
      pathname: `/compare/cities/${primaryCity.slug}-vs-${secondaryCity.slug}`
    } as any)
  });

  const preset = getCalculatorPresetForRoute("city-comparison");

  const primaryConfig = getTaxConfigByCountryId(primaryCountry.id);
  const secondaryConfig = getTaxConfigByCountryId(secondaryCountry.id);

  const primaryResult =
    primaryConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      primaryConfig
    );

  const secondaryResult =
    secondaryConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      secondaryConfig
    );

  const shareCard =
    primaryResult &&
    secondaryResult &&
    buildComparisonShareCard({
      routeType: "city-comparison",
      labelA: primaryCity.name,
      labelB: secondaryCity.name,
      currencyCodeA: primaryCountry.currencyCode,
      currencyCodeB: secondaryCountry.currencyCode,
      resultA: primaryResult,
      resultB: secondaryResult,
      amountLabel: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
      localeA: primaryCountry.locale,
      localeB: secondaryCountry.locale
    });

  const defaultScenario =
    primaryConfig && pickDefaultRefundScenarioForCountry(primaryCountry.id);
  const scenarioPayload =
    defaultScenario &&
    buildScenarioAwareSharePayload({
      scenario: defaultScenario,
      countryId: primaryCountry.id,
      currencyCode: primaryCountry.currencyCode,
      locale: primaryCountry.locale
    });

  const shareReady = buildShareReadyMetadata({
    seo,
    shareCard: shareCard || null,
    routeType: "city-comparison",
    scenarioPayload: scenarioPayload ?? null
  });

  const comparisonQuality = evaluateComparisonPageQuality({
    hasSnapshots: true,
    hasTrustBlocks: true,
    hasRelatedLinks: true,
    hasRefundInsights: true
  });

  const decision = buildSeoDecisionForRoute({
    routeType: "city-comparison",
    qualityScore: comparisonQuality.score
  });

  return {
    ...shareReady.metadata,
    robots: decision.noindex
      ? {
          index: false,
          follow: true
        }
      : shareReady.metadata.robots,
    other: {
      ...(shareReady.metadata as any).other,
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
              name: `Сравнение городов`,
              url: "https://www.example-salary-calculator.com/compare/cities"
            }
          ]
        })
      })
    }
  };
}

export default async function CityComparisonPage({
  params
}: {
  params: { primarySlug: string; secondarySlug: string };
}) {
  const [countries, cities] = await Promise.all([loadCountries(), loadCities()]);

  const primaryCity = cities.find((c) => c.slug === params.primarySlug);
  const secondaryCity = cities.find((c) => c.slug === params.secondarySlug);
  if (!primaryCity || !secondaryCity) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const primaryCountry = countries.find((c) => c.id === primaryCity.countryId);
  const secondaryCountry = countries.find((c) => c.id === secondaryCity.countryId);
  if (!primaryCountry || !secondaryCountry) return null;

  const trust = getTrustConfigForPageType("city-comparison");
  const comparisonNote = getComparisonTrustNote("city-comparison");
  const preset = getCalculatorPresetForRoute("city-comparison");

  const primaryConfig = getTaxConfigByCountryId(primaryCountry.id);
  const secondaryConfig = getTaxConfigByCountryId(secondaryCountry.id);

  const primaryResult =
    primaryConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      primaryConfig
    );

  const secondaryResult =
    secondaryConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      secondaryConfig
    );

  const shareCard =
    primaryResult &&
    secondaryResult &&
    buildComparisonShareCard({
      routeType: "city-comparison",
      labelA: primaryCity.name,
      labelB: secondaryCity.name,
      currencyCodeA: primaryCountry.currencyCode,
      currencyCodeB: secondaryCountry.currencyCode,
      resultA: primaryResult,
      resultB: secondaryResult,
      amountLabel: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
      localeA: primaryCountry.locale,
      localeB: secondaryCountry.locale
    });

  const exportFormats = shareCard ? buildExportFormatsFromCard(shareCard) : null;

  const refundComparison =
    primaryResult && secondaryResult
      ? buildRefundComparisonSummary(primaryResult, secondaryResult)
      : null;

  const related = getRelatedForCityPage({
    country: primaryCountry,
    city: primaryCity,
    professions: [],
    siblingCities: cities.filter(
      (city) => city.id !== primaryCity.id && city.countryId === primaryCountry.id
    )
  });

  logPageAuditInDev(
    `city-comparison-${primaryCity.slug}-vs-${secondaryCity.slug}`,
    {
      routeType: "city-comparison",
      trustMethodology: trust.methodology,
      explanationParagraphs: comparisonNote.paragraphs,
      relatedCount: related.length,
      locale: primaryCountry.locale,
      currencyCode: primaryCountry.currencyCode,
      countryId: primaryCountry.id,
      countries,
      refundComparison
    }
  );

  return (
    <ComparisonPageLayout
      breadcrumbsSlot={
        <Breadcrumbs
          items={[
            { label: "Главная", href: "/" },
            { label: "Калькулятор зарплаты", href: "/salary-calculator" },
            {
              label: "Сравнение городов",
              href: "/compare/cities"
            }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          eyebrow="Сравнение городов"
          title={`${primaryCity.name} vs ${secondaryCity.name}`}
          subtitle="Сравниваем два города по ключевым метрикам: gross, net, эффективная ставка и затраты работодателя. Ориентир для релокации и выбора оффера, а не точный расчёт под каждый кейс."
        />
      }
      summarySlot={
        primaryResult &&
        secondaryResult &&
        exportFormats && (
          <PrintableResultSection heading="Итог сравнения городов">
            <p className="mb-3 text-xs text-slate-300">
              Мы сравниваем, как один и тот же уровень дохода по gross выглядит по net в двух
              городах при типичных налоговых ставках и взносах. Ниже — базовый сценарий
              для указанной суммы и периода, без учёта индивидуальных льгот.
            </p>
            <ComparisonSnapshotCard
              baseline={{
                label: primaryCity.name,
                gross: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
                net: `${formatCurrency(primaryResult.net, primaryCountry.currencyCode, primaryCountry.locale)} net`,
                totalTax: formatCurrency(primaryResult.totalTax, primaryCountry.currencyCode, primaryCountry.locale),
                effectiveTaxRate: formatPercent(primaryResult.effectiveTaxRate, primaryCountry.locale)
              }}
              current={{
                label: secondaryCity.name,
                gross: `${preset.defaultAmount} ${secondaryCountry.currencyCode} gross`,
                net: `${formatCurrency(secondaryResult.net, secondaryCountry.currencyCode, secondaryCountry.locale)} net`,
                totalTax: formatCurrency(secondaryResult.totalTax, secondaryCountry.currencyCode, secondaryCountry.locale),
                effectiveTaxRate: formatPercent(secondaryResult.effectiveTaxRate, secondaryCountry.locale)
              }}
            />
            <ComparisonRefundShareBridge
              summaryText={exportFormats.plainText}
              compactText={exportFormats.compact}
              routeType="city-comparison"
              countryId={primaryCountry.id}
              currencyCode={primaryCountry.currencyCode}
              locale={primaryCountry.locale}
              baseLabel={primaryCity.name}
              defaultAmount={Number(preset.defaultAmount)}
              defaultPeriod={preset.defaultPeriod}
              defaultMode={preset.defaultMode}
              childrenAfterActions={
                <p className="mt-3 text-[11px] text-slate-400">
              Помните, что помимо налогов на результат влияют стоимость жизни, формат занятости
              и конкретные условия оффера. Используйте эту сводку как ориентир, а не как
              готовый ответ «куда переезжать». Взаимодействуя с блоком вычетов, вы видите,
              как ориентировочные вычеты по разным категориям могут менять net и годовой налог
              в основной стране сравнения.
            </p>
              }
            />
          </PrintableResultSection>
        )
      }
      explanationSlot={
        <>
          <ExplanationSection
            title={comparisonNote.title}
            paragraphs={comparisonNote.paragraphs}
          />
          <MethodologySection paragraphs={trust.methodology} />
          {trust.assumptions && trust.limitations && (
            <AssumptionsLimitationsSection
              assumptions={trust.assumptions}
              limitations={trust.limitations}
            />
          )}
          <DisclaimerSection paragraphs={trust.disclaimer} />
          <LastUpdatedSection value={trust.lastUpdated} />
        </>
      }
      relatedSlot={<RelatedPagesSection items={related} />}
    />
  );
}

