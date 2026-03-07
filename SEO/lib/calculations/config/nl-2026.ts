import type { TaxConfig } from "../types";

/**
 * Упрощённая конфигурация для Нидерландов (2026).
 * Отличается ставками и структурой от Германии, но следует той же модели.
 */
export const nl2026Config: TaxConfig = {
  id: "nl-2026",
  countryId: "nl",
  year: 2026,
  incomeTaxBrackets: [
    { from: 0, to: 37000, rate: 0.09 },
    { from: 37000, to: 73000, rate: 0.37 },
    { from: 73000, to: undefined, rate: 0.49 }
  ],
  socialContributions: [
    { label: "Pension", rate: 0.05 },
    { label: "Health", rate: 0.06 }
  ],
  employerContributionRate: 0.2
};

