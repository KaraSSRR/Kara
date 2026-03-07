import type { Metadata } from "next";
import type { PageType } from "./types";

interface WebAppSchemaOptions {
  name: string;
  url: string;
  description: string;
}

export function buildWebApplicationSchema(
  options: WebAppSchemaOptions
): Record<string, unknown> {
  return {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    name: options.name,
    url: options.url,
    description: options.description,
    applicationCategory: "BusinessApplication",
    operatingSystem: "Web"
  };
}

export interface BreadcrumbItem {
  name: string;
  url: string;
}

export function buildBreadcrumbSchema(
  items: BreadcrumbItem[]
): Record<string, unknown> {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.name,
      item: item.url
    }))
  };
}

export interface FaqItem {
  question: string;
  answer: string;
}

export function buildFaqSchema(faqs: FaqItem[]): Record<string, unknown> | null {
  if (!faqs.length) {
    return null;
  }

  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqs.map((faq) => ({
      "@type": "Question",
      name: faq.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: faq.answer
      }
    }))
  };
}

/** Собирает несколько JSON-LD схем в массив строк для metadata.other["ld+json"]. */
export function mergeJsonLdSchemas(
  schemas: (Record<string, unknown> | null | undefined)[]
): string[] {
  return schemas
    .filter((s): s is Record<string, unknown> => s != null && Object.keys(s).length > 0)
    .map((s) => JSON.stringify(s));
}

interface PageSchemaContext {
  pageType: PageType;
  breadcrumbs: BreadcrumbItem[];
  faqItems?: FaqItem[];
  includeWebApp?: boolean;
  webAppOptions?: WebAppSchemaOptions;
}

export function buildPageJsonLd(ctx: PageSchemaContext): string[] {
  const breadcrumb = buildBreadcrumbSchema(ctx.breadcrumbs);
  const faq = ctx.faqItems ? buildFaqSchema(ctx.faqItems) : null;
  const webApp =
    ctx.includeWebApp && ctx.webAppOptions
      ? buildWebApplicationSchema(ctx.webAppOptions)
      : null;

  return mergeJsonLdSchemas([breadcrumb, faq, webApp]);
}

export function injectJsonLd(metadata: Metadata, data: unknown): Metadata {
  if (!data || typeof data !== "object") {
    return metadata;
  }

  const script = JSON.stringify(data);

  const existing = (metadata as any).other ?? {};

  return {
    ...metadata,
    other: {
      ...existing,
      "ld+json": [
        ...(Array.isArray(existing["ld+json"])
          ? existing["ld+json"]
          : existing["ld+json"]
          ? [existing["ld+json"]]
          : []),
        script
      ]
    }
  };
}

