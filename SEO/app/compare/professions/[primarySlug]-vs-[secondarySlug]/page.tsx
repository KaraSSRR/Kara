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
import { loadCountries, loadProfessions } from "@/lib/data/loaders";
import { getTrustConfigForPageType, getComparisonTrustNote } from "@/lib/data/trust";
import { getRelatedForProfessionPage } from "@/lib/data/related";
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
  const [countries, professions] = await Promise.all([
    loadCountries(),
    loadProfessions()
  ]);

  const primaryCountry = countries[0];
  if (!primaryCountry) return [];

  const relevant = professions.filter(
    (p) => (p.searchPriorityByCountry[primaryCountry.id] ?? 0) >= 0.6
  );

  const params: { primarySlug: string; secondarySlug: string }[] = [];

  for (let i = 0; i < relevant.length; i++) {
    for (let j = i + 1; j < relevant.length; j++) {
      params.push({
        primarySlug: relevant[i].slug,
        secondarySlug: relevant[j].slug
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
  const [countries, professions] = await Promise.all([
    loadCountries(),
    loadProfessions()
  ]);

  const primaryCountry = countries[0];
  if (!primaryCountry) return {};

  const primaryProfession = professions.find((p) => p.slug === params.primarySlug);
  const secondaryProfession = professions.find((p) => p.slug === params.secondarySlug);
  if (!primaryProfession || !secondaryProfession) return {};

  const seo = buildSeoMetadata({
    pageType: "profession",
    country: {
      id: primaryCountry.id,
      slug: primaryCountry.slug,
      name: primaryCountry.name,
      isoCode: primaryCountry.isoCode,
      currencyCode: primaryCountry.currencyCode
    },
    profession: {
      id: primaryProfession.id,
      slug: primaryProfession.slug,
      name: primaryProfession.name,
      category: primaryProfession.category
    },
    ...( {
      pathname: `/compare/professions/${primaryProfession.slug}-vs-${secondaryProfession.slug}`
    } as any)
  });

  const preset = getCalculatorPresetForRoute("profession-comparison");
  const taxConfig = getTaxConfigByCountryId(primaryCountry.id);

  const primaryResult =
    taxConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      taxConfig
    );

  const secondaryResult =
    taxConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      taxConfig
    );

  const shareCard =
    primaryResult &&
    secondaryResult &&
    buildComparisonShareCard({
      routeType: "profession-comparison",
      labelA: primaryProfession.name,
      labelB: secondaryProfession.name,
      currencyCodeA: primaryCountry.currencyCode,
      currencyCodeB: primaryCountry.currencyCode,
      resultA: primaryResult,
      resultB: secondaryResult,
      amountLabel: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
      localeA: primaryCountry.locale,
      localeB: primaryCountry.locale
    });

  const defaultScenario =
    taxConfig && pickDefaultRefundScenarioForCountry(primaryCountry.id);
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
    routeType: "profession-comparison",
    scenarioPayload: scenarioPayload ?? null
  });

  const comparisonQuality = evaluateComparisonPageQuality({
    hasSnapshots: true,
    hasTrustBlocks: true,
    hasRelatedLinks: true,
    hasRefundInsights: true
  });

  const decision = buildSeoDecisionForRoute({
    routeType: "profession-comparison",
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
          pageType: "profession",
          breadcrumbs: [
            { name: "Главная", url: "https://www.example-salary-calculator.com/" },
            {
              name: "Калькулятор зарплаты",
              url: "https://www.example-salary-calculator.com/salary-calculator"
            },
            {
              name: "Сравнение профессий",
              url: "https://www.example-salary-calculator.com/compare/professions"
            }
          ]
        })
      })
    }
  };
}

