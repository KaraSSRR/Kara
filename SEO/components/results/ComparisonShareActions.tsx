"use client";

import { useSearchParams } from "next/navigation";
import { trackCalculatorEvent } from "@/lib/analytics/events";
import type { SeoRouteType } from "@/lib/seo/decisions";
import { getRefundCountryNotes, isRefundEnabledForCountry } from "@/lib/calculations/refund-config";
import { loadSavedRefundScenarios, buildComparisonRefundScenarioSummary } from "@/lib/calculations/refund-scenarios";
import { getComparisonCompactWithScenarioSummary } from "@/lib/share/format";

interface ComparisonShareActionsProps {
  routeType: SeoRouteType;
  summaryText: string;
  countryId?: string;
  currencyCode?: string;
  locale?: string;
  /** Строка A/B и закреплённых (из RefundComparisonSection через client bridge). */
  scenarioShareSummary?: string;
  /** Краткий формат для кнопки «Копировать кратко». */
  compactText?: string;
}

export function ComparisonShareActions({
  routeType,
  summaryText,
  countryId,
  currencyCode = "",
  locale = "ru",
  scenarioShareSummary,
  compactText
}: ComparisonShareActionsProps) {
  const searchParams = useSearchParams();

  const handleCopySummary = async () => {
    if (typeof window === "undefined") return;

    let textToCopy = summaryText;

    if (countryId && isRefundEnabledForCountry(countryId)) {
      const mode = searchParams.get("refundMode");
      const catA = searchParams.get("refundCatA");
      const catB = searchParams.get("refundCatB");
      const deductible = searchParams.get("refundDeductible");

      if (mode && (catA || catB || deductible)) {
        const refundSummary = buildComparisonRefundScenarioSummary({
          countryId,
          currencyCode,
          locale,
          mode: mode ?? "",
          catA,
          catB,
          deductible,
          savedScenarios: loadSavedRefundScenarios()
        });
        const note = getRefundCountryNotes(countryId).genericDisclaimer;
        const withNote = note ? `${refundSummary} ${note}` : refundSummary;
        textToCopy = `${summaryText}\n\n${withNote}`;
      }
    }
    if (scenarioShareSummary?.trim()) {
      textToCopy = `${scenarioShareSummary.trim()}\n\n${textToCopy}`;
    }

    await navigator.clipboard.writeText(textToCopy);
    trackCalculatorEvent({
      name: "comparison_snapshot_copied",
      routeType,
      mode: "gross-to-net",
      period: "monthly",
      amount: null
    });
  };

  const handleCopyCompact = async () => {
    if (typeof window === "undefined" || !compactText) return;
    const text = getComparisonCompactWithScenarioSummary(compactText, scenarioShareSummary);
    await navigator.clipboard.writeText(text);
    trackCalculatorEvent({
      name: "comparison_snapshot_copied",
      routeType,
      mode: "gross-to-net",
      period: "monthly",
      amount: null
    });
  };

  const handlePrint = () => {
    if (typeof window === "undefined") return;
    window.print();
    trackCalculatorEvent({
      name: "comparison_print_used",
      routeType,
      mode: "gross-to-net",
      period: "monthly",
      amount: null
    });
  };

  return (
    <div className="mt-3 flex flex-wrap gap-2 text-[11px]">
      <button
        type="button"
        onClick={handleCopySummary}
        className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-200 hover:border-slate-500"
      >
        Скопировать сводку сравнения
      </button>
      {compactText && (
        <button
          type="button"
          onClick={handleCopyCompact}
          className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-300 hover:border-slate-500"
        >
          Копировать кратко
        </button>
      )}
      <button
        type="button"
        onClick={handlePrint}
        className="rounded-full border border-slate-800 bg-transparent px-3 py-1 text-slate-400 hover:border-slate-600 hover:text-slate-100"
      >
        Распечатать сравнение
      </button>
    </div>
  );
}

