import type { ShareCard } from "./cards";

export interface ExportFormats {
  plainText: string;
  printableText: string;
  compact: string;
}

export function buildExportFormatsFromCard(card: ShareCard): ExportFormats {
  const metricsLines = card.metrics.map((m) => `${m.label}: ${m.value}`);

  const plainTextParts = [card.title];
  if (card.subtitle) plainTextParts.push(card.subtitle);
  plainTextParts.push(...metricsLines);
  if (card.footer) plainTextParts.push(card.footer);

  const plainText = plainTextParts.join("\n");

  const printableParts = [card.title, "", ...(card.subtitle ? [card.subtitle, ""] : []), ...metricsLines];
  if (card.footer) printableParts.push("", card.footer);
  const printableText = printableParts.join("\n");

  const refundMetric = card.metrics.find((m) =>
    m.label.toLowerCase().includes("налоговый возврат")
  );
  const netMetric =
    card.metrics.find((m) => m.label.toLowerCase().startsWith("net")) ?? card.metrics[1];

  const compactMetrics =
    refundMetric && netMetric
      ? [netMetric, refundMetric]
      : card.metrics.slice(0, 2);

  const compact = `${card.title} — ${compactMetrics
    .map((m) => `${m.label.toLowerCase()}: ${m.value}`)
    .join(", ")}`;

  return {
    plainText,
    printableText,
    compact
  };
}

/** Клиентский compact с опциональной строкой A/B (для comparison copy). */
export function getComparisonCompactWithScenarioSummary(
  compactText: string,
  scenarioShareSummary?: string | null
): string {
  if (!scenarioShareSummary?.trim()) return compactText;
  return `${scenarioShareSummary.trim()}\n${compactText}`;
}