export default async function ProfessionComparisonPage({
  params
}: {
  params: { primarySlug: string; secondarySlug: string };
}) {
  const [countries, professions] = await Promise.all([
    loadCountries(),
    loadProfessions()
  ]);

  const primaryCountry = countries[0];
  if (!primaryCountry) return null;

  const primaryProfession = professions.find((p) => p.slug === params.primarySlug);
  const secondaryProfession = professions.find((p) => p.slug === params.secondarySlug);
  if (!primaryProfession || !secondaryProfession) {
    const { notFound } = await import("next/navigation");
    notFound();
  }

  const trust = getTrustConfigForPageType("profession-comparison");
  const comparisonNote = getComparisonTrustNote("profession-comparison");
  const preset = getCalculatorPresetForRoute("profession-comparison");

  const taxConfig = getTaxConfigByCountryId(primaryCountry.id);

  const primaryResult =
    taxConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      taxConfig
    );

  const secondaryResult =
    taxConfig &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      taxConfig
    );

  const shareCard =
    primaryResult &&
    secondaryResult &&
    buildComparisonShareCard({
      routeType: "profession-comparison",
      labelA: primaryProfession.name,
      labelB: secondaryProfession.name,
      currencyCodeA: primaryCountry.currencyCode,
      currencyCodeB: primaryCountry.currencyCode,
      resultA: primaryResult,
      resultB: secondaryResult,
      amountLabel: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
      localeA: primaryCountry.locale,
      localeB: primaryCountry.locale
    });

  const exportFormats = shareCard ? buildExportFormatsFromCard(shareCard) : null;

  const refundComparison =
    primaryResult && secondaryResult
      ? buildRefundComparisonSummary(primaryResult, secondaryResult)
      : null;

  const related = getRelatedForProfessionPage({
    country: primaryCountry,
    profession: primaryProfession,
    cities: [],
    relatedProfessions: professions.filter(
      (p) => p.slug === secondaryProfession.slug
    )
  });

  logPageAuditInDev(
    `profession-comparison-${primaryProfession.slug}-vs-${secondaryProfession.slug}`,
    {
      routeType: "profession-comparison",
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
              label: "Сравнение профессий",
              href: "/compare/professions"
            }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          eyebrow="Сравнение профессий"
          title={`${primaryProfession.name} vs ${secondaryProfession.name}`}
          subtitle="Сопоставляем две профессии по ключевым метрикам и контексту. Инструмент для осознанного выбора, а не готовый ответ — реальные вилки зависят от компании, уровня и локации."
        />
      }
      summarySlot={
        primaryResult &&
        secondaryResult &&
        exportFormats && (
          <PrintableResultSection heading="Итог сравнения профессий">
            <p className="mb-3 text-xs text-slate-300">
              Здесь мы сопоставляем, как базовый уровень дохода по gross превращается в net
              для двух профессий при одинаковой сумме и периоде. Это упрощённая модель,
              которая помогает наметить порядок цифр, но не заменяет детальный разбор оффера.
            </p>
            <ComparisonSnapshotCard
              baseline={{
                label: primaryProfession.name,
                gross: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
                net: `${formatCurrency(primaryResult.net, primaryCountry.currencyCode, primaryCountry.locale)} net`,
                totalTax: formatCurrency(primaryResult.totalTax, primaryCountry.currencyCode, primaryCountry.locale),
                effectiveTaxRate: formatPercent(primaryResult.effectiveTaxRate, primaryCountry.locale)
              }}
              current={{
                label: secondaryProfession.name,
                gross: `${preset.defaultAmount} ${primaryCountry.currencyCode} gross`,
                net: `${formatCurrency(secondaryResult.net, primaryCountry.currencyCode, primaryCountry.locale)} net`,
                totalTax: formatCurrency(secondaryResult.totalTax, primaryCountry.currencyCode, primaryCountry.locale),
                effectiveTaxRate: formatPercent(secondaryResult.effectiveTaxRate, primaryCountry.locale)
              }}
            />
            <ComparisonRefundShareBridge
              summaryText={exportFormats.plainText}
              compactText={exportFormats.compact}
              routeType="profession-comparison"
              countryId={primaryCountry.id}
              currencyCode={primaryCountry.currencyCode}
              locale={primaryCountry.locale}
              baseLabel={primaryProfession.name}
              defaultAmount={Number(preset.defaultAmount)}
              defaultPeriod={preset.defaultPeriod}
              defaultMode={preset.defaultMode}
              childrenAfterActions={
                <p className="mt-3 text-[11px] text-slate-400">
                  При интерпретации результатов учитывайте уровень, стек, рынок и формат занятости:
                  в реальной жизни именно они часто сильнее всего влияют на вилку компенсации. Блок
                  вычетов помогает увидеть, как ориентировочные налоговые вычеты по разным категориям
                  могут менять net и годовой налог для основной страны сравнения.
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

