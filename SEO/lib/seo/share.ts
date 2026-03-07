import type { ShareCard } from "@/lib/share/cards";

export interface OgShareMeta {
  title: string;
  description: string;
}

export function buildOgMetaFromShareCard(card: ShareCard): OgShareMeta {
  const primaryMetrics = card.metrics.slice(0, 2).map((m) => `${m.label}: ${m.value}`).join(" · ");

  const descriptionParts = [];
  if (card.subtitle) descriptionParts.push(card.subtitle);
  if (primaryMetrics) descriptionParts.push(primaryMetrics);
  if (card.footer) descriptionParts.push(card.footer);

  return {
    title: card.title,
    description: descriptionParts.join(" — ")
  };
}

