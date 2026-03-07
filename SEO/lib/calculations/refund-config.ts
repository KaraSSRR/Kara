import type {
  SalaryResult,
  TaxRefundCategoryId,
  TaxRefundEstimate
} from "./types";

export interface RefundCategoryDefinition {
  id: TaxRefundCategoryId;
  label: string;
  shortDescription: string;
  /** Дополнительные человекочитаемые варианты названия категории. */
  aliases?: string[];
  /** Отдельные отображаемые лейблы по странам (countryId -> label). */
  countryDisplayLabels?: Record<string, string>;
  /** Короткая заметка про категорию, если нужна дополнительная интерпретация. */
  note?: string;
}

export interface RefundCategoryRule {
  id: TaxRefundCategoryId;
  /** Множитель к эффективной ставке подоходного налога (для консервативных/щедрых категорий). */
  rateMultiplier?: number;
  /** Максимальная доля годового налога, которую эта категория может вернуть в упрощённой модели. */
  maxShareOfAnnualTax?: number;
}

export interface RefundCountrySettings {
  countryId: string;
  enabled: boolean;
  /** Список категорий, которые вообще показываются пользователю. */
  supportedCategories: TaxRefundCategoryId[];
  /** Простые правила для категорий — без сложной страновой логики. */
  categoryRules: Record<TaxRefundCategoryId, RefundCategoryRule>;
}

export interface RefundCountryNotes {
  /** Сообщение, если расчёт возврата для страны отключён. */
  disabledMessage?: string;
  /** Короткий дисклеймер про оценочный характер расчёта. */
  genericDisclaimer?: string;
  /** Дополнительная подсказка про специфику страны. */
  countrySpecificHint?: string;
}

const BASE_CATEGORY_DEFINITIONS: RefundCategoryDefinition[] = [
  {
    id: "education",
    label: "Обучение",
    shortDescription: "Курсы, университет и другие расходы на образование.",
    aliases: ["education", "study"],
    note: "В разных странах к образовательным вычетам могут относиться только определённые программы и аккредитованные учреждения."
  },
  {
    id: "medical",
    label: "Медицина",
    shortDescription: "Медицинские услуги, страховка и связанные расходы.",
    aliases: ["health", "medical"],
    note: "Чаще всего учитываются расходы сверх определённого порога или только часть страховых взносов."
  },
  {
    id: "mortgage",
    label: "Жильё / ипотека",
    shortDescription: "Ипотека, проценты по кредиту и расходы на жильё.",
    aliases: ["housing", "mortgage"],
    note: "Во многих системах учитываются преимущественно проценты по ипотеке, а не вся стоимость жилья."
  },
  {
    id: "charity",
    label: "Благотворительность",
    shortDescription: "Пожертвования зарегистрированным организациям.",
    aliases: ["charity", "donation"],
    note: "Обычно признаются пожертвования в зарегистрированные организации и при наличии подтверждающих документов."
  },
  {
    id: "other",
    label: "Другое",
    shortDescription: "Другие возможные вычеты, не попавшие в список.",
    aliases: ["other"],
    note: "Сводная категория для вычетов, которые не попали в основные группы."
  }
];

const DEFAULT_CATEGORY_RULES: Record<TaxRefundCategoryId, RefundCategoryRule> = {
  education: {
    id: "education",
    rateMultiplier: 1,
    maxShareOfAnnualTax: 0.5
  },
  medical: {
    id: "medical",
    rateMultiplier: 1,
    maxShareOfAnnualTax: 0.7
  },
  mortgage: {
    id: "mortgage",
    rateMultiplier: 1,
    maxShareOfAnnualTax: 0.6
  },
  charity: {
    id: "charity",
    rateMultiplier: 0.9,
    maxShareOfAnnualTax: 0.4
  },
  other: {
    id: "other",
    rateMultiplier: 0.7,
    maxShareOfAnnualTax: 0.3
  }
};

const DEFAULT_SUPPORTED_CATEGORIES: TaxRefundCategoryId[] = [
  "education",
  "medical",
  "mortgage",
  "charity",
  "other"
];

const BASE_COUNTRY_NOTES: RefundCountryNotes = {
  genericDisclaimer:
    "Расчёт налогового возврата носит ориентировочный характер и не заменяет индивидуальную консультацию или официальный расчёт в налоговой декларации."
};

const COUNTRY_OVERRIDES: Record<
  string,
  {
    enabled?: boolean;
    supportedCategories?: TaxRefundCategoryId[];
    categoryRules?: Partial<Record<TaxRefundCategoryId, RefundCategoryRule>>;
    notes?: RefundCountryNotes;
    /** Локальные подписи категорий для конкретной страны. */
    categoryDisplayLabels?: Record<TaxRefundCategoryId, string>;
  }
