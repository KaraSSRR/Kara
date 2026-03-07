/**
 * Типы для будущего расширения сценариев расчёта.
 * Не меняют текущий calculation flow — только задел под:
 * - tax regime selector (например, разные режимы для самозанятых/ИП)
 * - bonus/extra income
 * - contract type (employment, freelance, etc.)
 * - scenario toggles (сравнение «с бонусом» / «без»)
 */

export type TaxRegimeId = "default" | "simplified" | "self-employed";

export type ContractType = "employment" | "freelance" | "contract";

export interface BonusConfig {
  amount: number;
  period: "monthly" | "yearly" | "one-time";
}

/** Расширенный вход для расчёта (опциональные поля — пока не используются в SalaryInput). */
export interface ScenarioInputExtension {
  taxRegimeId?: TaxRegimeId;
  contractType?: ContractType;
  bonus?: BonusConfig;
  /** Флаг для будущих сценариев (например, «учесть 13-ю зарплату»). */
  scenarioFlags?: Record<string, boolean>;
}
