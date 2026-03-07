import type { PageType } from "../seo/types";

export type PopulationBucket = "s" | "m" | "l" | "xl";

export interface Country {
  id: string;
  slug: string;
  name: string;
  isoCode: string;
  currencyCode: string;
  currencySymbol: string;
  locale: string;
  taxConfigId: string;
  searchPriority: number;
  hasLocalData: boolean;
  defaultCityId?: string;
  seoTitleTemplate?: string;
  seoDescriptionTemplate?: string;
  faqTemplateIds: string[];
  primaryTopics: string[];
}

export interface City {
  id: string;
  slug: string;
  name: string;
  countryId: Country["id"];
  populationBucket: PopulationBucket;
  costOfLivingIndex?: number;
  searchPriority: number;
  supportedProfessionIds: string[];
  introBlockId?: string;
  costOfLivingBlockId?: string;
  marketTrendsBlockId?: string;
  faqTemplateIds?: string[];
}

export interface ProfessionLevel {
  id: string;
  label: string;
  multiplier: number;
}

export interface Profession {
  id: string;
  slug: string;
  name: string;
  category: string;
  searchPriorityByCountry: Record<Country["id"], number>;
  levels: ProfessionLevel[];
  synonyms: string[];
  introBlockId?: string;
  responsibilitiesBlockId?: string;
  skillsBlockId?: string;
  faqTemplateIds?: string[];
}

export type FaqAppliesTo =
  | "global"
  | "country"
  | "city"
  | "profession"
  | "profession-city";

export interface FaqTemplateConditions {
  countryIds?: Country["id"][];
  cityIds?: City["id"][];
  professionIds?: Profession["id"][];
  professionCategories?: Profession["category"][];
  pageTypes?: PageType[];
}

export interface FaqTemplate {
  id: string;
  appliesTo: FaqAppliesTo;
  conditions?: FaqTemplateConditions;
  qTemplate: string;
  aTemplate: string;
  variationGroup?: string;
  weight?: number;
}

export type ContentBlockType =
  | "intro"
  | "explainer"
  | "comparison"
  | "how-it-works"
  | "taxes-overview"
  | "local-market-context"
  | "cta";

export interface ContentBlockAppliesTo {
  pageTypes?: PageType[];
  countryIds?: Country["id"][];
  cityIds?: City["id"][];
  professionIds?: Profession["id"][];
  professionCategories?: Profession["category"][];
}

export interface ContentBlockVariant {
  id: string;
  text: string;
  weight?: number;
  conditions?: ContentBlockAppliesTo;
}

export interface ContentBlock {
  id: string;
  type: ContentBlockType;
  appliesTo: ContentBlockAppliesTo;
  tokens?: string[];
  bodyVariants: ContentBlockVariant[];
}

export interface ProfessionCityPageInput {
  country: Country;
  city: City;
  profession: Profession;
  qualityScore: number;
}

export type RoutePageType =
  | "country"
  | "city"
  | "profession"
  | "professionCity";

export interface CountryRouteInput {
  type: "country";
  country: Country;
}

export interface CityRouteInput {
  type: "city";
  country: Country;
  city: City;
}

export interface ProfessionRouteInput {
  type: "profession";
  country: Country;
  profession: Profession;
}

export interface ProfessionCityRouteInput {
  type: "professionCity";
  country: Country;
  city: City;
  profession: Profession;
  qualityScore: number;
}

export type PageGenerationEntity =
  | CountryRouteInput
  | CityRouteInput
  | ProfessionRouteInput
  | ProfessionCityRouteInput;

