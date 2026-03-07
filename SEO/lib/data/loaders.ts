import fs from "node:fs/promises";
import path from "node:path";

import type {
  Country,
  City,
  Profession,
  FaqTemplate,
  ContentBlock
} from "./types";

interface DataCache<T> {
  value: T | null;
  loaded: boolean;
}

const countriesCache: DataCache<Country[]> = { value: null, loaded: false };
const citiesCache: DataCache<City[]> = { value: null, loaded: false };
const professionsCache: DataCache<Profession[]> = { value: null, loaded: false };
const faqTemplatesCache: DataCache<FaqTemplate[]> = {
  value: null,
  loaded: false
};
const contentBlocksCache: DataCache<ContentBlock[]> = {
  value: null,
  loaded: false
};

async function loadJsonFile<T>(relativePath: string): Promise<T> {
  const filePath = path.join(process.cwd(), relativePath);
  const raw = await fs.readFile(filePath, "utf8");
  return JSON.parse(raw) as T;
}

export async function loadCountries(): Promise<Country[]> {
  if (countriesCache.loaded && countriesCache.value) {
    return countriesCache.value;
  }

  const countries = await loadJsonFile<Country[]>("data/countries.json");
  countriesCache.value = countries;
  countriesCache.loaded = true;
  return countries;
}

export async function loadCities(): Promise<City[]> {
  if (citiesCache.loaded && citiesCache.value) {
    return citiesCache.value;
  }

  const cities = await loadJsonFile<City[]>("data/cities.json");
  citiesCache.value = cities;
  citiesCache.loaded = true;
  return cities;
}

export async function loadProfessions(): Promise<Profession[]> {
  if (professionsCache.loaded && professionsCache.value) {
    return professionsCache.value;
  }

  const professions = await loadJsonFile<Profession[]>(
    "data/professions.json"
  );
  professionsCache.value = professions;
  professionsCache.loaded = true;
  return professions;
}

export async function loadFaqTemplates(): Promise<FaqTemplate[]> {
  if (faqTemplatesCache.loaded && faqTemplatesCache.value) {
    return faqTemplatesCache.value;
  }

  const templates = await loadJsonFile<FaqTemplate[]>(
    "data/faq-templates.json"
  );
  faqTemplatesCache.value = templates;
  faqTemplatesCache.loaded = true;
  return templates;
}

export async function loadContentBlocks(): Promise<ContentBlock[]> {
  if (contentBlocksCache.loaded && contentBlocksCache.value) {
    return contentBlocksCache.value;
  }

  const blocks = await loadJsonFile<ContentBlock[]>(
    "data/content-blocks.json"
  );
  contentBlocksCache.value = blocks;
  contentBlocksCache.loaded = true;
  return blocks;
}