> = {
  de: {
    enabled: true,
    supportedCategories: ["education", "medical", "mortgage", "charity", "other"],
    categoryRules: {
      education: { id: "education", rateMultiplier: 1, maxShareOfAnnualTax: 0.5 },
      medical: { id: "medical", rateMultiplier: 1, maxShareOfAnnualTax: 0.7 },
      mortgage: { id: "mortgage", rateMultiplier: 1, maxShareOfAnnualTax: 0.6 },
      charity: { id: "charity", rateMultiplier: 0.9, maxShareOfAnnualTax: 0.4 },
      other: { id: "other", rateMultiplier: 0.7, maxShareOfAnnualTax: 0.3 }
    },
    notes: {
      countrySpecificHint:
        "Для Германии модель особенно хорошо подходит как ориентир по вычетам на обучение, медицину и жильё, но реальные лимиты и условия зависят от вашего статуса и состава семьи."
    },
    categoryDisplayLabels: {
      education: "Обучение (DE, упрощённая оценка)",
      medical: "Медицина (DE, упрощённая оценка)",
      mortgage: "Жильё / ипотека (DE, упрощённая оценка)"
    }
  },
  nl: {
    enabled: true,
    supportedCategories: ["education", "mortgage", "charity", "other"],
    categoryRules: {
      education: { id: "education", rateMultiplier: 0.9, maxShareOfAnnualTax: 0.4 },
      medical: { id: "medical", rateMultiplier: 0.8, maxShareOfAnnualTax: 0.3 },
      mortgage: { id: "mortgage", rateMultiplier: 1, maxShareOfAnnualTax: 0.5 },
      charity: { id: "charity", rateMultiplier: 1, maxShareOfAnnualTax: 0.4 },
      other: { id: "other", rateMultiplier: 0.7, maxShareOfAnnualTax: 0.25 }
    },
    notes: {
      countrySpecificHint:
        "В Нидерландах правила по вычетам зависят от конкретных программ и периода, поэтому модель даёт консервативную оценку по основным категориям."
    },
    categoryDisplayLabels: {
      education: "Обучение (NL, упрощённая оценка)",
      mortgage: "Жильё / ипотека (NL, упрощённая оценка)"
    }
  }
};

export function getRefundCountrySettings(countryId: string): RefundCountrySettings {
  const override = COUNTRY_OVERRIDES[countryId];

  const supportedCategories =
    override?.supportedCategories ?? DEFAULT_SUPPORTED_CATEGORIES;

  const mergedRules: Record<TaxRefundCategoryId, RefundCategoryRule> = {
    ...DEFAULT_CATEGORY_RULES
  };

  if (override?.categoryRules) {
    for (const [key, value] of Object.entries(override.categoryRules)) {
      const id = key as TaxRefundCategoryId;
      mergedRules[id] = {
        ...mergedRules[id],
        ...value,
        id
      };
    }
  }

  return {
    countryId,
    enabled: override?.enabled ?? true,
    supportedCategories,
    categoryRules: mergedRules
  };
}

export function isRefundEnabledForCountry(countryId: string): boolean {
  return getRefundCountrySettings(countryId).enabled;
}

export function getRefundCategoriesForCountry(countryId: string): RefundCategoryDefinition[] {
  const settings = getRefundCountrySettings(countryId);
  const supported = new Set<TaxRefundCategoryId>(settings.supportedCategories);
  return BASE_CATEGORY_DEFINITIONS.filter((def) => supported.has(def.id));
}

export function getRefundCategoryLabel(categoryId: TaxRefundCategoryId | undefined): string | null {
  if (!categoryId) return null;
  const found = BASE_CATEGORY_DEFINITIONS.find((c) => c.id === categoryId);
  return found ? found.label : null;
}

export function getRefundCategoryDisplayLabel(
  countryId: string,
  categoryId: TaxRefundCategoryId | undefined
): string | null {
  if (!categoryId) return null;
  const found = BASE_CATEGORY_DEFINITIONS.find((c) => c.id === categoryId);
  if (!found) return null;
  const countryLabel = found.countryDisplayLabels?.[countryId];
  return countryLabel ?? found.label;
}

export function getRefundCategoryShortDescription(
  categoryId: TaxRefundCategoryId | undefined
): string | null {
  if (!categoryId) return null;
  const found = BASE_CATEGORY_DEFINITIONS.find((c) => c.id === categoryId);
  return found ? found.shortDescription : null;
}

export function getRefundCountryNotes(countryId: string): RefundCountryNotes {
  const override = COUNTRY_OVERRIDES[countryId];
  return {
    ...BASE_COUNTRY_NOTES,
    ...(override?.notes ?? {})
  };
}

