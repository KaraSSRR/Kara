import type { TaxConfig } from "../types";
import { de2026Config } from "./de-2026";
import { nl2026Config } from "./nl-2026";

const configs: TaxConfig[] = [de2026Config, nl2026Config];

export function getTaxConfigByCountryId(countryId: string): TaxConfig | null {
  return configs.find((c) => c.countryId === countryId) ?? null;
}

export function getTaxConfigById(id: string): TaxConfig | null {
  return configs.find((c) => c.id === id) ?? null;
}
