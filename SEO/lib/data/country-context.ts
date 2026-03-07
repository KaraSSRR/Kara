import type { Country } from "./types";
import { loadCountries } from "./loaders";
import { getTaxConfigByCountryId } from "@/lib/calculations/config";

let countriesCache: Country[] | null = null;

async function getCountries(): Promise<Country[]> {
  if (!countriesCache) {
    countriesCache = await loadCountries();
  }
  return countriesCache;
}

/** Локаль для форматирования (даты, числа) по стране. Используется в formatters и UI. */
export async function getLocaleForCountryId(countryId: string): Promise<string> {
  const countries = await getCountries();
  const country = countries.find((c) => c.id === countryId);
  return country?.locale ?? "de-DE";
}

/** Код валюты по стране. Для расчётов и отображения. */
export async function getCurrencyForCountryId(
  countryId: string
): Promise<string> {
  const countries = await getCountries();
  const country = countries.find((c) => c.id === countryId);
  return country?.currencyCode ?? "EUR";
}

/** Символ валюты по стране. Для UI. */
export async function getCurrencySymbolForCountryId(
  countryId: string
): Promise<string> {
  const countries = await getCountries();
  const country = countries.find((c) => c.id === countryId);
  return country?.currencySymbol ?? "€";
}

/** Синхронный вариант: принимает уже загруженный список стран. Для использования в компонентах, где страны уже есть. */
export function getLocaleForCountry(countries: Country[], countryId: string): string {
  const country = countries.find((c) => c.id === countryId);
  return country?.locale ?? "de-DE";
}

export function getCurrencyForCountry(countries: Country[], countryId: string): string {
  const country = countries.find((c) => c.id === countryId);
  return country?.currencyCode ?? "EUR";
}

export function getCurrencySymbolForCountry(countries: Country[], countryId: string): string {
  const country = countries.find((c) => c.id === countryId);
  return country?.currencySymbol ?? "€";
}

/**
 * Готовность страны к использованию в калькуляторе: есть в data, есть tax config, есть locale/currency.
 * При добавлении новой страны: запись в countries.json + конфиг в lib/calculations/config.
 */
export interface CountryReadiness {
  hasCountry: boolean;
  hasTaxConfig: boolean;
  locale: string;
  currencyCode: string;
  currencySymbol: string;
}

/** Синхронная проверка по уже загруженному списку стран. */
export function getCountryReadiness(countries: Country[], countryId: string): CountryReadiness {
  const country = countries.find((c) => c.id === countryId);
  const taxConfig = getTaxConfigByCountryId(countryId);
  return {
    hasCountry: Boolean(country),
    hasTaxConfig: Boolean(taxConfig),
    locale: country?.locale ?? "de-DE",
    currencyCode: country?.currencyCode ?? "EUR",
    currencySymbol: country?.currencySymbol ?? "€"
  };
}

/** Асинхронная версия: подгружает страны при первом вызове. */
export async function getCountryReadinessAsync(countryId: string): Promise<CountryReadiness> {
  const countries = await getCountries();
  return getCountryReadiness(countries, countryId);
}
