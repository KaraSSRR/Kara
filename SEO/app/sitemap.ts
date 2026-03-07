import type { MetadataRoute } from "next";
import { SITE_ORIGIN } from "@/lib/seo/site";
import { getSitemapBuckets } from "@/lib/data/sitemap";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const buckets = await getSitemapBuckets();

  return buckets.indexable.map((entry) => ({
    url: `${SITE_ORIGIN}${entry.path}`,
    lastModified: entry.lastmod,
    changeFrequency: entry.changeFrequency,
    priority: entry.priority
  }));
}

