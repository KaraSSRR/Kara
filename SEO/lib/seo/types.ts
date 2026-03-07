import type { Metadata } from "next";

export type PageType =
  | "home"
  | "generic-calculator"
  | "gross-to-net"
  | "net-to-gross"
  | "country"
  | "city"
  | "profession"
  | "profession-city"
  | "city-comparison"
  | "profession-comparison"
  | "faq";

export interface SeoContextBase {
  pageType: PageType;
  locale?: string;
  year?: number;
}

export interface CountrySeoContext extends SeoContextBase {
  pageType:
    | "country"
    | "gross-to-net"
    | "net-to-gross"
    | "generic-calculator";
  country: {
    id: string;
    slug: string;
    name: string;
    isoCode: string;
    currencyCode: string;
  };
}

export interface CitySeoContext extends SeoContextBase {
  pageType: "city" | "profession-city";
  country: CountrySeoContext["country"];
  city: {
    id: string;
    slug: string;
    name: string;
  };
}

export interface ProfessionSeoContext extends SeoContextBase {
  pageType: "profession" | "profession-city";
  country?: CountrySeoContext["country"];
  city?: CitySeoContext["city"];
  profession: {
    id: string;
    slug: string;
    name: string;
    category?: string;
  };
}

export type SeoContext =
  | SeoContextBase
  | CountrySeoContext
  | CitySeoContext
  | ProfessionSeoContext;

export interface CanonicalParams {
  pathname: string;
}

export interface SeoMetadataResult {
  metadata: Metadata;
  canonicalUrl: string;
}

