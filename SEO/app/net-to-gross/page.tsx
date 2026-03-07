import type { Metadata } from "next";
import { buildSeoMetadata } from "@/lib/seo/metadata";
import { buildShareReadyMetadata } from "@/lib/seo/share-metadata";
import { calculate } from "@/lib/calculations";
import { getTaxConfigByCountryId } from "@/lib/calculations/config";
import { buildSingleResultShareCard } from "@/lib/share/cards";
import {
  buildScenarioAwareSharePayload,
  pickDefaultRefundScenarioForCountry,
  scenarioSummaryForMetadata
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
    pageType: "net-to-gross",
    ...( { pathname: "/net-to-gross" } as any)
  });

  const faqTemplates = await getFaqTemplatesForContext({ pageType: "net-to-gross" });
  const faqItems = renderFaqTemplates(faqTemplates, {
    grossLabel: "gross",
    netLabel: "net",
    year: new Date().getFullYear()
  });

  const preset = getCalculatorPresetForRoute("net-to-gross");
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
      routeType: "net-to-gross",
      mode: preset.defaultMode,
      period: preset.defaultPeriod,
      currencyCode: "EUR",
      contextLabel: undefined,
      result: defaultResult
    });

  const defaultScenario = pickDefaultRefundScenarioForCountry("de");
  const scenarioSummary =
    defaultScenario &&
    scenarioSummaryForMetadata(
      buildScenarioAwareSharePayload({
        scenario: defaultScenario,
        countryId: "de",
        currencyCode: "EUR",
        locale: "de-DE"
      })
    );

  const shareReady = buildShareReadyMetadata({
    seo,
    shareCard: shareCard || null,
    routeType: "net-to-gross",
    scenarioSummary: scenarioSummary ?? null
  });

  const decision = buildSeoDecisionForRoute({ routeType: "net-to-gross" });
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
          pageType: "net-to-gross",
          breadcrumbs: [
            { name: "Главная", url: "https://www.example-salary-calculator.com/" },
            {
              name: "Net → Gross калькулятор",
              url: "https://www.example-salary-calculator.com/net-to-gross"
            }
          ],
          faqItems,
          includeWebApp: true,
          webAppOptions: {
            name: "Salary Calculator",
            url: "https://www.example-salary-calculator.com/net-to-gross",
            description:
              "Net to Gross salary calculator to estimate required gross income for target take-home pay."
          }
        })
      })
    }
  };
}

export default async function NetToGrossPage() {
  const [countries, cities, professions, faqTemplates] = await Promise.all([
    loadCountries(),
    loadCities(),
    loadProfessions(),
    getFaqTemplatesForContext({ pageType: "net-to-gross" })
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

  const trust = getTrustConfigForPageType("net-to-gross");
  const preset = getCalculatorPresetForRoute("net-to-gross");

  logPageAuditInDev("net-to-gross", {
    routeType: "net-to-gross",
    trustMethodology: trust.methodology,
    explanationParagraphs: [
      "Этот режим помогает планировать целевой доход: вы задаёте желаемую сумму net, а калькулятор оценивает необходимый gross с учётом налогов и взносов.",
      "Такой сценарий полезен при переговорах по зарплате, планировании релокации и выборе между разными форматами занятости."
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
            { label: "Net → Gross калькулятор" }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          title="Net → Gross калькулятор"
          subtitle="Оцените, какой gross-доход нужен, чтобы получить целевой net-результат «на руки»."
          eyebrow="Режим Net → Gross"
        />
      }
      calculatorSlot={
        <SalaryCalculator
          routeType="net-to-gross"
          defaultMode={preset.defaultMode}
          helperText={preset.helperText}
          comparisonHint={preset.comparisonHint}
        />
      }
      explanationSlot={
        <>
          <ExplanationSection
            paragraphs={[
              "Этот режим помогает планировать целевой доход: вы задаёте желаемую сумму net, а калькулятор оценивает необходимый gross с учётом налогов и взносов.",
              "Такой сценарий полезен при переговорах по зарплате, планировании релокации и выборе между разными форматами занятости."
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

