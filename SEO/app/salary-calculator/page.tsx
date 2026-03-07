import type { Metadata } from "next";
import { buildSeoMetadata } from "@/lib/seo/metadata";
import { buildShareReadyMetadata } from "@/lib/seo/share-metadata";
import { calculate } from "@/lib/calculations";
import { getTaxConfigByCountryId } from "@/lib/calculations/config";
import { buildSingleResultShareCard } from "@/lib/share/cards";
import {
  buildScenarioAwareSharePayload,
  pickDefaultRefundScenarioForCountry
} from "@/lib/calculations/refund-scenarios";
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
import { AssumptionsLimitationsSection } from "@/components/sections/AssumptionsLimitationsSection";
import { LastUpdatedSection } from "@/components/sections/LastUpdatedSection";
import { loadCountries, loadCities, loadProfessions } from "@/lib/data/loaders";
import { getFaqTemplatesForContext } from "@/lib/data/selectors";
import { getRelatedForGenericCalculator } from "@/lib/data/related";
import { renderFaqTemplates } from "@/lib/content/compose";
import { getTrustConfigForPageType } from "@/lib/data/trust";
import { getCalculatorPresetForRoute } from "@/lib/data/presets";
import { buildSeoDecisionForRoute } from "@/lib/seo/decisions";
import { logPageAuditInDev } from "@/lib/seo/page-audit";

export async function generateMetadata(): Promise<Metadata> {
  const seo = buildSeoMetadata({
    pageType: "generic-calculator",
    ...( { pathname: "/salary-calculator" } as any)
  });

  const faqTemplates = await getFaqTemplatesForContext({ pageType: "generic-calculator" });
  const faqItems = renderFaqTemplates(faqTemplates, {
    grossLabel: "gross",
    netLabel: "net",
    year: new Date().getFullYear()
  });

  const preset = getCalculatorPresetForRoute("generic-calculator");
  const config = getTaxConfigByCountryId("de");

  const defaultResult =
    config &&
    calculate(
      {
        amount: Number(preset.defaultAmount),
        period: preset.defaultPeriod,
        direction: preset.defaultMode
      },
      config
    );

  const shareCard =
    defaultResult &&
    buildSingleResultShareCard({
      routeType: "generic-calculator",
      mode: preset.defaultMode,
      period: preset.defaultPeriod,
      currencyCode: "EUR",
      contextLabel: undefined,
      result: defaultResult
    });

  const defaultScenario = pickDefaultRefundScenarioForCountry("de");
  const scenarioPayload =
    defaultScenario &&
    buildScenarioAwareSharePayload({
      scenario: defaultScenario,
      countryId: "de",
      currencyCode: "EUR",
      locale: "de-DE"
    });

  const shareReady = buildShareReadyMetadata({
    seo,
    shareCard: shareCard || null,
    routeType: "generic-calculator",
    scenarioPayload: scenarioPayload ?? null
  });

  const decision = buildSeoDecisionForRoute({ routeType: "generic-calculator" });

  const metadata = shareReady.metadata;

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
          pageType: "generic-calculator",
          breadcrumbs: [
            { name: "Главная", url: "https://www.example-salary-calculator.com/" },
            {
              name: "Калькулятор зарплаты",
              url: "https://www.example-salary-calculator.com/salary-calculator"
            }
          ],
          faqItems,
          includeWebApp: true,
          webAppOptions: {
            name: "Salary Calculator",
            url: "https://www.example-salary-calculator.com/salary-calculator",
            description:
              "Modern salary calculator for gross to net and net to gross salary comparisons across countries, cities, and professions."
          }
        })
      })
    }
  };
}

export default async function SalaryCalculatorPage() {
  const [countries, cities, professions, faqTemplates] = await Promise.all([
    loadCountries(),
    loadCities(),
    loadProfessions(),
    getFaqTemplatesForContext({ pageType: "generic-calculator" })
  ]);

  const primaryCountry = countries[0] ?? null;
  const topCities = [...cities].sort(
    (a, b) => b.searchPriority - a.searchPriority
  );
  const topProfessions = [...professions].sort((a, b) => {
    const pa = primaryCountry ? a.searchPriorityByCountry[primaryCountry.id] ?? 0 : 0;
    const pb = primaryCountry ? b.searchPriorityByCountry[primaryCountry.id] ?? 0 : 0;
    return pb - pa;
  });

  const related = getRelatedForGenericCalculator({
    country: primaryCountry,
    topCities,
    topProfessions
  });

  const faqItems = renderFaqTemplates(faqTemplates, {
    grossLabel: "gross",
    netLabel: "net",
    year: new Date().getFullYear()
  });

  const trust = getTrustConfigForPageType("generic-calculator");
  const preset = getCalculatorPresetForRoute("generic-calculator");

  logPageAuditInDev("salary-calculator", {
    routeType: "generic-calculator",
    trustMethodology: trust.methodology,
    explanationParagraphs: [
      "Этот калькулятор станет базой для всех сценариев: от сравнения офферов до планирования переезда. Далее вы сможете перейти на страницы конкретных городов и профессий.",
      "Мы избегаем тонких и дублирующих страниц: для каждого города и профессии будет отдельный шаблон с собственными пояснениями, если у нас достаточно данных."
    ],
    relatedCount: related.length,
    locale: primaryCountry?.locale ?? null,
    currencyCode: primaryCountry?.currencyCode ?? null
  });

  return (
    <CalculatorPageLayout
      breadcrumbsSlot={
        <Breadcrumbs
          items={[
            { label: "Главная", href: "/" },
            { label: "Калькулятор зарплаты" }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          title="Калькулятор зарплаты: gross ↔ net"
          subtitle="Быстро посчитайте, сколько останется «на руки» от указанной в оффере суммы, и сравните варианты между собой."
          eyebrow="Глобальный калькулятор"
        />
      }
      calculatorSlot={
        <SalaryCalculator
          routeType="generic-calculator"
          defaultMode={preset.defaultMode}
          helperText={preset.helperText}
          comparisonHint={preset.comparisonHint}
        />
      }
      explanationSlot={
        <>
          <ExplanationSection
            paragraphs={[
              "Этот калькулятор станет базой для всех сценариев: от сравнения офферов до планирования переезда. Далее вы сможете перейти на страницы конкретных городов и профессий.",
              "Мы избегаем тонких и дублирующих страниц: для каждого города и профессии будет отдельный шаблон с собственными пояснениями, если у нас достаточно данных."
            ]}
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
      faqSlot={<FaqSection items={faqItems} />}
      relatedSlot={<RelatedPagesSection items={related} />}
    />
  );
}

