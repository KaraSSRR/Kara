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
    pageType: "gross-to-net",
    ...( { pathname: "/gross-to-net" } as any)
  });

  const faqTemplates = await getFaqTemplatesForContext({ pageType: "gross-to-net" });
  const faqItems = renderFaqTemplates(faqTemplates, {
    grossLabel: "gross",
    netLabel: "net",
    year: new Date().getFullYear()
  });

  const preset = getCalculatorPresetForRoute("gross-to-net");
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
      routeType: "gross-to-net",
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
    routeType: "gross-to-net",
    scenarioSummary: scenarioSummary ?? null
  });

  const decision = buildSeoDecisionForRoute({ routeType: "gross-to-net" });
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
          pageType: "gross-to-net",
          breadcrumbs: [
            { name: "Главная", url: "https://www.example-salary-calculator.com/" },
            {
              name: "Gross → Net калькулятор",
              url: "https://www.example-salary-calculator.com/gross-to-net"
            }
          ],
          faqItems,
          includeWebApp: true,
          webAppOptions: {
            name: "Salary Calculator",
            url: "https://www.example-salary-calculator.com/gross-to-net",
            description:
              "Gross to Net salary calculator with localized tax logic for multiple countries, cities, and professions."
          }
        })
      })
    }
  };
}

export default async function GrossToNetPage() {
  const [countries, cities, professions, faqTemplates] = await Promise.all([
    loadCountries(),
    loadCities(),
    loadProfessions(),
    getFaqTemplatesForContext({ pageType: "gross-to-net" })
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

  const trust = getTrustConfigForPageType("gross-to-net");
  const preset = getCalculatorPresetForRoute("gross-to-net");

  logPageAuditInDev("gross-to-net", {
    routeType: "gross-to-net",
    trustMethodology: trust.methodology,
    explanationParagraphs: [
      "Этот режим фокусируется на переводе офферов и вилок из gross в net, чтобы вы видели реалистичный take-home доход.",
      "Вы можете сохранить результат, поделиться ссылкой и затем перейти к более детальным городским или профессиональным страницам."
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
            { label: "Gross → Net калькулятор" }
          ]}
        />
      }
      heroSlot={
        <CalculatorHero
          title="Gross → Net калькулятор"
          subtitle="Узнайте, сколько останется «на руки» от указанной gross-зарплаты, с учётом локальных налогов и взносов."
          eyebrow="Режим Gross → Net"
        />
      }
      calculatorSlot={
        <SalaryCalculator
          routeType="gross-to-net"
          defaultMode={preset.defaultMode}
          helperText={preset.helperText}
          comparisonHint={preset.comparisonHint}
        />
      }
      explanationSlot={
        <>
          <ExplanationSection
            paragraphs={[
              "Этот режим фокусируется на переводе офферов и вилок из gross в net, чтобы вы видели реалистичный take-home доход.",
              "Вы можете сохранить результат, поделиться ссылкой и затем перейти к более детальным городским или профессиональным страницам."
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

