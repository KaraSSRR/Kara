/** Период: месячный или годовой */
export type SalaryPeriod = "monthly" | "yearly";

/** Направление расчёта */
export type CalculationDirection = "gross-to-net" | "net-to-gross";

/** Категория потенциального налогового вычета (foundation для будущих правил) */
export type TaxRefundCategoryId =
  | "education" // обучение
  | "medical" // медицина
  | "mortgage" // ипотека / жильё
  | "charity" // благотворительность
  | "other"; // другие категории

/** Входные данные для оценки налогового возврата */
export interface TaxRefundInput {
  /** Оценочная годовая сумма расходов, дающих право на вычет */
  deductibleAmount: number;
  /** Опциональная категория вычета — влияет только на интерпретацию, не на формулу MVP */
  categoryId?: TaxRefundCategoryId;
  /** Налоговый год, к которому относится вычет (по умолчанию — текущий год) */
  year?: number;
}

/** Оценка потенциального налогового возврата за год */
export interface TaxRefundEstimate {
  /** Общая сумма налогов за год, учтённая при расчёте (налог + взносы) */
  annualTaxPaid: number;
  /** Часть годового налога, которую мы считаем потенциально возвращаемой (например, подоходный налог) */
  refundableTaxBase: number;
  /** Годовая сумма вычета, которая реально попала в расчёт (после ограничений) */
  deductibleUsed: number;
  /** Ориентировочная сумма возможного возврата за год */
  potentialRefund: number;
  /** Флаг, что расчёт был «обрезан» по уплаченному налогу */
  cappedByPaidTax: boolean;
  /** Категория вычета — для будущих пояснений и UI */
  categoryId?: TaxRefundCategoryId;
}

/** Вход калькулятора */
export interface SalaryInput {
  amount: number;
  period: SalaryPeriod;
  direction: CalculationDirection;
  /** Опциональный блок с параметрами налогового вычета для оценки потенциального возврата */
  refund?: TaxRefundInput | null;
}

/** Одна строка разбивки (налог или взнос) */
export interface TaxBreakdownItem {
  label: string;
  amount: number;
  rate?: number;
}

/** Результат расчёта */
export interface SalaryResult {
  gross: number;
  net: number;
  totalTax: number;
  effectiveTaxRate: number;
  breakdown: TaxBreakdownItem[];
  employerCost: number;
  /** Оценка общей суммы налогов за год (даже если расчёт велся в месячном режиме) */
  annualTaxPaid: number;
  /** Оценка потенциального налогового возврата за год (если задан вычет) */
  taxRefundEstimate?: TaxRefundEstimate | null;
  period: SalaryPeriod;
}

/** Налоговая скобка (прогрессия) */
export interface TaxBracket {
  from: number;
  to?: number;
  rate: number;
}

/** Конфиг социальных взносов: доля от gross в пределах базы */
export interface SocialConfig {
  label: string;
  rate: number;
  maxBase?: number;
}

/** Правила для одной страны/года */
export interface TaxConfig {
  id: string;
  countryId: string;
  year: number;
  incomeTaxBrackets: TaxBracket[];
  socialContributions: SocialConfig[];
  /** Доля работодателя (employer cost = gross * (1 + employerRate)) */
  employerContributionRate: number;
}
