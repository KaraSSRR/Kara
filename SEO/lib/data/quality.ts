import type { ContentBlock } from "./types";

export interface PageQualitySignals {
  explanationBlocks: ContentBlock[];
  faqCount: number;
  relatedCount: number;
}

export function isPageContentStrong(signals: PageQualitySignals): boolean {
  const explanationOk = signals.explanationBlocks.length > 0;
  const faqOk = signals.faqCount > 0;
  const relatedOk = signals.relatedCount >= 1;
  return explanationOk && (faqOk || relatedOk);
}

export function scoreFromPageQualitySignals(signals: PageQualitySignals): number {
  const explanationScore = signals.explanationBlocks.length > 0 ? 0.4 : 0;
  const faqScore = signals.faqCount > 0 ? 0.3 : 0;
  const relatedScore = signals.relatedCount > 0 ? 0.3 : 0;
  return explanationScore + faqScore + relatedScore;
}

export type PageQualityLevel = "weak" | "borderline" | "strong";

export interface QualityEvaluation {
  score: number;
  level: PageQualityLevel;
  indexable: boolean;
  noindexCandidate: boolean;
}

export function evaluateProfessionCityQuality(score: number): QualityEvaluation {
  const clamped = Math.max(0, Math.min(1, score));

  if (clamped >= 0.7) {
    return {
      score: clamped,
      level: "strong",
      indexable: true,
      noindexCandidate: false
    };
  }

  if (clamped >= 0.5) {
    return {
      score: clamped,
      level: "borderline",
      indexable: true,
      noindexCandidate: true
    };
  }

  return {
    score: clamped,
    level: "weak",
    indexable: false,
    noindexCandidate: true
  };
}

export interface ComparisonQualityInput {
  hasSnapshots: boolean;
  hasTrustBlocks: boolean;
  hasRelatedLinks: boolean;
  /** Дополнительная надстройка: есть ли осмысленные сценарии сравнения вычетов/налогового возврата. */
  hasRefundInsights?: boolean;
}

export function evaluateComparisonPageQuality(
  input: ComparisonQualityInput
): QualityEvaluation {
  const scoreParts = [
    input.hasSnapshots ? 0.4 : 0,
    input.hasTrustBlocks ? 0.3 : 0,
    input.hasRelatedLinks ? 0.3 : 0,
    input.hasRefundInsights ? 0.1 : 0
  ];
  const score = scoreParts.reduce((s, v) => s + v, 0);

  if (score >= 0.7) {
    return {
      score,
      level: "strong",
      indexable: true,
      noindexCandidate: false
    };
  }

  if (score >= 0.5) {
    return {
      score,
      level: "borderline",
      indexable: true,
      noindexCandidate: true
    };
  }

  return {
    score,
    level: "weak",
    indexable: false,
    noindexCandidate: true
  };
}

