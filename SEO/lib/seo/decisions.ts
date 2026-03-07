import type { PageType } from "./types";
import { evaluateProfessionCityQuality } from "@/lib/data/quality";
import { evaluateComparisonPageQuality } from "@/lib/data/quality";

export type SeoRouteType =
  | "home"
  | "generic-calculator"
  | "gross-to-net"
  | "net-to-gross"
  | "city"
  | "profession"
  | "profession-city"
  | "city-comparison"
  | "profession-comparison";

export interface SeoDecision {
  indexable: boolean;
  noindex: boolean;
  includeInSitemap: boolean;
  qualityLevel?: "weak" | "borderline" | "strong";
  canonicalMode: "default" | "consolidated" | "canonical";
  includeJsonLd: boolean;
}

export function buildSeoDecisionForRoute(options: {
  routeType: SeoRouteType;
  qualityScore?: number;
}): SeoDecision {
  if (
    (options.routeType === "city" ||
      options.routeType === "profession" ||
      options.routeType === "profession-city") &&
    typeof options.qualityScore === "number"
  ) {
    const evaluation = evaluateProfessionCityQuality(options.qualityScore);

    return {
      indexable: evaluation.indexable,
      noindex: evaluation.noindexCandidate,
      includeInSitemap: evaluation.indexable && !evaluation.noindexCandidate,
      qualityLevel: evaluation.level,
      canonicalMode: "default",
      includeJsonLd: true
    };
  }

  if (
    (options.routeType === "city-comparison" ||
      options.routeType === "profession-comparison") &&
    typeof options.qualityScore === "number"
  ) {
    const evaluation = evaluateComparisonPageQuality({
      hasSnapshots: options.qualityScore >= 0.4,
      hasTrustBlocks: true,
      hasRelatedLinks: true
    });

    return {
      indexable: evaluation.indexable,
      noindex: evaluation.noindexCandidate,
      includeInSitemap: evaluation.indexable && !evaluation.noindexCandidate,
      qualityLevel: evaluation.level,
      canonicalMode: "default",
      includeJsonLd: true
    };
  }

  return {
    indexable: true,
    noindex: false,
    includeInSitemap: true,
    canonicalMode: "default",
    includeJsonLd: true
  };
}

