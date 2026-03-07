import type { SeoRouteType } from "@/lib/seo/decisions";
import type { SalaryResult, SalaryPeriod, CalculationDirection } from "@/lib/calculations/types";
import { getRefundCategoryLabel } from "@/lib/calculations/refund-config";

export interface ShareMetric {
  label: string;
  value: string;
}

export interface ShareCard {
  title: string;
  subtitle?: string;
  metrics: ShareMetric[];
  footer?: string;
}

export function buildSingleResultShareCard(options: {
  routeType: SeoRouteType;
  mode: CalculationDirection;
  period: SalaryPeriod;
  currencyCode: string;
  contextLabel?: string;
  result: SalaryResult;
  locale?: string;
  /** Единая строка сценария вычетов для share/print/compact (scenario-aware payload). */
  refundScenarioSummary?: string;
}): ShareCard {
  const baseTitle =
    options.routeType === "gross-to-net"
      ? "Gross → Net расчёт"
      : options.routeType === "net-to-gross"
      ? "Net → Gross расчёт"
      : "Результат расчёта зарплаты";

  const subtitle = options.contextLabel
    ? `${options.contextLabel}, период: ${options.period === "monthly" ? "месячный" : "годовой"}`
    : `Период: ${options.period === "monthly" ? "месячный" : "годовой"}`;

  const locale = options.locale ?? "de-DE";
  const formatter = new Intl.NumberFormat(locale);

  const metrics: ShareMetric[] = [
    {
      label: "Gross",
      value: `${formatter.format(Math.round(options.result.gross))} ${options.currencyCode}`
    },
    {
      label: "Net",
      value: `${formatter.format(Math.round(options.result.net))} ${options.currencyCode}`
    },
    {
      label: "Налоги и взносы",
      value: `${formatter.format(Math.round(options.result.totalTax))} ${options.currencyCode}`
    },
    {
      label: "Эффективная ставка",
      value: `${Math.round(options.result.effectiveTaxRate * 100)}%`
    },
    {
      label: "Налоги за год",
      value: `${formatter.format(Math.round(options.result.annualTaxPaid))} ${options.currencyCode}`
    }
  ];

  if (options.result.taxRefundEstimate && options.result.taxRefundEstimate.potentialRefund > 0) {
    const categoryLabel = getRefundCategoryLabel(options.result.taxRefundEstimate.categoryId);
    const refundValue = `${formatter.format(
      Math.round(options.result.taxRefundEstimate.potentialRefund)
    )} ${options.currencyCode}`;

    metrics.push({
      label: "Потенциальный налоговый возврат (оценка)",
      value: categoryLabel ? `${refundValue} · категория: ${categoryLabel}` : refundValue
    });
  }

  const baseFooter =
    options.mode === "gross-to-net"
      ? "Gross указан в оффере, Net — ориентировочная сумма «на руки»."
      : "Net — целевой доход «на руки», Gross — оценка, какой уровень нужен для его достижения.";
  const footer = options.refundScenarioSummary
    ? `${baseFooter} ${options.refundScenarioSummary}`
    : baseFooter;

  return {
    title: baseTitle,
    subtitle,
    metrics,
    footer
  };
}

export function buildComparisonShareCard(options: {
  routeType: SeoRouteType;
  labelA: string;
  labelB: string;
  currencyCodeA: string;
  currencyCodeB: string;
  resultA: SalaryResult;
  resultB: SalaryResult;
  amountLabel: string;
  localeA?: string;
  localeB?: string;
  /** Опциональное текстовое описание активного сценария вычетов для future-ready карточек. */
  refundScenarioLabel?: string;
  /** Краткая заметка про влияние вычетов на результат. */
  refundNote?: string;
}): ShareCard {
  const title =
    options.routeType === "city-comparison"
      ? `Сравнение городов: ${options.labelA} vs ${options.labelB}`
      : `Сравнение профессий: ${options.labelA} vs ${options.labelB}`;

  const subtitle = `Сценарий при ${options.amountLabel}: одинаковый gross, разные net и налоги.`;

  const localeA = options.localeA ?? "de-DE";
  const localeB = options.localeB ?? "de-DE";
  const formatterA = new Intl.NumberFormat(localeA);
  const formatterB = new Intl.NumberFormat(localeB);

  const diffNet = Math.round(options.resultB.net - options.resultA.net);

  const metrics: ShareMetric[] = [
    {
      label: `Net в ${options.labelA}`,
      value: `${formatterA.format(Math.round(options.resultA.net))} ${options.currencyCodeA}`
    },
    {
      label: `Net в ${options.labelB}`,
      value: `${formatterB.format(Math.round(options.resultB.net))} ${options.currencyCodeB}`
    },
    {
      label: "Разница по net",
      value: `${diffNet > 0 ? "+" : ""}${formatterB.format(Math.abs(diffNet))} ${
        diffNet >= 0 ? options.currencyCodeB : options.currencyCodeA
      }`
    },
    {
      label: "Эффективная ставка A/B",
      value: `${Math.round(options.resultA.effectiveTaxRate * 100)}% / ${Math.round(
        options.resultB.effectiveTaxRate * 100
      )}%`
    }
  ];

  const footer =
    options.routeType === "city-comparison"
      ? "Сравнение носит ориентировочный характер: реальные цифры зависят от статуса, льгот и структуры компенсации."
      : "Сравнение профессий учитывает только базовый доход: бонусы, опционы и специфические льготы не входят в модель.";

  const enhancedFooter =
    options.refundScenarioLabel || options.refundNote
      ? [
          footer,
          options.refundScenarioLabel
            ? `Активный сценарий вычетов: ${options.refundScenarioLabel}.`
            : null,
          options.refundNote ?? null
        ]
          .filter(Boolean)
          .join(" ")
      : footer;

  return {
    title,
    subtitle,
    metrics,
    footer: enhancedFooter
  };
}

