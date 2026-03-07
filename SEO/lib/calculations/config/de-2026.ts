import type { TaxConfig } from "../types";

/**
 * Упрощённая конфигурация для Германии (2026).
 * Реальные ставки и пороги можно заменить на актуальные; структура позволяет масштабировать.
 */
export const de2026Config: TaxConfig = {
  id: "de-2026",
  countryId: "de",
  year: 2026,
  incomeTaxBrackets: [
    { from: 0, to: 11604, rate: 0 },
    { from: 11604, to: 17005, rate: 0.14 },
    { from: 17005, to: 66760, rate: 0.24 },
    { from: 66760, to: 277825, rate: 0.42 },
    { from: 277825, to: undefined, rate: 0.45 }
  ],
  socialContributions: [
    { label: "Pension", rate: 0.093, maxBase: 95800 },
    { label: "Unemployment", rate: 0.013, maxBase: 95800 },
    { label: "Health", rate: 0.0735, maxBase: 95800 },
    { label: "Care", rate: 0.01775, maxBase: 95800 }
  ],
  employerContributionRate: 0.18
};
