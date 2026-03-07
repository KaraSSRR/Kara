"use client";

import { trackCalculatorEvent } from "@/lib/analytics/events";
import type { SeoRouteType } from "@/lib/seo/decisions";
import type { SalaryResult, SalaryPeriod, CalculationDirection } from "@/lib/calculations/types";
import type { ScenarioAwareSharePayload } from "@/lib/calculations/refund-scenarios";
import { buildSingleResultExport } from "@/lib/share/copy";
import { PrintableResultSection } from "@/components/results/PrintableResultSection";

interface SingleResultShareActionsProps {
  routeType: SeoRouteType;
  mode: CalculationDirection;
  period: SalaryPeriod;
  currencyCode: string;
  locale?: string;
  contextLabel?: string;
  result: SalaryResult;
  /** Единообразный payload для share/print/compact (label, deductible, estimate note). */
  scenarioPayload?: ScenarioAwareSharePayload | null;
}

export function SingleResultShareActions({
  routeType,
  mode,
  period,
  currencyCode,
  locale,
  contextLabel,
  result,
  scenarioPayload
}: SingleResultShareActionsProps) {
  const { formats } = buildSingleResultExport({
    routeType,
    mode,
    period,
    currencyCode,
    locale,
    contextLabel,
    result,
    scenarioPayload
  });

  const handleCopyPlain = async () => {
    if (typeof window === "undefined") return;
    await navigator.clipboard.writeText(formats.plainText);
    trackCalculatorEvent({
      name: "result_copied",
      routeType,
      mode,
      period,
      amount: result.gross
    });
  };

  const handleCopyCompact = async () => {
    if (typeof window === "undefined") return;
    await navigator.clipboard.writeText(formats.compact);
    trackCalculatorEvent({
      name: "result_copied",
      routeType,
      mode,
      period,
      amount: result.gross
    });
  };

  const handlePrint = () => {
    if (typeof window === "undefined") return;
    window.print();
  };

  return (
    <PrintableResultSection heading="Поделиться результатом">
      <div className="space-y-2 text-[11px] text-slate-200">
        <p className="text-slate-400">
          Вы можете скопировать краткую сводку или распечатать результат, чтобы отправить его
          по почте или сохранить в заметках.
        </p>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={handleCopyPlain}
            className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-200 hover:border-slate-500"
          >
            Скопировать подробную сводку
          </button>
          <button
            type="button"
            onClick={handleCopyCompact}
            className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-200 hover:border-slate-500"
          >
            Скопировать короткий итог
          </button>
          <button
            type="button"
            onClick={handlePrint}
            className="rounded-full border border-slate-800 bg-transparent px-3 py-1 text-slate-400 hover:border-slate-600 hover:text-slate-100"
          >
            Распечатать результат
          </button>
        </div>
      </div>
    </PrintableResultSection>
  );
}

