import type {
  Country,
  City,
  Profession,
  ContentBlock,
  FaqTemplate
} from "./types";
import { loadCountries, loadCities, loadProfessions, loadFaqTemplates, loadContentBlocks } from "./loaders";
import type { PageType } from "../seo/types";

export async function getCountryBySlug(slug: string): Promise<Country | null> {
  const countries = await loadCountries();
  return countries.find((c) => c.slug === slug) ?? null;
}

export async function getCityBySlug(
  slug: string
): Promise<{ city: City; country: Country } | null> {
  const [cities, countries] = await Promise.all([
    loadCities(),
    loadCountries()
  ]);

  const city = cities.find((c) => c.slug === slug);
  if (!city) return null;

  const country = countries.find((c) => c.id === city.countryId);
  if (!country) return null;

  return { city, country };
}

export async function getProfessionBySlug(
  slug: string
): Promise<Profession | null> {
  const professions = await loadProfessions();
  return professions.find((p) => p.slug === slug) ?? null;
}

export async function getCountryById(id: string): Promise<Country | null> {
  const countries = await loadCountries();
  return countries.find((c) => c.id === id) ?? null;
}

export async function getCityById(id: string): Promise<City | null> {
  const cities = await loadCities();
  return cities.find((c) => c.id === id) ?? null;
}

export async function getProfessionById(
  id: string
): Promise<Profession | null> {
  const professions = await loadProfessions();
  return professions.find((p) => p.id === id) ?? null;
}

export async function getContentBlocksForContext(options: {
  pageType: PageType;
  countryId?: Country["id"];
  cityId?: City["id"];
  professionId?: Profession["id"];
  professionCategory?: Profession["category"];
}): Promise<ContentBlock[]> {
  const blocks = await loadContentBlocks();

  return blocks.filter((block) => {
    const { appliesTo } = block;

    if (appliesTo.pageTypes && !appliesTo.pageTypes.includes(options.pageType)) {
      return false;
    }

    if (appliesTo.countryIds && options.countryId) {
      if (!appliesTo.countryIds.includes(options.countryId)) return false;
    }

    if (appliesTo.cityIds && options.cityId) {
      if (!appliesTo.cityIds.includes(options.cityId)) return false;
    }

    if (appliesTo.professionIds && options.professionId) {
      if (!appliesTo.professionIds.includes(options.professionId)) return false;
    }

    if (
      appliesTo.professionCategories &&
      options.professionCategory &&
      !appliesTo.professionCategories.includes(options.professionCategory)
    ) {
      return false;
    }

    return true;
  });
}

export async function getFaqTemplatesForContext(options: {
  pageType: PageType;
  countryId?: Country["id"];
  cityId?: City["id"];
  professionId?: Profession["id"];
  professionCategory?: Profession["category"];
}): Promise<FaqTemplate[]> {
  const templates = await loadFaqTemplates();

  return templates.filter((template) => {
    const { conditions } = template;

    if (!conditions) {
      return true;
    }

    if (conditions.pageTypes && !conditions.pageTypes.includes(options.pageType)) {
      return false;
    }

    if (conditions.countryIds && options.countryId) {
      if (!conditions.countryIds.includes(options.countryId)) return false;
    }

    if (conditions.cityIds && options.cityId) {
      if (!conditions.cityIds.includes(options.cityId)) return false;
    }

    if (conditions.professionIds && options.professionId) {
      if (!conditions.professionIds.includes(options.professionId)) return false;
    }

    if (
      conditions.professionCategories &&
      options.professionCategory &&
      !conditions.professionCategories.includes(options.professionCategory)
    ) {
      return false;
    }

    return true;
  });
}

