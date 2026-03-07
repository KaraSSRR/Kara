import type {
  TaxConfig,
  SalaryInput,
  SalaryResult,
  TaxBreakdownItem,
  SalaryPeriod,
  TaxRefundEstimate
} from "./types";
import { getRefundCountrySettings } from "./refund-config";

function annualize(amount: number, period: SalaryPeriod): number {
  return period === "yearly" ? amount : amount * 12;
}

function toPeriod(annual: number, period: SalaryPeriod): number {
  return period === "yearly" ? annual : annual / 12;
}

function estimateTaxRefund(
  annualGross: number,
  incomeTax: number,
  annualTaxPaid: number,
  refundInput: SalaryInput["refund"],
  countryId: string
): TaxRefundEstimate | null {
  if (!refundInput) return null;

  const settings = getRefundCountrySettings(countryId);
  if (!settings.enabled) {
    return null;
  }

  const rawDeductible = refundInput.deductibleAmount;
  if (!Number.isFinite(rawDeductible) || rawDeductible <= 0) {
    return null;
  }

  // В MVP считаем, что вычет уменьшает налоговую базу по подоходному налогу.
  // Используем эффективную ставку по подоходному налогу как приближение к маржинальной.
  const effectiveIncomeTaxRate = annualGross > 0 ? incomeTax / annualGross : 0;
  if (effectiveIncomeTaxRate <= 0 || incomeTax <= 0) {
    return null;
  }

  const refundableTaxBase = incomeTax;
  const deductibleUsed = Math.max(0, rawDeductible);

  const categoryId = refundInput.categoryId ?? "other";
  const categoryRule = settings.categoryRules[categoryId] ?? settings.categoryRules.other;

  const rateMultiplier = categoryRule.rateMultiplier ?? 1;
  const rawRefund = deductibleUsed * effectiveIncomeTaxRate * rateMultiplier;

  const maxByPaidTax = Math.min(refundableTaxBase, annualTaxPaid);
  const maxByCategory =
    categoryRule.maxShareOfAnnualTax != null
      ? annualTaxPaid * categoryRule.maxShareOfAnnualTax
      : annualTaxPaid;

  const maxRefundCap = Math.min(maxByPaidTax, maxByCategory);

  const potentialRefund = Math.max(0, Math.min(rawRefund, maxRefundCap));

  return {
    annualTaxPaid,
    refundableTaxBase,
    deductibleUsed,
    potentialRefund,
    cappedByPaidTax: potentialRefund < rawRefund,
    categoryId
  };
}

function calcIncomeTax(annualGross: number, brackets: TaxConfig["incomeTaxBrackets"]): number {
  let tax = 0;
  for (const b of brackets) {
    const from = b.from;
    const to = b.to ?? Infinity;
    if (annualGross <= from) break;
    const taxableInBracket = Math.min(annualGross, to) - from;
    if (taxableInBracket > 0) {
      tax += taxableInBracket * b.rate;
    }
    if (annualGross <= to) break;
  }
  return tax;
}

function calcSocial(annualGross: number, config: TaxConfig): TaxBreakdownItem[] {
  const items: TaxBreakdownItem[] = [];
  let total = 0;
  for (const s of config.socialContributions) {
    const base = s.maxBase != null ? Math.min(annualGross, s.maxBase) : annualGross;
    const amount = base * s.rate;
    total += amount;
    items.push({ label: s.label, amount, rate: s.rate });
  }
  return items;
}

/**
 * Gross → Net по конфигу. Сумма в input считается в выбранном периоде (monthly/yearly).
 */
export function grossToNet(input: SalaryInput, config: TaxConfig): SalaryResult {
  const annualGross = annualize(input.amount, input.period);
  const incomeTax = calcIncomeTax(annualGross, config.incomeTaxBrackets);
  const socialItems = calcSocial(annualGross, config);
  const totalSocial = socialItems.reduce((s, i) => s + i.amount, 0);
  const annualTaxPaid = incomeTax + totalSocial;
  const annualNet = annualGross - annualTaxPaid;
  const employerCost = annualGross * (1 + config.employerContributionRate);

  const breakdown: TaxBreakdownItem[] = [
    { label: "Income tax", amount: incomeTax, rate: incomeTax / annualGross }
  ].concat(socialItems);

  const taxRefundEstimate = estimateTaxRefund(
    annualGross,
    incomeTax,
    annualTaxPaid,
    input.refund,
    config.countryId
  );

  return {
    gross: toPeriod(annualGross, input.period),
    net: toPeriod(annualNet, input.period),
    totalTax: toPeriod(annualTaxPaid, input.period),
    effectiveTaxRate: annualTaxPaid / annualGross,
    breakdown: breakdown.map((b) => ({
      ...b,
      amount: toPeriod(b.amount, input.period)
    })),
    employerCost: toPeriod(employerCost, input.period),
    annualTaxPaid,
    taxRefundEstimate,
    period: input.period
  };
}

/**
 * Net → Gross: итеративно подбираем gross так, чтобы net совпадал с введённой суммой.
 */
export function netToGross(input: SalaryInput, config: TaxConfig): SalaryResult {
  const targetAnnualNet = annualize(input.amount, input.period);
  let low = targetAnnualNet;
  let high = targetAnnualNet * 2.5;
  for (let i = 0; i < 50; i++) {
    const mid = (low + high) / 2;
    const res = grossToNet(
      { amount: mid, period: "yearly", direction: "gross-to-net", refund: null },
      config
    );
    if (Math.abs(res.net - targetAnnualNet) < 1) {
      return grossToNet(
        { amount: input.period === "yearly" ? mid : mid / 12, period: input.period, direction: "gross-to-net" },
        config
      );
    }
    if (res.net < targetAnnualNet) low = mid;
    else high = mid;
  }
  const finalGross = (low + high) / 2;
  return grossToNet(
    {
      amount: input.period === "yearly" ? finalGross : finalGross / 12,
      period: input.period,
      direction: "gross-to-net",
      refund: input.refund ?? null
    },
    config
  );
}

export function calculate(input: SalaryInput, config: TaxConfig): SalaryResult {
  return input.direction === "gross-to-net"
    ? grossToNet(input, config)
    : netToGross(input, config);
}
