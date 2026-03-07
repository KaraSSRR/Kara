"use client";

import { useState, useMemo, useEffect } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { calculate } from "@/lib/calculations";
import { getTaxConfigByCountryId } from "@/lib/calculations/config";
import { formatCurrency, formatPercent } from "@/lib/calculations/formatters";
import {
  getRefundCategoriesForCountry,
  getRefundCategoryDisplayLabel,
  getRefundCountryNotes,
  getRefundScenarioPresets,
  isRefundEnabledForCountry,
  buildRefundLimitExplanation
} from "@/lib/calculations/refund-config";
import {
  buildRefundScenarioPayload,
  buildRefundScenarioSummaryLine,
  buildScenarioAwareSharePayload,
  getSavedScenarioInterpretationText,
  getSavedScenariosLocalityHint,
  getScenarioManagementHint,
  getPinnedScenariosHint,
  getWhenToSaveScenarioHint,
  getWhenToUsePresetHint,
  type SavedRefundScenario
} from "@/lib/calculations/refund-scenarios";
import {
  buildAppliedScenarioInputs,
  duplicateSavedScenarioInList,
  getApplyScenarioIncompatibleMessage,
  getApplyScenarioSuccessMessage,
  isScenarioValid,
  removeSavedScenarioFromList,
  renameSavedScenarioInList,
  saveCurrentRefundScenario
} from "@/lib/calculations/refund-scenario-manager";
import {
  getSavedScenariosForCountry
} from "@/lib/calculations/scenario-library-foundation";
import { SavedScenariosPanel } from "@/components/scenarios/SavedScenariosPanel";
import { useSavedRefundScenariosSync } from "@/lib/hooks/useSavedRefundScenariosSync";
import { usePinnedScenarioIds } from "@/lib/hooks/usePinnedScenarioIds";
import { trackCalculatorEvent } from "@/lib/analytics/events";
import type {
  SalaryPeriod,
  CalculationDirection,
  TaxRefundCategoryId
} from "@/lib/calculations/types";
import type { SeoRouteType } from "@/lib/seo/decisions";
import { ComparisonSnapshotCard } from "@/components/results/ComparisonSnapshotCard";
import { getResultExplanation } from "@/lib/data/result-explanations";
import { SingleResultShareActions } from "@/components/results/SingleResultShareActions";

interface SalaryCalculatorProps {
  /** Режим по умолчанию */
  defaultMode?: CalculationDirection;
  /** Тип маршрута для пресетов/объяснений/аналитики */
  routeType?: SeoRouteType;
  /** Страна для налогового конфига (по умолчанию de) */
  countryId?: string;
  /** Символ валюты для отображения */
  currencySymbol?: string;
  /** Локаль для форматирования (например de-DE, nl-NL). Если не задана — выводится по countryId. */
  locale?: string;
  /** Код валюты (например EUR). Если не задан — выводится по countryId. */
  currencyCode?: string;
  /** Контекст страницы для подсказок (город/профессия) */
  contextLabel?: string;
  /** Дополнительный текст-подсказка под формой, зависящий от маршрута */
  helperText?: string;
  /** Подсказка про сценарии сравнения/сохранения результата */
  comparisonHint?: string;
  /** Callback для будущих сценариев шаринга/печати результатов */
  onResultChange?: (summary: {
    gross: number;
    net: number;
    totalTax: number;
    effectiveTaxRate: number;
    employerCost: number;
  } | null) => void;
}

