import {
  generateCityRouteInputs,
  generateProfessionCityRouteInputs,
  generateProfessionRouteInputs
} from "./generators";
import { buildSeoDecisionForRoute } from "@/lib/seo/decisions";

export interface SitemapBucket<T> {
  indexable: T[];
  noindex: T[];
  excluded: T[];
}

export type SitemapRouteType =
  | "home"
  | "generic-calculator"
  | "gross-to-net"
  | "net-to-gross"
  | "city"
  | "profession"
  | "profession-city"
  | "city-comparison"
  | "profession-comparison";

export interface SitemapRouteEntry {
  path: string;
  type: SitemapRouteType;
  qualityScore?: number;
  lastmod?: string;
  changeFrequency?: "daily" | "weekly" | "monthly" | "yearly";
  priority?: number;
}

function getDefaultSitemapMeta(
  type: SitemapRouteType
): Pick<SitemapRouteEntry, "changeFrequency" | "priority"> {
  switch (type) {
    case "home":
    case "generic-calculator":
      return { changeFrequency: "daily", priority: 1.0 };
    case "gross-to-net":
    case "net-to-gross":
      return { changeFrequency: "daily", priority: 0.9 };
    case "city":
    case "profession":
      return { changeFrequency: "weekly", priority: 0.8 };
    case "profession-city":
      return { changeFrequency: "monthly", priority: 0.6 };
    default:
      return { changeFrequency: "monthly", priority: 0.5 };
  }
}

export async function getSitemapBuckets(): Promise<
  SitemapBucket<SitemapRouteEntry>
> {
  const buckets: SitemapBucket<SitemapRouteEntry> = {
    indexable: [],
    noindex: [],
    excluded: []
  };

  const now = new Date().toISOString();

  // Статические страницы
  buckets.indexable.push(
    {
      path: "/",
      type: "home",
      lastmod: now,
      ...getDefaultSitemapMeta("home")
    },
    {
      path: "/salary-calculator",
      type: "generic-calculator",
      lastmod: now,
      ...getDefaultSitemapMeta("generic-calculator")
    },
    {
      path: "/gross-to-net",
      type: "gross-to-net",
      lastmod: now,
      ...getDefaultSitemapMeta("gross-to-net")
    },
    {
      path: "/net-to-gross",
      type: "net-to-gross",
      lastmod: now,
      ...getDefaultSitemapMeta("net-to-gross")
    }
  );

  // Городские страницы
  const cityInputs = await generateCityRouteInputs();
  for (const input of cityInputs) {
    buckets.indexable.push({
      path: `/salary-calculator/${input.city.slug}`,
      type: "city",
      lastmod: now,
      ...getDefaultSitemapMeta("city")
    });
  }

  // Профессиональные страницы
  const professionInputs = await generateProfessionRouteInputs();
  for (const input of professionInputs) {
    buckets.indexable.push({
      path: `/salary-calculator/profession/${input.profession.slug}`,
      type: "profession",
      lastmod: now,
      ...getDefaultSitemapMeta("profession")
    });
  }

  // Страницы профессия+город
  const professionCityInputs = await generateProfessionCityRouteInputs({
    minQualityScore: 0
  });

  for (const input of professionCityInputs) {
    const decision = buildSeoDecisionForRoute({
      routeType: "profession-city",
      qualityScore: input.qualityScore
    });

    const entry: SitemapRouteEntry = {
      path: `/salary-calculator/${input.profession.slug}/${input.city.slug}`,
      type: "profession-city",
      qualityScore: input.qualityScore,
      lastmod: now,
      ...getDefaultSitemapMeta("profession-city")
    };

    if (!decision.indexable) {
      buckets.excluded.push(entry);
    } else if (!decision.includeInSitemap) {
      buckets.noindex.push(entry);
    } else {
      buckets.indexable.push(entry);
    }
  }

  // Сравнения городов и профессий (MVP: только несколько топовых)
  const topCityComparisons = buckets.indexable
    .filter((entry) => entry.type === "city")
    .slice(0, 4);

  if (topCityComparisons.length >= 2) {
    const comparisonDecision = buildSeoDecisionForRoute({
      routeType: "city-comparison"
    });

    if (comparisonDecision.includeInSitemap) {
      for (let i = 0; i < topCityComparisons.length; i++) {
        for (let j = i + 1; j < topCityComparisons.length; j++) {
          const a = topCityComparisons[i];
          const b = topCityComparisons[j];
          buckets.indexable.push({
            path: `/compare/cities/${a.path.split("/").pop()}-vs-${b.path
              .split("/")
              .pop()}`,
            type: "city-comparison",
            lastmod: now,
            ...getDefaultSitemapMeta("city-comparison" as SitemapRouteType)
          });
        }
      }
    }
  }

  const topProfessionComparisons = buckets.indexable
    .filter((entry) => entry.type === "profession")
    .slice(0, 4);

  if (topProfessionComparisons.length >= 2) {
    const comparisonDecision = buildSeoDecisionForRoute({
      routeType: "profession-comparison"
    });

    if (comparisonDecision.includeInSitemap) {
      for (let i = 0; i < topProfessionComparisons.length; i++) {
        for (let j = i + 1; j < topProfessionComparisons.length; j++) {
          const a = topProfessionComparisons[i];
          const b = topProfessionComparisons[j];
          buckets.indexable.push({
            path: `/compare/professions/${a.path.split("/").pop()}-vs-${b.path
              .split("/")
              .pop()}`,
            type: "profession-comparison",
            lastmod: now,
            ...getDefaultSitemapMeta("profession-comparison" as SitemapRouteType)
          });
        }
      }
    }
  }

  return buckets;
}

