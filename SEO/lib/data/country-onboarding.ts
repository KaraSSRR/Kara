import { loadCountries } from "./loaders";
import type { Country } from "./types";
import {
  getCountryReadinessAsync,
  type CountryReadiness
} from "./country-context";
import { getCalculatorPresetForRoute } from "./presets";
import type { SeoRouteType } from "@/lib/seo/decisions";

export interface CountryOnboardingChecklist {
  countryId: string;
  hasCountryData: boolean;
  hasTaxConfig: boolean;
  hasLocaleCurrency: boolean;
  hasPresets: boolean;
  hasTrustAndContentHooks: boolean;
  readiness: CountryReadiness;
  /** Итоговый статус: есть ли незакрытые обязательные элементы. */
  status: "ready" | "missing-required" | "missing-recommended";
  /** Группы для онбординга: что ещё стоит сделать. */
  missingRequired: string[];
  missingRecommended: string[];
  missingOptional: string[];
}

const PRESET_ROUTES: SeoRouteType[] = [
  "generic-calculator",
  "gross-to-net",
  "net-to-gross",
  "city",
  "profession",
  "profession-city",
  "city-comparison",
  "profession-comparison"
];

export async function buildCountryOnboardingChecklist(
  countryId: string
): Promise<CountryOnboardingChecklist> {
  const countries = await loadCountries();
  const country = countries.find((c) => c.id === countryId);

  const readiness = await getCountryReadinessAsync(countryId);

  // Пресеты глобальные по типу маршрута — считаем, что они есть, если функция не падает.
  const hasPresets = PRESET_ROUTES.every((route) => {
    try {
      return Boolean(getCalculatorPresetForRoute(route));
    } catch {
      return false;
    }
  });

  // Простая эвристика: если у страны есть faqTemplateIds или hasLocalData, считаем, что хуки trust/content подготовлены.
  const hasTrustAndContentHooks =
    Boolean(country?.faqTemplateIds?.length) || Boolean(country?.hasLocalData);

  const missingRequired: string[] = [];
  if (!Boolean(country)) missingRequired.push("country-data");
  if (!readiness.hasTaxConfig) missingRequired.push("tax-config");
  if (!Boolean(readiness.locale && readiness.currencyCode)) {
    missingRequired.push("locale/currency");
  }

  const missingRecommended: string[] = [];
  if (!hasPresets) missingRecommended.push("presets");
  if (!hasTrustAndContentHooks) missingRecommended.push("trust/content");

  const missingOptional: string[] = [];

  let status: CountryOnboardingChecklist["status"] = "ready";
  if (missingRequired.length > 0) {
    status = "missing-required";
  } else if (missingRecommended.length > 0) {
    status = "missing-recommended";
  }

  return {
    countryId,
    hasCountryData: Boolean(country),
    hasTaxConfig: readiness.hasTaxConfig,
    hasLocaleCurrency: Boolean(readiness.locale && readiness.currencyCode),
    hasPresets,
    hasTrustAndContentHooks,
    readiness,
    status,
    missingRequired,
    missingRecommended,
    missingOptional
  };
}

