"use client";

import { useState } from "react";
import type { SeoRouteType } from "@/lib/seo/decisions";
import type { CalculationDirection, SalaryPeriod } from "@/lib/calculations/types";
import { RefundComparisonSection } from "./RefundComparisonSection";
import { ComparisonShareActions } from "./ComparisonShareActions";

export interface ComparisonRefundShareBridgeProps {
  summaryText: string;
  /** Опционально: для кнопки «Копировать кратко» с A/B. */
  compactText?: string;
  routeType: SeoRouteType;
  countryId: string;
  currencyCode: string;
  locale: string;
  baseLabel: string;
  defaultAmount: number;
  defaultPeriod: SalaryPeriod;
  defaultMode: CalculationDirection;
  childrenAfterActions?: React.ReactNode;
}

export function ComparisonRefundShareBridge({
  summaryText,
  compactText,
  routeType,
  countryId,
  currencyCode,
  locale,
  baseLabel,
  defaultAmount,
  defaultPeriod,
  defaultMode,
  childrenAfterActions
}: ComparisonRefundShareBridgeProps) {
  const [scenarioShareSummary, setScenarioShareSummary] = useState("");

  return (
    <>
      <RefundComparisonSection
        countryId={countryId}
        currencyCode={currencyCode}
        locale={locale}
        baseLabel={baseLabel}
        defaultAmount={defaultAmount}
        defaultPeriod={defaultPeriod}
        defaultMode={defaultMode}
        onScenarioShareContextChange={setScenarioShareSummary}
      />
      <ComparisonShareActions
        routeType={routeType}
        summaryText={summaryText}
        compactText={compactText}
        countryId={countryId}
        currencyCode={currencyCode}
        locale={locale}
        scenarioShareSummary={scenarioShareSummary}
      />
      {childrenAfterActions}
    </>
  );
}
