import type { Metadata } from "next";
import type { SeoMetadataResult } from "./types";
import type { ShareCard } from "@/lib/share/cards";
import type { ExportFormats } from "@/lib/share/format";
import type { ScenarioAwareSharePayload } from "@/lib/calculations/refund-scenarios";
import { buildScenarioMetadataNote } from "@/lib/calculations/refund-scenarios";
import { buildOgMetaFromShareCard } from "./share";
import { buildExportFormatsFromCard } from "@/lib/share/format";

export interface ShareReadyMetadataResult {
  metadata: Metadata;
  canonicalUrl: string;
  shareCard?: ShareCard;
  shareExportFormats?: ExportFormats;
}

/** Safe policy: когда scenario-aware текст можно добавлять в preview/metadata. */
function shouldUseScenarioSummaryInMetadata(routeType?: string): boolean {
  if (!routeType) return false;
  // Разрешаем только для стабильных канонических маршрутов без user-local состояния.
  return (
    routeType === "generic-calculator" ||
    routeType === "gross-to-net" ||
    routeType === "net-to-gross" ||
    routeType === "city-comparison" ||
    routeType === "profession-comparison"
  );
}

export function buildShareReadyMetadata(options: {
  seo: SeoMetadataResult;
  shareCard?: ShareCard | null;
  locale?: string;
  routeType?: string;
  /** Scenario-aware summary для preview/metadata (только для безопасных маршрутов). */
  scenarioSummary?: string | null;
  /** Payload сценария: при переданном значении используется buildScenarioMetadataNote(..., { includeLocalLabel: false }) для canonical/preview. */
  scenarioPayload?: ScenarioAwareSharePayload | null;
}): ShareReadyMetadataResult {
  const { seo, shareCard, locale, routeType, scenarioSummary, scenarioPayload } = options;
  const baseMetadata = seo.metadata;

  if (!shareCard) {
    return {
      metadata: baseMetadata,
      canonicalUrl: seo.canonicalUrl
    };
  }

  const ogMeta = buildOgMetaFromShareCard(shareCard);
  const exportFormats = buildExportFormatsFromCard(shareCard);

  const scenarioTextRaw =
    scenarioPayload != null
      ? buildScenarioMetadataNote(scenarioPayload, { includeLocalLabel: false }).scenarioSummary
      : scenarioSummary ?? null;
  const scenarioText =
    scenarioTextRaw && shouldUseScenarioSummaryInMetadata(routeType)
      ? scenarioTextRaw
      : null;

  const ogDescription = scenarioText
    ? `${ogMeta.description} — ${scenarioText}`
    : ogMeta.description;

  const metadata: Metadata = {
    ...baseMetadata,
    openGraph: {
      ...(baseMetadata.openGraph ?? {}),
      title: ogMeta.title,
      description: ogDescription,
      ...(locale && { locale })
    },
    twitter: {
      ...(baseMetadata.twitter ?? {}),
      title: ogMeta.title,
      description: ogDescription
    },
    other: {
      ...(baseMetadata as any).other,
      "share:card": JSON.stringify(shareCard),
      "share:card:compact": exportFormats.compact,
      ...(scenarioText && { "share:scenario-summary": scenarioText }),
      ...(locale && { "share:locale": locale }),
      ...(routeType && { "share:route": routeType })
    }
  };

  return {
    metadata,
    canonicalUrl: seo.canonicalUrl,
    shareCard,
    shareExportFormats: exportFormats
  };
}