export function SalaryCalculator({
  defaultMode = "gross-to-net",
  countryId = "de",
  currencySymbol = "€",
  locale: localeProp,
  currencyCode: currencyCodeProp,
  routeType = "generic-calculator",
  contextLabel,
  helperText,
  comparisonHint,
  onResultChange
}: SalaryCalculatorProps) {
  const searchParams = useSearchParams();
  const pathname = usePathname();
  const router = useRouter();

  const [amountInput, setAmountInput] = useState<string>("5000");
  const [period, setPeriod] = useState<SalaryPeriod>("monthly");
  const [direction, setDirection] = useState<CalculationDirection>(defaultMode);
  const [error, setError] = useState<string | null>(null);
  const [deductibleInput, setDeductibleInput] = useState<string>("");
  const [refundCategoryId, setRefundCategoryId] = useState<TaxRefundCategoryId>("other");
  const [baselineSummary, setBaselineSummary] = useState<{
    label: string;
    gross: number;
    net: number;
    totalTax: number;
    effectiveTaxRate: number;
  } | null>(null);
  const [savedRefundScenarios, setSavedRefundScenarios] = useSavedRefundScenariosSync();
  const [pinnedIds, setPinnedIds] = usePinnedScenarioIds();
  const [scenarioMessage, setScenarioMessage] = useState<string | null>(null);

  useEffect(() => {
    const amountParam = searchParams.get("amount");
    const modeParam = searchParams.get("mode");
    const periodParam = searchParams.get("period");

    if (amountParam) {
      setAmountInput(amountParam);
    }
    if (modeParam === "gross-to-net" || modeParam === "net-to-gross") {
      setDirection(modeParam);
    }
    if (periodParam === "monthly" || periodParam === "yearly") {
      setPeriod(periodParam);
    }
  }, [searchParams]);

  const config = useMemo(
    () => getTaxConfigByCountryId(countryId),
    [countryId]
  );

  const refundCategories = useMemo(
    () => getRefundCategoriesForCountry(countryId),
    [countryId]
  );

  const refundPresets = useMemo(
    () => getRefundScenarioPresets(countryId),
    [countryId]
  );

  const refundEnabled = useMemo(
    () => isRefundEnabledForCountry(countryId),
    [countryId]
  );

  const refundNotes = useMemo(
    () => getRefundCountryNotes(countryId),
    [countryId]
  );

  const parsedAmount = useMemo(() => {
    if (!amountInput) {
      setError("Введите сумму");
      return null;
    }
    const normalized = amountInput.replace(",", ".");
    const value = Number(normalized);
    if (!Number.isFinite(value) || value <= 0) {
      setError("Сумма должна быть больше нуля");
      return null;
    }
    if (value > 1_000_000) {
      setError("Сумма слишком велика для расчёта");
      return null;
    }
    setError(null);
    return value;
  }, [amountInput]);

  const parsedDeductible = useMemo(() => {
    if (!deductibleInput) return null;
    const normalized = deductibleInput.replace(",", ".");
    const value = Number(normalized);
    if (!Number.isFinite(value) || value <= 0) {
      return null;
    }
    if (value > 1_000_000) {
      return 1_000_000;
    }
    return value;
  }, [deductibleInput]);

  const savedScenariosForCountry = useMemo(
    () => getSavedScenariosForCountry(savedRefundScenarios, countryId, pinnedIds),
    [savedRefundScenarios, countryId, pinnedIds]
  );

  useEffect(() => {
    if (!pathname) return;
    const params = new URLSearchParams(searchParams.toString());

    if (parsedAmount != null) {
      params.set("amount", String(parsedAmount));
    } else {
      params.delete("amount");
    }
    params.set("mode", direction);
    params.set("period", period);

    const query = params.toString();
    const url = query ? `${pathname}?${query}` : pathname;
    router.replace(url, { scroll: false });
  }, [parsedAmount, direction, period, pathname, router, searchParams]);

  const result = useMemo(() => {
    if (!config || parsedAmount == null) return null;
    const refund =
      refundEnabled && parsedDeductible != null
        ? {
            deductibleAmount: parsedDeductible,
            year: new Date().getFullYear(),
            categoryId: refundCategoryId
          }
        : undefined;
    const computed = calculate(
      { amount: parsedAmount, period, direction, refund },
      config
    );
    trackCalculatorEvent({
      name: "calculator_used",
      routeType,
      mode: direction,
      period,
      amount: parsedAmount,
      hasBaseline: Boolean(baselineSummary)
    });
    return computed;
  }, [config, parsedAmount, period, direction, routeType, baselineSummary]);

  useEffect(() => {
    if (!onResultChange) return;
    if (!result) {
      onResultChange(null);
      return;
    }
    onResultChange({
      gross: result.gross,
      net: result.net,
      totalTax: result.totalTax,
      effectiveTaxRate: result.effectiveTaxRate,
      employerCost: result.employerCost
    });
  }, [onResultChange, result]);

  const currency = currencyCodeProp ?? (config?.countryId === "nl" ? "EUR" : "EUR");
  const locale = localeProp ?? (config?.countryId === "nl" ? "nl-NL" : "de-DE");

  const handleCopyLink = async () => {
    if (typeof window === "undefined") return;
    await navigator.clipboard.writeText(window.location.href);
    trackCalculatorEvent({
      name: "link_copied",
      routeType,
      mode: direction,
      period,
      amount: parsedAmount ?? null
    });
  };

  const handleCopySummary = async () => {
    if (!result) return;
    const summary = [
      `Gross: ${formatCurrency(result.gross, currency)}`,
      `Net: ${formatCurrency(result.net, currency)}`,
      `Taxes: ${formatCurrency(result.totalTax, currency)}`,
      `Effective rate: ${formatPercent(result.effectiveTaxRate)}`
    ].join("\n");
    await navigator.clipboard.writeText(summary);
    trackCalculatorEvent({
      name: "result_copied",
      routeType,
      mode: direction,
      period,
      amount: parsedAmount ?? null
    });
  };

  const handleSaveBaseline = () => {
    if (!result) return;
    setBaselineSummary({
      label:
        direction === "gross-to-net"
          ? "Сценарий Gross → Net"
          : "Сценарий Net → Gross",
      gross: result.gross,
      net: result.net,
      totalTax: result.totalTax,
      effectiveTaxRate: result.effectiveTaxRate
    });
    trackCalculatorEvent({
      name: "comparison_saved",
      routeType,
      mode: direction,
      period,
      amount: parsedAmount ?? null,
      baselineGross: result.gross,
      baselineNet: result.net
    });
  };

  const handleClearBaseline = () => {
    setBaselineSummary(null);
  };

  const handleReset = () => {
    setAmountInput("5000");
    setPeriod("monthly");
    setDirection(defaultMode);
    setError(null);
    setDeductibleInput("");
    setRefundCategoryId("other");
  };

  const handleSaveRefundScenario = () => {
    if (!refundEnabled || !parsedDeductible || parsedDeductible <= 0) {
      setScenarioMessage("Укажите годовую сумму вычета, чтобы сохранить сценарий.");
      return;
    }
    let lastError: string | undefined;
    setSavedRefundScenarios((prev) => {
      const { scenarios, error } = saveCurrentRefundScenario({
        list: prev,
        countryId,
        categoryId: refundCategoryId,
        deductibleAmount: parsedDeductible
      });
      lastError = error;
      return scenarios;
    });
    if (lastError) {
      setScenarioMessage(lastError);
    } else {
      setScenarioMessage("Сценарий сохранён для повторного использования.");
    }
  };

  const activeRefundScenario = useMemo(() => {
    if (!refundEnabled || !parsedDeductible || parsedDeductible <= 0) return null;
    const base = buildRefundScenarioPayload({
      countryId,
      categoryId: refundCategoryId,
      deductibleAmount: parsedDeductible
    });
    const savedMatch = savedScenariosForCountry.find(
      (s) =>
        s.categoryId === base.categoryId &&
        Math.round(s.deductibleAmount) === Math.round(base.deductibleAmount)
    );
    return savedMatch ?? base;
  }, [refundEnabled, parsedDeductible, countryId, refundCategoryId, savedScenariosForCountry]);

  return (
    <div className="rounded-2xl border border-slate-800/80 bg-slate-900/70 p-5 shadow-card backdrop-blur">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 className="text-sm font-semibold text-slate-100">
          Калькулятор зарплаты
        </h2>
        {contextLabel && (
          <span className="text-[11px] text-slate-400">{contextLabel}</span>
        )}
      </div>

      <div className="space-y-4">
        <div>
          <label className="mb-1 block text-[11px] font-medium text-slate-400">
            Сумма ({direction === "gross-to-net" ? "gross" : "net"})
          </label>
          <input
            type="number"
            min={0}
            step={100}
            value={amountInput}
            onChange={(e) => setAmountInput(e.target.value)}
            className="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="5000"
          />
        </div>
        {error && (
          <p className="rounded-lg border border-amber-600/30 bg-amber-950/30 px-3 py-2 text-xs text-amber-200/90" role="alert">
            {error}
          </p>
        )}

        <div className="space-y-1 text-[11px] text-slate-500">
          <p>
            {helperText ??
              "Укажите сумму и период, чтобы оценить зарплату до и после налогов. Вы можете поделиться результатом по ссылке."}
          </p>
          {comparisonHint && (
            <p className="text-[10px] text-slate-500/90">
              {comparisonHint}
            </p>
          )}
        </div>

        <div className="flex flex-wrap gap-3">
          <div>
            <span className="mb-1 block text-[11px] font-medium text-slate-400">
              Режим
            </span>
            <select
              value={direction}
              onChange={(e) => setDirection(e.target.value as CalculationDirection)}
              className="rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none"
            >
              <option value="gross-to-net">Gross → Net</option>
              <option value="net-to-gross">Net → Gross</option>
            </select>
          </div>
          <div>
            <span className="mb-1 block text-[11px] font-medium text-slate-400">
              Период
            </span>
            <select
              value={period}
              onChange={(e) => setPeriod(e.target.value as SalaryPeriod)}
              className="rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none"
            >
              <option value="monthly">В месяц</option>
              <option value="yearly">В год</option>
            </select>
          </div>
        </div>
        {refundEnabled && (
          <div className="space-y-2 rounded-xl border border-slate-800/80 bg-slate-950/70 p-3">
            <div className="flex flex-wrap gap-3">
              <div className="min-w-[140px] flex-1">
                <label className="mb-1 block text-[11px] font-medium text-slate-400">
                  Категория вычета
                </label>
                <select
                  value={refundCategoryId}
                  onChange={(e) => setRefundCategoryId(e.target.value as TaxRefundCategoryId)}
                  className="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none"
                >
                  {refundCategories.map((category) => (
                    <option key={category.id} value={category.id}>
                        {getRefundCategoryDisplayLabel(countryId, category.id)}
                    </option>
                  ))}
                </select>
              </div>
              <div className="min-w-[140px] flex-1">
                <label className="mb-1 block text-[11px] font-medium text-slate-400">
                  Сумма вычета в год (опционально)
                </label>
                <input
                  type="number"
                  min={0}
                  step={100}
                  value={deductibleInput}
                  onChange={(e) => setDeductibleInput(e.target.value)}
                  className="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="Например, 2000"
                />
              </div>
            </div>
            {refundPresets.length > 1 && (
              <div className="flex flex-wrap items-center gap-2">
                <span className="text-[10px] text-slate-500">Быстрые сценарии:</span>
                {refundPresets.map((preset) => (
                  <button
                    key={preset.id}
                    type="button"
                    onClick={() => {
                      if (preset.id === "no-deduction") {
                        setDeductibleInput("");
                        setRefundCategoryId("other");
                        return;
                      }
                      if (preset.categoryId) {
                        setRefundCategoryId(preset.categoryId);
                      }
                      if (preset.defaultDeductible != null) {
                        setDeductibleInput(String(preset.defaultDeductible));
                      }
                    }}
                    className="rounded-full border border-slate-700 bg-slate-950/80 px-2.5 py-0.5 text-[10px] text-slate-200 hover:border-slate-500"
                  >
                    {preset.label}
                  </button>
                ))}
              </div>
            )}
            <p className="text-[10px] text-slate-500">
              Мы используем указанную категорию и годовую сумму вычетов, чтобы оценить возможный
              возврат налога. Модель упрощена: реальные лимиты и правила зависят от страны,
              статуса и конкретной программы, поэтому результат носит ориентировочный характер.
            </p>
            <p className="text-[10px] text-slate-500">
              {getSavedScenarioInterpretationText(countryId)}
            </p>
            {savedScenariosForCountry.length > 0 && (
              <p className="text-[10px] text-slate-500">{getSavedScenariosLocalityHint()}</p>
            )}
            <p className="text-[10px] text-slate-500">{getScenarioManagementHint()}</p>
            <p className="text-[10px] text-slate-500">{getWhenToSaveScenarioHint()}</p>
            <p className="text-[10px] text-slate-500">{getWhenToUsePresetHint()}</p>
            {pinnedIds.length > 0 && (
              <p className="text-[10px] text-slate-500">{getPinnedScenariosHint()}</p>
            )}
            <div className="mt-2 space-y-1">
              <button
                type="button"
                onClick={() => {
                  setRefundCategoryId(refundCategories[0]?.id ?? "other");
                  setDeductibleInput(
                    String(refundPresets.find((p) => p.defaultDeductible != null)?.defaultDeductible ?? 2000)
                  );
                }}
                className="rounded-full border border-slate-800 bg-transparent px-2 py-0.5 text-[10px] text-slate-500 hover:border-slate-600 hover:text-slate-100"
              >
                Сбросить к пресету
              </button>
              <SavedScenariosPanel
                scenarios={savedScenariosForCountry}
                countryId={countryId}
                activeScenarioId={activeRefundScenario?.origin === "saved" ? activeRefundScenario.id : null}
                pinnedIds={pinnedIds}
                scenarioMessage={scenarioMessage}
                onSave={handleSaveRefundScenario}
                onTogglePin={(scenario) => {
                  setPinnedIds((prev) =>
                    prev.includes(scenario.id)
                      ? prev.filter((id) => id !== scenario.id)
                      : [...prev, scenario.id]
                  );
                }}
                onApply={(scenario) => {
                  const applied = buildAppliedScenarioInputs(scenario);
                  setRefundCategoryId(applied.categoryId);
                  setDeductibleInput(applied.deductibleInput);
                  setScenarioMessage(getApplyScenarioSuccessMessage(scenario, countryId));
                }}
                onRename={(scenario, newLabel) => {
                  let lastError: string | undefined;
                  setSavedRefundScenarios((prev) => {
                    const { scenarios, error } = renameSavedScenarioInList({
                      list: prev,
                      id: scenario.id,
                      newLabel
                    });
                    lastError = error;
                    return scenarios;
                  });
                  setScenarioMessage(lastError ?? null);
                }}
                onDuplicate={(scenario) => {
                  let lastError: string | undefined;
                  setSavedRefundScenarios((prev) => {
                    const { scenarios, error } = duplicateSavedScenarioInList({
                      list: prev,
                      id: scenario.id
                    });
                    lastError = error;
                    return scenarios;
                  });
                  setScenarioMessage(lastError ?? null);
                }}
                onRemove={(scenario) => {
                  setSavedRefundScenarios((prev) => {
                    const { scenarios } = removeSavedScenarioFromList({
                      list: prev,
                      id: scenario.id
                    });
                    return scenarios;
                  });
                  setScenarioMessage(null);
                }}
                isScenarioValid={isScenarioValid}
                getApplySuccessMessage={(s) => getApplyScenarioSuccessMessage(s, countryId)}
                getIncompatibleMessage={getApplyScenarioIncompatibleMessage}
              />
            </div>
          </div>
        )}
        {!refundEnabled && (
          <div className="rounded-xl border border-slate-800/80 bg-slate-950/60 p-3 text-[11px] text-slate-400">
            <p>
              Оценка налогового возврата для выбранной страны пока недоступна в калькуляторе, но
              вы всё равно можете использовать его для расчёта gross и net.
            </p>
            {refundNotes.countrySpecificHint && (
              <p className="mt-1 text-[10px] text-slate-500">
                {refundNotes.countrySpecificHint}
              </p>
            )}
          </div>
        )}
      </div>

      {!result && !error && (
        <p className="mt-4 text-center text-xs text-slate-500">
          Введите сумму и параметры для расчёта
        </p>
      )}

      {result && (
        <div className="mt-5 space-y-3 border-t border-slate-800/80 pt-4 print:border-0 print:pt-0">
          <div className="grid grid-cols-2 gap-2 text-xs">
            <div className="rounded-lg bg-slate-950/80 px-3 py-2">
              <span className="text-slate-400">Gross</span>
              <p className="font-medium text-slate-100">
                {formatCurrency(result.gross, currency, locale)}
              </p>
            </div>
            <div className="rounded-lg bg-slate-950/80 px-3 py-2">
              <span className="text-slate-400">Net</span>
              <p className="font-medium text-emerald-400">
                {formatCurrency(result.net, currency, locale)}
              </p>
            </div>
          </div>
          <div className="flex justify-between text-[11px] text-slate-400">
            <span>Налоги и взносы</span>
            <span>{formatCurrency(result.totalTax, currency, locale)}</span>
          </div>
          <div className="flex justify-between text-[11px] text-slate-400">
            <span>Эффективная ставка</span>
            <span>{formatPercent(result.effectiveTaxRate, locale)}</span>
          </div>
          <div className="flex justify-between text-[11px] text-slate-400">
            <span>Затраты работодателя</span>
            <span>{formatCurrency(result.employerCost, currency, locale)}</span>
          </div>
          <div className="flex justify-between text-[11px] text-slate-400">
            <span>
              Налоги за год
              {period === "monthly" ? " (оценка)" : ""}
            </span>
            <span>{formatCurrency(result.annualTaxPaid, currency, locale)}</span>
          </div>
          {result.taxRefundEstimate && result.taxRefundEstimate.potentialRefund > 0 && (
            <div className="mt-2 rounded-lg border border-emerald-700/40 bg-emerald-950/30 px-3 py-2 text-[11px]">
              <div className="flex items-baseline justify-between gap-2">
                <span className="font-medium text-emerald-200">
                  Возможный налоговый возврат (оценка)
                </span>
                <span className="font-semibold text-emerald-300">
                  {formatCurrency(result.taxRefundEstimate.potentialRefund, currency, locale)}
                </span>
              </div>
              <p className="mt-1 text-[10px] text-emerald-200/80">
                Категория:{" "}
                {getRefundCategoryDisplayLabel(
                  countryId,
                  result.taxRefundEstimate.categoryId
                ) ?? "другое"}
                .
                В расчёт попала годовая сумма вычета{" "}
                {formatCurrency(result.taxRefundEstimate.deductibleUsed, currency, locale)}.
              </p>
              {buildRefundLimitExplanation(result.taxRefundEstimate, countryId) && (
                <p className="mt-1 text-[10px] text-emerald-200/80">
                  {buildRefundLimitExplanation(result.taxRefundEstimate, countryId)}
                </p>
              )}
              {refundNotes.genericDisclaimer && (
                <p className="mt-1 text-[10px] text-emerald-200/70">
                  {refundNotes.genericDisclaimer}
                </p>
              )}
            </div>
          )}
          {result.breakdown.length > 0 && (
            <details className="mt-2">
              <summary className="cursor-pointer text-[11px] text-slate-400 hover:text-slate-300">
                Разбивка
              </summary>
              <ul className="mt-1 space-y-1 pl-2 text-[11px] text-slate-500">
                {result.breakdown.map((b, i) => (
                  <li key={i}>
                    {b.label}: {formatCurrency(b.amount, currency, locale)}
                  </li>
                ))}
              </ul>
            </details>
          )}

          <div className="mt-3 flex flex-wrap gap-2 text-[11px]">
            <button
              type="button"
              onClick={handleCopyLink}
              className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-200 hover:border-slate-500"
            >
              Скопировать ссылку
            </button>
            <button
              type="button"
              onClick={handleCopySummary}
              className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-200 hover:border-slate-500"
            >
              Скопировать результат
            </button>
            <button
              type="button"
              onClick={handleReset}
              className="rounded-full border border-slate-800 bg-transparent px-3 py-1 text-slate-400 hover:border-slate-600 hover:text-slate-100"
            >
              Сброс
            </button>
            <button
              type="button"
              onClick={handleSaveBaseline}
              className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-slate-200 hover:border-slate-500"
            >
              Сохранить сценарий для сравнения
            </button>
            {baselineSummary && (
              <button
                type="button"
                onClick={handleClearBaseline}
                className="rounded-full border border-slate-800 bg-transparent px-3 py-1 text-slate-400 hover:border-slate-600 hover:text-slate-100"
              >
                Очистить сравнение
              </button>
            )}
          </div>

          {baselineSummary && (
            <ComparisonSnapshotCard
              baseline={{
                label: baselineSummary.label,
                gross: formatCurrency(baselineSummary.gross, currency, locale),
                net: formatCurrency(baselineSummary.net, currency, locale),
                totalTax: formatCurrency(baselineSummary.totalTax, currency, locale),
                effectiveTaxRate: formatPercent(baselineSummary.effectiveTaxRate, locale)
              }}
              current={{
                label:
                  direction === "gross-to-net"
                    ? "Сценарий Gross → Net"
                    : "Сценарий Net → Gross",
                gross: formatCurrency(result.gross, currency, locale),
                net: formatCurrency(result.net, currency, locale),
                totalTax: formatCurrency(result.totalTax, currency, locale),
                effectiveTaxRate: formatPercent(result.effectiveTaxRate, locale)
              }}
            />
          )}

          {(() => {
            const explanation = getResultExplanation({
              routeType,
              mode: direction,
              hasComparison: Boolean(baselineSummary)
            });
            return (
              <section className="mt-3 rounded-lg border border-slate-800/80 bg-slate-950/70 p-3 text-[11px] text-slate-200">
                <h3 className="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                  {explanation.title}
                </h3>
                <ul className="list-disc space-y-1 pl-4">
                  {explanation.bullets.map((item, idx) => (
                    <li key={idx}>{item}</li>
                  ))}
                </ul>
              </section>
            );
          })()}

          <SingleResultShareActions
            routeType={routeType}
            mode={direction}
            period={period}
            currencyCode={currency}
            locale={locale}
            contextLabel={contextLabel}
            result={result}
            scenarioPayload={
              activeRefundScenario
                ? buildScenarioAwareSharePayload({
                    scenario: activeRefundScenario,
                    countryId,
                    currencyCode: currency,
                    locale
                  })
                : null
            }
          />
        </div>
      )}

      {!config && (
        <p className="mt-3 text-[11px] text-amber-500/90">
          Налоговый конфиг для выбранной страны пока недоступен.
        </p>
      )}
    </div>
  );
}
