import type { City, Profession } from "./types";

export type ComparisonType =
  | "city-vs-city"
  | "profession-vs-profession"
  | "scenario-vs-scenario";

export interface CityComparisonDescriptor {
  type: "city-vs-city";
  primaryCity: City;
  secondaryCity: City;
}

export interface ProfessionComparisonDescriptor {
  type: "profession-vs-profession";
  primaryProfession: Profession;
  secondaryProfession: Profession;
}

export interface ScenarioComparisonDescriptor {
  type: "scenario-vs-scenario";
  labelA: string;
  labelB: string;
}

export type ComparisonDescriptor =
  | CityComparisonDescriptor
  | ProfessionComparisonDescriptor
  | ScenarioComparisonDescriptor;

export function buildCityComparisonDescriptor(
  primary: City,
  secondary: City
): CityComparisonDescriptor {
  return {
    type: "city-vs-city",
    primaryCity: primary,
    secondaryCity: secondary
  };
}

export function buildProfessionComparisonDescriptor(
  primary: Profession,
  secondary: Profession
): ProfessionComparisonDescriptor {
  return {
    type: "profession-vs-profession",
    primaryProfession: primary,
    secondaryProfession: secondary
  };
}

export interface ComparisonScenario {
  key: string;
  label: string;
  description: string;
  amount: number;
  period: "monthly" | "yearly";
}

export function buildDefaultComparisonScenarios(options: {
  presetAmount: string;
  presetPeriod: "monthly" | "yearly";
}): ComparisonScenario[] {
  const baseAmount = Number(options.presetAmount) || 0;

  return [
    {
      key: "same-gross",
      label: "Одинаковый gross в обеих точках",
      description:
        "Сравнение при фиксированной сумме gross: помогает понять, где тот же оффер даёт более высокий net и какую эффективную ставку вы фактически платите.",
      amount: baseAmount,
      period: options.presetPeriod
    },
    {
      key: "higher-gross",
      label: "Сценарий с повышенным gross",
      description:
        "Сценарий для обсуждения повышения: увеличьте gross и посмотрите, как меняется net и налоговая нагрузка в сравнении.",
      amount: Math.round(baseAmount * 1.2),
      period: options.presetPeriod
    }
  ];
}

