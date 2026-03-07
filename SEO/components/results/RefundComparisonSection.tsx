"use client";

import { useEffect, useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { calculate } from "@/lib/calculations";
import { getTaxConfigByCountryId } from "@/lib/calculations/config";
import { formatCurrency, formatPercent } from "@/lib/calculations/formatters";
import type {
  CalculationDirection,
  SalaryPeriod,
  TaxRefundCategoryId,
  SalaryResult
} from "@/lib/calculations/types";
import {
  buildRefundComparisonSummary,
  getRefundCategoriesForCountry,
  getRefundCategoryDisplayLabel,
  getRefundCountryNotes,
  getRefundScenarioPresets,
  isRefundEnabledForCountry,
  buildRefundLimitExplanation
} from "@/lib/calculations/refund-config";
import {
  buildRefundScenarioPayload,
  getSavedScenarioInterpretationText,
  getSavedScenariosLocalityHint,
  getScenarioManagementHint,
  getPinnedScenariosHint,
  getWhenScenarioForComparisonHint,
  buildComparisonRefundScenarioSummary,
  buildScenarioShareSummary,
  getScenarioComparisonHeadingLabel,
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
import { getSavedScenariosForCountry } from "@/lib/calculations/scenario-library-foundation";
import { SavedScenariosPanel } from "@/components/scenarios/SavedScenariosPanel";
import { useSavedRefundScenariosSync } from "@/lib/hooks/useSavedRefundScenariosSync";
import { usePinnedScenarioIds } from "@/lib/hooks/usePinnedScenarioIds";

type RefundScenarioMode = "none-vs-refund" | "category-vs-category";

interface RefundComparisonSectionProps {
  countryId: string;
  currencyCode: string;
  locale: string;
  baseLabel: string;
  defaultAmount: number;
  defaultPeriod: SalaryPeriod;
  defaultMode: CalculationDirection;
  /** Клиентский мост: при изменении A/B/закреплённых передаёт строку для comparison share. */
  onScenarioShareContextChange?: (summary: string) => void;
}

export function RefundComparisonSection({
  countryId,
  currencyCode,
  locale,
  baseLabel,
  defaultAmount,
  defaultPeriod,
  defaultMode,
  onScenarioShareContextChange
}: RefundComparisonSectionProps) {
  const searchParams = useSearchParams();
  const pathname = usePathname();
  const router = useRouter();

  const refundEnabled = isRefundEnabledForCountry(countryId);
  const categories = getRefundCategoriesForCountry(countryId);
  const notes = getRefundCountryNotes(countryId);
  const presets = getRefundScenarioPresets(countryId);

  const [mode, setMode] = useState<RefundScenarioMode>("none-vs-refund");
  const [deductibleInput, setDeductibleInput] = useState<string>("2000");

  const [categoryAId, categoryBId] = useMemo(() => {
    if (!categories.length) return ["other", "other"] as TaxRefundCategoryId[];
    if (categories.length === 1) return [categories[0].id, categories[0].id];
    return [categories[0].id, categories[1].id];
  }, [categories]);

  const [selectedCategoryA, setSelectedCategoryA] = useState<TaxRefundCategoryId>(categoryAId);
  const [selectedCategoryB, setSelectedCategoryB] = useState<TaxRefundCategoryId>(categoryBId);
  const [savedScenarios, setSavedScenarios] = useSavedRefundScenariosSync();
  const [pinnedIds, setPinnedIds] = usePinnedScenarioIds();
  const [scenarioMessage, setScenarioMessage] = useState<string | null>(null);

  const savedForCountry = useMemo(
    () => getSavedScenariosForCountry(savedScenarios, countryId, pinnedIds),
    [savedScenarios, countryId, pinnedIds]
  );

  const parsedDeductible = useMemo(() => {
    if (!deductibleInput) return null;
    const normalized = deductibleInput.replace(",", ".");
    const value = Number(normalized);
    if (!Number.isFinite(value) || value <= 0) return null;
    if (value > 1_000_000) return 1_000_000;
    return value;
  }, [deductibleInput]);

  const currentLabelA = useMemo(() => {
    if (!parsedDeductible) return null;
    const match = savedForCountry.find(
      (s) =>
        s.categoryId === selectedCategoryA &&
        Math.round(s.deductibleAmount) === Math.round(parsedDeductible)
    );
    return match?.label ?? getRefundCategoryDisplayLabel(countryId, selectedCategoryA) ?? selectedCategoryA;
  }, [savedForCountry, selectedCategoryA, parsedDeductible, countryId]);

  const currentLabelB = useMemo(() => {
    if (!parsedDeductible) return null;
    const match = savedForCountry.find(
      (s) =>
        s.categoryId === selectedCategoryB &&
        Math.round(s.deductibleAmount) === Math.round(parsedDeductible)
    );
    return match?.label ?? getRefundCategoryDisplayLabel(countryId, selectedCategoryB) ?? selectedCategoryB;
  }, [savedForCountry, selectedCategoryB, parsedDeductible, countryId]);

  const currentScenarioA = useMemo(
    () =>
      parsedDeductible
        ? savedForCountry.find(
            (s) =>
              s.categoryId === selectedCategoryA &&
              Math.round(s.deductibleAmount) === Math.round(parsedDeductible)
          ) ?? null
        : null,
    [savedForCountry, selectedCategoryA, parsedDeductible]
  );
  const currentScenarioB = useMemo(
    () =>
      parsedDeductible
        ? savedForCountry.find(
            (s) =>
              s.categoryId === selectedCategoryB &&
              Math.round(s.deductibleAmount) === Math.round(parsedDeductible)
          ) ?? null
        : null,
    [savedForCountry, selectedCategoryB, parsedDeductible]
  );

  const scenarioShareLine = useMemo(
    () =>
      buildScenarioShareSummary(
        {
          slotAScenarioId: currentScenarioA?.id ?? null,
          slotBScenarioId: currentScenarioB?.id ?? null
        },
        savedForCountry,
        { countryId, pinnedIds }
      ),
    [currentScenarioA?.id, currentScenarioB?.id, savedForCountry, pinnedIds, countryId]
  );

  useEffect(() => {
    if (onScenarioShareContextChange) onScenarioShareContextChange(scenarioShareLine);
  }, [onScenarioShareContextChange, scenarioShareLine]);

  const handleSaveCurrentScenario = () => {
    if (!parsedDeductible || parsedDeductible <= 0) {
      setScenarioMessage("Укажите годовую сумму вычета, чтобы сохранить сценарий.");
      return;
    }
    let lastError: string | undefined;
    setSavedScenarios((prev) => {
      const { scenarios, error } = saveCurrentRefundScenario({
        list: prev,
        countryId,
        categoryId: selectedCategoryA,
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

  const applySavedAsA = (s: SavedRefundScenario) => {
    if (!isScenarioValid(s)) {
      setScenarioMessage(getApplyScenarioIncompatibleMessage());
      return;
    }
    setMode("none-vs-refund");
    const applied = buildAppliedScenarioInputs(s);
    setSelectedCategoryA(applied.categoryId);
    setDeductibleInput(applied.deductibleInput);
    setScenarioMessage(getApplyScenarioSuccessMessage(s, countryId));
  };

  const applySavedAsB = (s: SavedRefundScenario) => {
    if (!isScenarioValid(s)) {
      setScenarioMessage(getApplyScenarioIncompatibleMessage());
      return;
    }
    setMode("category-vs-category");
    const applied = buildAppliedScenarioInputs(s);
    setSelectedCategoryB(applied.categoryId);
    setDeductibleInput(applied.deductibleInput);
    setScenarioMessage(getApplyScenarioSuccessMessage(s, countryId));
  };

  const removeSaved = (id: string) => {
    setSavedScenarios((prev) => {
      const { scenarios } = removeSavedScenarioFromList({ list: prev, id });
      return scenarios;
    });
    setScenarioMessage(null);
  };

  useEffect(() => {
    if (!searchParams) return;

    const modeParam = searchParams.get("refundMode");
    if (modeParam === "none-vs-refund" || modeParam === "category-vs-category") {
      setMode(modeParam);
    }

    const catAParam = searchParams.get("refundCatA") as TaxRefundCategoryId | null;
    const catBParam = searchParams.get("refundCatB") as TaxRefundCategoryId | null;
    const availableIds = new Set<TaxRefundCategoryId>(categories.map((c) => c.id));

    if (catAParam && availableIds.has(catAParam)) {
      setSelectedCategoryA(catAParam);
    }
    if (catBParam && availableIds.has(catBParam)) {
      setSelectedCategoryB(catBParam);
    }

    const deductParam = searchParams.get("refundDeductible");
    if (deductParam) {
      const normalized = deductParam.replace(",", ".");
      const value = Number(normalized);
      if (Number.isFinite(value) && value > 0) {
        const safe = Math.min(value, 1_000_000);
        setDeductibleInput(String(safe));
      }
    }
  }, [searchParams, categories]);

  useEffect(() => {
    if (!pathname || !searchParams) return;

    const params = new URLSearchParams(searchParams.toString());

    params.set("refundMode", mode);
    params.set("refundCatA", selectedCategoryA);
    params.set("refundCatB", selectedCategoryB);

    if (parsedDeductible != null) {
      params.set("refundDeductible", String(parsedDeductible));
    } else {
      params.delete("refundDeductible");
    }

    const query = params.toString();
    const url = query ? `${pathname}?${query}` : pathname;
    router.replace(url, { scroll: false });
  }, [
    mode,
    selectedCategoryA,
    selectedCategoryB,
    parsedDeductible,
    pathname,
    router,
    searchParams
  ]);

  const results = useMemo(() => {
    if (!refundEnabled) return null;
    const config = getTaxConfigByCountryId(countryId);
    if (!config) return null;

    const deductibleAmount = parsedDeductible;
    if (deductibleAmount == null) return null;

    const baseInput = {
      amount: defaultAmount,
      period: defaultPeriod,
      direction: defaultMode as CalculationDirection
    };

    let baselineResult: SalaryResult;
    let currentResult: SalaryResult;

    if (mode === "none-vs-refund") {
      baselineResult = calculate(baseInput, config);
      currentResult = calculate(
        {
          ...baseInput,
          refund: {
            deductibleAmount,
            year: new Date().getFullYear(),
            categoryId: selectedCategoryA
          }
        },
        config
      );
    } else {
      baselineResult = calculate(
        {
          ...baseInput,
          refund: {
            deductibleAmount,
            year: new Date().getFullYear(),
            categoryId: selectedCategoryA
          }
        },
        config
      );
      currentResult = calculate(
        {
          ...baseInput,
          refund: {
            deductibleAmount,
            year: new Date().getFullYear(),
            categoryId: selectedCategoryB
          }
        },
        config
      );
    }

    const comparison = buildRefundComparisonSummary(baselineResult, currentResult);
    return { baselineResult, currentResult, comparison };
  }, [
    countryId,
    defaultAmount,
    defaultPeriod,
    defaultMode,
    mode,
    parsedDeductible,
    refundEnabled,
    selectedCategoryA,
    selectedCategoryB
  ]);

  const handleCopySummary = async () => {
    if (typeof window === "undefined" || !results) return;
    const { baselineResult, currentResult, comparison } = results;
    const scenarioSummaryLine = buildComparisonRefundScenarioSummary({
      countryId,
      currencyCode,
      locale,
      mode,
      catA: selectedCategoryA,
      catB: selectedCategoryB,
      deductible: parsedDeductible != null ? String(parsedDeductible) : null,
      savedScenarios
    });
    const lines = [
      ...(scenarioShareLine ? [scenarioShareLine] : []),
      `Сценарий вычетов для ${baseLabel}: ${scenarioSummaryLine}`,
      `Сумма вычета в год: ${deductibleInput || "—"} ${currencyCode}`,
      `Базовый сценарий — net: ${formatCurrency(
        baselineResult.net,
        currencyCode,
        locale
      )}, годовые налоги: ${formatCurrency(
        baselineResult.annualTaxPaid,
        currencyCode,
        locale
      )}, возврат: ${
        baselineResult.taxRefundEstimate
          ? formatCurrency(baselineResult.taxRefundEstimate.potentialRefund, currencyCode, locale)
          : "0"
      }`,
      `Текущий сценарий — net: ${formatCurrency(
        currentResult.net,
        currencyCode,
        locale
      )}, годовые налоги: ${formatCurrency(
        currentResult.annualTaxPaid,
        currencyCode,
        locale
      )}, возврат: ${
        currentResult.taxRefundEstimate
          ? formatCurrency(currentResult.taxRefundEstimate.potentialRefund, currencyCode, locale)
          : "0"
      }`,
      `Разница по возврату: ${formatCurrency(
        comparison.refundDelta,
        currencyCode,
        locale
      )}, эффективная ставка (база/текущий): ${formatPercent(
        baselineResult.effectiveTaxRate,
        locale
      )} → ${formatPercent(currentResult.effectiveTaxRate, locale)}`
    ];

    await navigator.clipboard.writeText(lines.join("\n"));
  };

  if (!refundEnabled || !categories.length) {
    return null;
  }

  return (
    <section className="mt-4 space-y-3 rounded-xl border border-slate-800/80 bg-slate-950/70 p-3 text-[11px] text-slate-100">
      <div className="flex items-center justify-between gap-2">
        <h3 className="text-[11px] font-semibold uppercase tracking-wide text-slate-300">
          Сравнение сценариев с вычетами
        </h3>
        <span className="text-[10px] text-slate-500">
          Оценка влияния вычетов на результат
        </span>
      </div>

      <div className="flex flex-wrap gap-3">
        <div className="min-w-[140px]">
          <label className="mb-1 block text-[10px] font-medium text-slate-400">
            Режим сравнения
          </label>
          <select
            value={mode}
            onChange={(e) => setMode(e.target.value as RefundScenarioMode)}
            className="rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-1.5 text-[11px] text-slate-100 focus:border-indigo-500 focus:outline-none"
          >
            <option value="none-vs-refund">Без вычета vs с вычетом</option>
            <option value="category-vs-category">Категория A vs категория B</option>
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="mb-1 block text-[10px] font-medium text-slate-400">
            Категория A
          </label>
          <select
            value={selectedCategoryA}
            onChange={(e) => setSelectedCategoryA(e.target.value as TaxRefundCategoryId)}
            className="rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-1.5 text-[11px] text-slate-100 focus:border-indigo-500 focus:outline-none"
          >
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {getRefundCategoryDisplayLabel(countryId, c.id)}
              </option>
            ))}
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="mb-1 block text-[10px] font-medium text-slate-400">
            Категория B
          </label>
          <select
            value={selectedCategoryB}
            onChange={(e) => setSelectedCategoryB(e.target.value as TaxRefundCategoryId)}
            className="rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-1.5 text-[11px] text-slate-100 focus:border-indigo-500 focus:outline-none"
          >
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {getRefundCategoryDisplayLabel(countryId, c.id)}
              </option>
            ))}
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="mb-1 block text-[10px] font-medium text-slate-400">
            Сумма вычета в год
          </label>
          <input
            type="number"
            min={0}
            step={100}
            value={deductibleInput}
            onChange={(e) => setDeductibleInput(e.target.value)}
            className="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-1.5 text-[11px] text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Например, 2000"
          />
        </div>
      </div>

      {presets.length > 1 && (
        <div className="mt-2 flex flex-wrap gap-2 text-[10px]">
          <span className="text-slate-500">Быстрые пресеты:</span>
          {presets.map((preset) => (
            <button
              key={preset.id}
              type="button"
              onClick={() => {
                if (preset.id === "no-deduction") {
                  setMode("none-vs-refund");
                  setDeductibleInput("2000");
                  setSelectedCategoryA(categories[0]?.id ?? "other");
                  return;
                }
                setMode("none-vs-refund");
                if (preset.categoryId) {
                  setSelectedCategoryA(preset.categoryId);
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

      <p className="mt-2 text-[10px] text-slate-500">{getWhenScenarioForComparisonHint()}</p>
      {pinnedIds.length > 0 && (
        <p className="text-[10px] text-slate-500">{getPinnedScenariosHint()}</p>
      )}
      <div className="mt-2">
        <SavedScenariosPanel
          scenarios={savedForCountry}
          countryId={countryId}
          activeScenarioId={null}
          pinnedIds={pinnedIds}
          scenarioMessage={scenarioMessage}
          onSave={handleSaveCurrentScenario}
          onTogglePin={(scenario) => {
            setPinnedIds((prev) =>
              prev.includes(scenario.id)
                ? prev.filter((id) => id !== scenario.id)
                : [...prev, scenario.id]
            );
          }}
          slotLabelA="Как A"
          slotLabelB="Как B"
          onApplyAsA={applySavedAsA}
          onApplyAsB={applySavedAsB}
          onRename={(scenario, newLabel) => {
            let lastError: string | undefined;
            setSavedScenarios((prev) => {
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
            setSavedScenarios((prev) => {
              const { scenarios, error } = duplicateSavedScenarioInList({
                list: prev,
                id: scenario.id
              });
              lastError = error;
              return scenarios;
            });
            setScenarioMessage(lastError ?? null);
          }}
          onRemove={(scenario) => removeSaved(scenario.id)}
          isScenarioValid={isScenarioValid}
          getApplySuccessMessage={(s) => getApplyScenarioSuccessMessage(s, countryId)}
          getIncompatibleMessage={getApplyScenarioIncompatibleMessage}
        />
      </div>

      {results && (
        <div className="space-y-2 rounded-lg border border-slate-800/80 bg-slate-950/80 p-3 print:break-inside-avoid">
          {scenarioShareLine ? (
            <div className="rounded border border-slate-700/80 bg-slate-900/50 px-2 py-1.5 print:border-slate-600 print:bg-slate-100 print:text-slate-800">
              <p className="mb-0.5 text-[9px] font-medium uppercase text-slate-500 print:text-slate-600">
                {getScenarioComparisonHeadingLabel()}
              </p>
              <p className="text-[10px] text-slate-400 print:text-[11px] print:text-slate-700">
                {scenarioShareLine}
              </p>
            </div>
          ) : null}
          <p className="text-[10px] text-slate-300">
            В базовом сценарии для {baseLabel} вычет{" "}
            {mode === "none-vs-refund"
              ? "не учитывается"
              : `учитывается как категория «${
                  getRefundCategoryDisplayLabel(
                    countryId,
                    results.comparison.baseline?.categoryId
                  ) ?? "A"
                }»`}{" "}
            с суммой{" "}
            {formatCurrency(results.comparison.baseline?.deductibleUsed ?? 0, currencyCode, locale)}
            . В текущем сценарии мы применяем категорию{" "}
            {getRefundCategoryDisplayLabel(
              countryId,
              results.comparison.current?.categoryId
            ) ?? "B"}
            .
          </p>
          <p className="text-[10px] text-slate-300">
            Net меняется с{" "}
            {formatCurrency(results.baselineResult.net, currencyCode, locale)} до{" "}
            {formatCurrency(results.currentResult.net, currencyCode, locale)}, годовые налоги —
            с{" "}
            {formatCurrency(results.baselineResult.annualTaxPaid, currencyCode, locale)} до{" "}
            {formatCurrency(results.currentResult.annualTaxPaid, currencyCode, locale)}. Оценка
            возврата меняется на{" "}
            {formatCurrency(results.comparison.refundDelta, currencyCode, locale)}.
          </p>
          <p className="text-[10px] text-slate-400">
            Доступные категории и лимиты по вычетам зависят от выбранной страны: мы используем
            упрощённые правила для основных направлений (обучение, медицина, жильё, благотворительность
            и др.), поэтому результат показывает порядок цифр, а не точный размер возврата.
          </p>
          {buildRefundLimitExplanation(
            results.currentResult.taxRefundEstimate ?? null,
            countryId
          ) && (
            <p className="text-[10px] text-slate-400">
              {buildRefundLimitExplanation(
                results.currentResult.taxRefundEstimate ?? null,
                countryId
              )}
            </p>
          )}
          {notes.genericDisclaimer && (
            <p className="text-[10px] text-slate-500">{notes.genericDisclaimer}</p>
          )}
          <p className="text-[10px] text-slate-500">{getSavedScenarioInterpretationText(countryId)}</p>
          {savedForCountry.length > 0 && (
            <p className="text-[10px] text-slate-500">{getSavedScenariosLocalityHint()}</p>
          )}
          <p className="text-[10px] text-slate-500">{getScenarioManagementHint()}</p>
          <button
            type="button"
            onClick={handleCopySummary}
            className="mt-1 rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-[10px] text-slate-200 hover:border-slate-500"
          >
            Скопировать сводку по вычетам
          </button>
        </div>
      )}
    </section>
  );
}

