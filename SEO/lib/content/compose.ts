import { renderTemplate } from "./render";
import type { TemplateContext } from "./types";
import type { FaqTemplate } from "../data/types";
import type { ContentBlock, ContentBlockVariant } from "../data/types";
import type { PageType } from "../seo/types";

export interface RenderedFaqItem {
  question: string;
  answer: string;
}

/**
 * Рендерит массив FAQ-шаблонов в готовые вопрос/ответ с подстановкой токенов.
 */
export function renderFaqTemplates(
  templates: FaqTemplate[],
  context: Partial<TemplateContext> = {}
): RenderedFaqItem[] {
  return templates.map((t) => ({
    question: renderTemplate(t.qTemplate, context),
    answer: renderTemplate(t.aTemplate, context)
  }));
}

function variantMatchesPageType(
  variant: ContentBlockVariant,
  pageType?: PageType
): boolean {
  if (!variant.conditions || !pageType) return true;
  if (variant.conditions.pageTypes && !variant.conditions.pageTypes.includes(pageType)) {
    return false;
  }
  return true;
}

function pickVariant(
  block: ContentBlock,
  context: Partial<TemplateContext>,
  pageType?: PageType
): ContentBlockVariant | null {
  const candidates = block.bodyVariants.filter((variant) =>
    variantMatchesPageType(variant, pageType)
  );

  if (!candidates.length) {
    return block.bodyVariants[0] ?? null;
  }

  // Простая детерминированная выборка с учётом weight
  const base =
    context.cityName ||
    context.professionName ||
    context.countryName ||
    block.id;

  const hash =
    Array.from(base).reduce((acc, ch) => acc + ch.charCodeAt(0), 0) ||
    Math.floor(Math.random() * 1000);

  const weights = candidates.map((c) => c.weight ?? 1);
  const total = weights.reduce((sum, w) => sum + w, 0);
  if (!total) {
    const index = hash % candidates.length;
    return candidates[index];
  }

  let threshold = hash % total;
  for (let i = 0; i < candidates.length; i++) {
    threshold -= weights[i];
    if (threshold < 0) {
      return candidates[i];
    }
  }

  return candidates[0] ?? null;
}

/**
 * Из контент-блоков собирает массив строк для explanation section.
 * Вариант выбирается с учётом pageType и контекста, а не всегда bodyVariants[0].
 */
export function renderContentBlocksToParagraphs(
  blocks: ContentBlock[],
  context: Partial<TemplateContext> & { pageType?: PageType } = {}
): string[] {
  const out: string[] = [];
  for (const block of blocks) {
    const variant = pickVariant(block, context, context.pageType);
    if (variant?.text) {
      out.push(renderTemplate(variant.text, context));
    }
  }
  return out;
}