export function getRefundCategoryNoteForCountry(
  countryId: string,
  categoryId: TaxRefundCategoryId | undefined
): string | null {
  if (!categoryId) return null;
  const base = BASE_CATEGORY_DEFINITIONS.find((c) => c.id === categoryId);
  if (!base) return null;
  // Пока используем общую заметку по категории; в будущем можно добавить per-country.
  return base.note ?? null;
}

export function buildRefundLimitExplanation(
  estimate: TaxRefundEstimate | null | undefined,
  countryId: string
): string | null {
  if (!estimate || estimate.potentialRefund <= 0) return null;
  const settings = getRefundCountrySettings(countryId);

  if (!settings.enabled) {
    return null;
  }

  if (estimate.cappedByPaidTax) {
    return "Мы ограничиваем оценку возврата суммой уплаченного за год налога и базовыми лимитами по выбранной категории для этой страны, поэтому итоговая цифра остаётся ориентировочной.";
  }

  return "Даже если оценка не упирается в лимиты, фактический возврат зависит от того, какие расходы вы сможете подтвердить в декларации и какие правила действуют в вашей стране.";
}

export interface RefundScenarioSummary {
  hasRefund: boolean;
  categoryId?: TaxRefundCategoryId;
  categoryLabel?: string | null;
  deductibleUsed: number;
  potentialRefund: number;
  annualTaxPaid: number;
  cappedByPaidTax: boolean;
}

export function buildRefundScenarioSummary(
  result: SalaryResult | null | undefined
): RefundScenarioSummary | null {
  if (!result || !result.taxRefundEstimate) return null;
  const estimate: TaxRefundEstimate = result.taxRefundEstimate;
  if (estimate.potentialRefund <= 0) {
    return null;
  }

  return {
    hasRefund: true,
    categoryId: estimate.categoryId,
    categoryLabel: getRefundCategoryLabel(estimate.categoryId),
    deductibleUsed: estimate.deductibleUsed,
    potentialRefund: estimate.potentialRefund,
    annualTaxPaid: estimate.annualTaxPaid,
    cappedByPaidTax: estimate.cappedByPaidTax
  };
}

export interface RefundComparisonSummary {
  baseline: RefundScenarioSummary | null;
  current: RefundScenarioSummary | null;
  /** Положительное значение означает, что во втором сценарии возврат больше. */
  refundDelta: number;
}

export function buildRefundComparisonSummary(
  baselineResult: SalaryResult | null | undefined,
  currentResult: SalaryResult | null | undefined
): RefundComparisonSummary {
  const baseline = buildRefundScenarioSummary(baselineResult);
  const current = buildRefundScenarioSummary(currentResult);

  const baselineRefund = baseline?.potentialRefund ?? 0;
  const currentRefund = current?.potentialRefund ?? 0;

  return {
    baseline,
    current,
    refundDelta: currentRefund - baselineRefund
  };
}

export type RefundScenarioPresetId =
  | "no-deduction"
  | "education-deduction"
  | "medical-deduction"
  | "housing-deduction";

export interface RefundScenarioPreset {
  id: RefundScenarioPresetId;
  label: string;
  description: string;
  categoryId?: TaxRefundCategoryId;
  /** Рекомендуемая ориентировочная годовая сумма вычетов для быстрого старта. */
  defaultDeductible?: number;
}

export function getRefundScenarioPresets(countryId: string): RefundScenarioPreset[] {
  const settings = getRefundCountrySettings(countryId);
  const categories = new Set<TaxRefundCategoryId>(settings.supportedCategories);

  const presets: RefundScenarioPreset[] = [
    {
      id: "no-deduction",
      label: "Без вычетов",
      description: "Базовый сценарий без учёта налоговых вычетов."
    }
  ];

  if (categories.has("education")) {
    presets.push({
      id: "education-deduction",
      label: "Образовательный вычет",
      description: "Ориентировочный вычет на обучение и повышение квалификации.",
      categoryId: "education",
      defaultDeductible: 2000
    });
  }

  if (categories.has("medical")) {
    presets.push({
      id: "medical-deduction",
      label: "Медицинский вычет",
      description: "Ориентировочный вычет на медицинские услуги и страховку.",
      categoryId: "medical",
      defaultDeductible: 1500
    });
  }

  if (categories.has("mortgage")) {
    presets.push({
      id: "housing-deduction",
      label: "Жильё / ипотека",
      description: "Ориентировочный вычет по процентам по ипотеке и расходам на жильё.",
      categoryId: "mortgage",
      defaultDeductible: 4000
    });
  }

  return presets;
}


