import type { SeoRouteType } from "@/lib/seo/decisions";
import type { SalaryResult, SalaryPeriod, CalculationDirection } from "@/lib/calculations/types";
import type { ScenarioAwareSharePayload } from "@/lib/calculations/refund-scenarios";
import { formatScenarioAwareShareLine } from "@/lib/calculations/refund-scenarios";
import { buildSingleResultShareCard, buildComparisonShareCard } from "./cards";
import { buildExportFormatsFromCard } from "./format";

export interface EmailCopyPayload {
  subject: string;
  body: string;
}

export function buildSingleResultExport(options: {
  routeType: SeoRouteType;
  mode: CalculationDirection;
  period: SalaryPeriod;
  currencyCode: string;
  contextLabel?: string;
  result: SalaryResult;
  locale?: string;
  /** Опциональная текстовая сводка по активному сценарию вычетов (legacy). */
  refundScenarioSummary?: string;
  /** Единообразный payload для share/print/compact: label, deductible, estimate note. */
  scenarioPayload?: ScenarioAwareSharePayload | null;
}): {
  formats: ReturnType<typeof buildExportFormatsFromCard>;
  email: EmailCopyPayload;
} {
  const scenarioLine =
    options.scenarioPayload &&
    (options.scenarioPayload.scenarioLabel ||
      options.scenarioPayload.presetLabel ||
      options.scenarioPayload.categoryLabel)
      ? formatScenarioAwareShareLine(options.scenarioPayload)
      : options.refundScenarioSummary ?? null;

  const card = buildSingleResultShareCard({
    ...options,
    scenarioPayload: options.scenarioPayload,
    refundScenarioSummary: scenarioLine ?? undefined
  });
  const formats = buildExportFormatsFromCard(card);
  const subject = `${card.title} — ${card.subtitle ?? ""}`.trim();

  const bodyLines = [card.title];
  if (card.subtitle) bodyLines.push(card.subtitle, "");
  bodyLines.push(...card.metrics.map((m) => `${m.label}: ${m.value}`));
  if (card.footer) {
    bodyLines.push("", card.footer);
  }
  if (scenarioLine) {
    bodyLines.push("", scenarioLine);
  }

  return {
    formats,
    email: {
      subject,
      body: bodyLines.join("\n")
    }
  };
}

export function buildComparisonExport(options: {
  routeType: SeoRouteType;
  labelA: string;
  labelB: string;
  currencyCodeA: string;
  currencyCodeB: string;
  resultA: SalaryResult;
  resultB: SalaryResult;
  amountLabel: string;
  /** Опциональная текстовая сводка по сценарию вычетов (если пользователь её выбрал). */
  refundScenarioSummary?: string;
  /** Единообразная подпись сценария вычетов для footer карточки. */
  scenarioPayload?: ScenarioAwareSharePayload | null;
}): {
  formats: ReturnType<typeof buildExportFormatsFromCard>;
  email: EmailCopyPayload;
} {
  const scenarioLine =
    options.scenarioPayload &&
    (options.scenarioPayload.scenarioLabel ||
      options.scenarioPayload.presetLabel ||
      options.scenarioPayload.categoryLabel)
      ? formatScenarioAwareShareLine(options.scenarioPayload)
      : options.refundScenarioSummary ?? null;

  const card = buildComparisonShareCard({
    ...options,
    refundScenarioLabel: options.scenarioPayload?.scenarioLabel ?? options.scenarioPayload?.presetLabel ?? options.scenarioPayload?.categoryLabel ?? undefined,
    refundNote: options.scenarioPayload?.countryAwareNote ?? undefined
  });
  const formats = buildExportFormatsFromCard(card);
  const subject = card.title;

  const bodyLines = [card.title];
  if (card.subtitle) bodyLines.push(card.subtitle, "");
  bodyLines.push(...card.metrics.map((m) => `${m.label}: ${m.value}`));
  if (card.footer) {
    bodyLines.push("", card.footer);
  }
  if (scenarioLine) {
    bodyLines.push("", scenarioLine);
  }

  return {
    formats,
    email: {
      subject,
      body: bodyLines.join("\n")
    }
  };
}

