/**
 * Минимальный слой проверки готовности страницы к публикации.
 * Foundation для контроля качества: metadata, trust, explanation, related, locale/currency.
 */

export interface PageReadinessCheck {
  hasMetadata: boolean;
  hasTrust: boolean;
  hasExplanation: boolean;
  hasRelated: boolean;
  hasLocaleCurrency: boolean;
}

export interface PageReadinessInput {
  metadata?: Record<string, unknown> | null;
  trustMethodology?: string[];
  explanationParagraphs?: string[];
  relatedCount?: number;
  locale?: string | null;
  currencyCode?: string | null;
}

export function checkPageReadiness(input: PageReadinessInput): PageReadinessCheck {
  return {
    hasMetadata: Boolean(input.metadata && Object.keys(input.metadata).length > 0),
    hasTrust: Boolean(input.trustMethodology?.length),
    hasExplanation: Boolean(input.explanationParagraphs?.length),
    hasRelated: (input.relatedCount ?? 0) > 0,
    hasLocaleCurrency: Boolean(input.locale && input.currencyCode)
  };
}
