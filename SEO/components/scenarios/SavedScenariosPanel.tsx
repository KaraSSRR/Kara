"use client";

import { useState, useMemo, useCallback } from "react";
import type { SavedRefundScenario } from "@/lib/calculations/refund-scenarios";
import {
  getScenarioSearchPlaceholder,
  getCategoryFilterLabel,
  getPinnedOnlyFilterLabel,
  getNoScenariosFoundLabel,
  getNoScenariosFoundWithFiltersLabel,
  getResetFiltersLabel,
  getExportSingleScenarioLabel,
  getExportVisibleLabel,
  getExportVisibleHint,
  getExportSingleScenarioHint,
  getSelectScenarioLabel,
  getSelectAllVisibleLabel,
  getClearSelectionLabel,
  getExportSelectedLabel,
  getPinSelectedLabel,
  getUnpinSelectedLabel,
  getDeleteSelectedLabel,
  getSelectedCountLabel,
  getExportSelectedPrefixLabel
} from "@/lib/calculations/refund-scenarios";
import { getRefundCategoriesForCountry, getRefundCategoryDisplayLabel } from "@/lib/calculations/refund-config";
import {
  applyScenarioFilters,
  getSavedScenariosForCountryGroupedByCategory,
  exportVisibleScenariosToText,
  exportSingleScenarioToText,
  exportSelectedScenariosToText,
  filterScenariosByIds
} from "@/lib/calculations/scenario-library-foundation";

export interface SavedScenariosPanelProps {
  scenarios: SavedRefundScenario[];
  countryId: string;
  activeScenarioId: string | null;
  pinnedIds?: string[];
  scenarioMessage: string | null;
  onSave: () => void;
  onApply?: (scenario: SavedRefundScenario) => void;
  onApplyAsA?: (scenario: SavedRefundScenario) => void;
  onApplyAsB?: (scenario: SavedRefundScenario) => void;
  onTogglePin?: (scenario: SavedRefundScenario) => void;
  onRename: (scenario: SavedRefundScenario, newLabel: string) => void;
  onDuplicate: (scenario: SavedRefundScenario) => void;
  onRemove: (scenario: SavedRefundScenario) => void;
  isScenarioValid: (scenario: SavedRefundScenario) => boolean;
  getApplySuccessMessage?: (scenario: SavedRefundScenario) => string;
  getIncompatibleMessage?: () => string;
  /** Краткая подпись для слотов A/B (только comparison). */
  slotLabelA?: string;
  slotLabelB?: string;
}

export function SavedScenariosPanel({
  scenarios,
  countryId,
  activeScenarioId,
  pinnedIds,
  scenarioMessage,
  onSave,
  onApply,
  onApplyAsA,
  onApplyAsB,
  onTogglePin,
  onRename,
  onDuplicate,
  onRemove,
  isScenarioValid,
  getApplySuccessMessage,
  getIncompatibleMessage,
  slotLabelA,
  slotLabelB
}: SavedScenariosPanelProps) {
  const [searchQuery, setSearchQuery] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("");
  const [pinnedOnly, setPinnedOnly] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());

  const isComparison = Boolean(onApplyAsA && onApplyAsB);
  const hasApply = Boolean(onApply || (onApplyAsA && onApplyAsB));
  const pinnedSet = pinnedIds?.length ? new Set(pinnedIds) : null;

  const filtered = useMemo(
    () =>
      applyScenarioFilters(scenarios, {
        query: searchQuery || undefined,
        categoryId: categoryFilter || undefined,
        pinnedOnly: pinnedOnly || undefined,
        pinnedIds: pinnedOnly ? pinnedIds : undefined
      }),
    [scenarios, searchQuery, categoryFilter, pinnedOnly, pinnedIds]
  );

  const categories = useMemo(
    () => getRefundCategoriesForCountry(countryId),
    [countryId]
  );

  const scenarioIdsSet = useMemo(() => new Set(scenarios.map((s) => s.id)), [scenarios]);
  const effectiveSelectedIds = useMemo(
    () => new Set([...selectedIds].filter((id) => scenarioIdsSet.has(id))),
    [selectedIds, scenarioIdsSet]
  );
  const selectedCount = effectiveSelectedIds.size;
  const selectedScenarios = useMemo(
    () => filterScenariosByIds(scenarios, effectiveSelectedIds),
    [scenarios, effectiveSelectedIds]
  );

  const toggleSelection = useCallback((id: string) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }, []);
  const selectAllVisible = useCallback(() => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      filtered.forEach((s) => next.add(s.id));
      return next;
    });
  }, [filtered]);
  const clearSelection = useCallback(() => setSelectedIds(new Set()), []);

  const hasActiveFilters = Boolean(searchQuery || categoryFilter || pinnedOnly);
  const handleResetFilters = () => {
    setSearchQuery("");
    setCategoryFilter("");
    setPinnedOnly(false);
  };
  const handleExportVisible = async () => {
    if (typeof window === "undefined" || filtered.length === 0) return;
    const text = exportVisibleScenariosToText(filtered);
    await navigator.clipboard.writeText(text);
  };
  const handleExportOne = async (scenario: SavedRefundScenario) => {
    if (typeof window === "undefined") return;
    const text = exportSingleScenarioToText(scenario);
    await navigator.clipboard.writeText(text);
  };
  const handleExportSelected = async () => {
    if (typeof window === "undefined" || selectedCount === 0) return;
    const body = exportSelectedScenariosToText(scenarios, effectiveSelectedIds);
    const prefix = getExportSelectedPrefixLabel(selectedCount);
    await navigator.clipboard.writeText(`${prefix}\n${body}`);
  };
  const handlePinSelected = () => {
    if (!onTogglePin || selectedCount === 0) return;
    selectedScenarios.forEach((s) => {
      if (!pinnedSet?.has(s.id)) onTogglePin(s);
    });
  };
  const handleUnpinSelected = () => {
    if (!onTogglePin || selectedCount === 0) return;
    selectedScenarios.forEach((s) => {
      if (pinnedSet?.has(s.id)) onTogglePin(s);
    });
  };
  const handleDeleteSelected = () => {
    if (selectedCount === 0) return;
    selectedScenarios.forEach((s) => onRemove(s));
    setSelectedIds((prev) => {
      const remove = new Set(selectedScenarios.map((s) => s.id));
      return new Set([...prev].filter((id) => !remove.has(id)));
    });
  };

  const useGroups = filtered.length > 5;
  const grouped = useMemo(
    () =>
      useGroups
        ? getSavedScenariosForCountryGroupedByCategory(filtered, countryId, pinnedIds)
        : [],
    [useGroups, filtered, countryId, pinnedIds]
  );

  const handleRename = (scenario: SavedRefundScenario) => {
    const nextLabel =
      typeof window !== "undefined"
        ? window.prompt("Новое название сценария", scenario.label) ?? ""
        : "";
    onRename(scenario, nextLabel);
  };

  if (scenarios.length === 0 && !scenarioMessage) {
    return (
      <div className="rounded-xl border border-slate-800/80 bg-slate-950/60 px-3 py-2 text-[10px] text-slate-400">
        <button
          type="button"
          onClick={onSave}
          className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-1 text-[11px] text-slate-200 hover:border-slate-500"
        >
          Сохранить текущий сценарий
        </button>
      </div>
    );
  }

  const activeLabel = activeScenarioId
    ? scenarios.find((s) => s.id === activeScenarioId)?.label ?? null
    : null;

  return (
    <div className="rounded-xl border border-slate-800/80 bg-slate-950/60 px-3 py-2 text-[10px] text-slate-400">
      <div className="mb-1.5 flex items-center justify-between gap-2">
        <span className="text-[10px] font-medium uppercase tracking-wide text-slate-500">
          Сохранённые сценарии
        </span>
        <span className="text-[9px] text-slate-500">локально в браузере</span>
      </div>
      <div className="space-y-1.5">
        <button
          type="button"
          onClick={onSave}
          className="rounded-full border border-slate-700 bg-slate-950/80 px-3 py-0.5 text-[10px] text-slate-200 hover:border-slate-500"
        >
          Сохранить текущий
        </button>
        {activeLabel && (
          <p className="rounded border border-indigo-700/50 bg-indigo-950/30 px-2 py-0.5 text-[10px] text-indigo-200">
            Активный: {activeLabel}
          </p>
        )}
        {scenarioMessage && (
          <p className="text-[10px] text-amber-300/90">{scenarioMessage}</p>
        )}
        {scenarios.length > 0 && (
          <>
            <div className="flex flex-wrap items-center gap-1.5">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder={getScenarioSearchPlaceholder()}
                className="max-w-[140px] rounded border border-slate-700 bg-slate-950/80 px-1.5 py-0.5 text-[9px] text-slate-200 placeholder:text-slate-500"
              />
              <select
                value={categoryFilter}
                onChange={(e) => setCategoryFilter(e.target.value)}
                className="rounded border border-slate-700 bg-slate-950/80 px-1.5 py-0.5 text-[9px] text-slate-200"
                title={getCategoryFilterLabel()}
              >
                <option value="">{getCategoryFilterLabel()}</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {getRefundCategoryDisplayLabel(countryId, c.id) ?? c.id}
                  </option>
                ))}
              </select>
              {pinnedIds?.length ? (
                <label className="flex items-center gap-1 text-[9px] text-slate-500">
                  <input
                    type="checkbox"
                    checked={pinnedOnly}
                    onChange={(e) => setPinnedOnly(e.target.checked)}
                    className="rounded border-slate-600"
                  />
                  {getPinnedOnlyFilterLabel()}
                </label>
              ) : null}
              {hasActiveFilters && (
                <button
                  type="button"
                  onClick={handleResetFilters}
                  className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-400 hover:border-slate-500 hover:text-slate-300"
                >
                  {getResetFiltersLabel()}
                </button>
              )}
              {filtered.length > 0 && (
                <button
                  type="button"
                  onClick={handleExportVisible}
                  className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-400 hover:border-slate-500 hover:text-slate-300"
                  title={getExportVisibleHint()}
                >
                  {getExportVisibleLabel()}
                </button>
              )}
            </div>
            {filtered.length > 0 && (
              <div className="flex flex-wrap items-center gap-1.5">
                <button
                  type="button"
                  onClick={selectAllVisible}
                  className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-slate-300"
                >
                  {getSelectAllVisibleLabel()}
                </button>
                {selectedCount > 0 && (
                  <>
                    <span className="text-[9px] text-slate-500">{getSelectedCountLabel(selectedCount)}</span>
                    <button
                      type="button"
                      onClick={clearSelection}
                      className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-slate-300"
                    >
                      {getClearSelectionLabel()}
                    </button>
                    <button
                      type="button"
                      onClick={handleExportSelected}
                      className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-400 hover:text-slate-300"
                    >
                      {getExportSelectedLabel()}
                    </button>
                    {onTogglePin && (
                      <>
                        <button
                          type="button"
                          onClick={handlePinSelected}
                          className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-400 hover:text-slate-300"
                        >
                          {getPinSelectedLabel()}
                        </button>
                        <button
                          type="button"
                          onClick={handleUnpinSelected}
                          className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-400 hover:text-slate-300"
                        >
                          {getUnpinSelectedLabel()}
                        </button>
                      </>
                    )}
                    <button
                      type="button"
                      onClick={handleDeleteSelected}
                      className="rounded border border-slate-600 px-1.5 py-0.5 text-[9px] text-slate-400 hover:text-red-400"
                    >
                      {getDeleteSelectedLabel()}
                    </button>
                  </>
                )}
              </div>
            )}
            {hasActiveFilters && (
              <p className="text-[9px] text-slate-500">Фильтры включены</p>
            )}
            {filtered.length === 0 ? (
              <div className="space-y-1">
                <p className="text-[10px] text-slate-500">
                  {hasActiveFilters ? getNoScenariosFoundWithFiltersLabel() : getNoScenariosFoundLabel()}
                </p>
                {hasActiveFilters && (
                  <button
                    type="button"
                    onClick={handleResetFilters}
                    className="rounded border border-slate-600 px-2 py-0.5 text-[9px] text-slate-400 hover:text-slate-300"
                  >
                    {getResetFiltersLabel()}
                  </button>
                )}
              </div>
            ) : useGroups ? (
              <div className="space-y-1.5">
                {grouped.map(({ categoryId, scenarios: groupScenarios }) => (
                  <div key={categoryId}>
                    <p className="mb-0.5 text-[9px] font-medium uppercase text-slate-500">
                      {getRefundCategoryDisplayLabel(countryId, categoryId) ?? categoryId}
                    </p>
                    <ul className="space-y-1">
                      {groupScenarios.map((scenario) => renderScenarioItem(scenario))}
                    </ul>
                  </div>
                ))}
              </div>
            ) : (
          <ul className="space-y-1">
            {filtered.map((scenario) => renderScenarioItem(scenario))}
          </ul>
            )}
          </>
        )}
      </div>
    </div>
  );

  function renderScenarioItem(scenario: SavedRefundScenario) {
    const valid = isScenarioValid(scenario);
    const isPinned = pinnedSet?.has(scenario.id) ?? false;
    const categoryLabel =
      getRefundCategoryDisplayLabel(countryId, scenario.categoryId) ?? scenario.categoryId;
    const isActive = scenario.id === activeScenarioId;
    const isSelected = effectiveSelectedIds.has(scenario.id);
    return (
      <li
                  key={scenario.id}
                  className={`flex flex-wrap items-center justify-between gap-1.5 rounded-lg border px-2 py-1 ${
                    isActive
                      ? "border-indigo-700/50 bg-indigo-950/20"
                      : "border-slate-800/80 bg-slate-950/80"
                  }`}
                >
                  <label className="flex min-w-0 flex-1 cursor-pointer items-center gap-1.5" title={getSelectScenarioLabel()}>
                    <input
                      type="checkbox"
                      checked={isSelected}
                      onChange={() => toggleSelection(scenario.id)}
                      className="rounded border-slate-600"
                    />
                  <span className="min-w-0 flex-1 truncate text-slate-200" title={scenario.label}>
                    {isPinned && (
                      <span className="mr-1 text-[9px] text-amber-400" title="Закреплён">
                        ★
                      </span>
                    )}
                    {scenario.label}
                  </span>
                  </label>
                  <span className="text-[9px] text-slate-500">{categoryLabel}</span>
                  <div className="flex flex-wrap items-center gap-0.5">
                    {onTogglePin && (
                      <button
                        type="button"
                        onClick={() => onTogglePin(scenario)}
                        className="rounded border border-transparent px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-amber-400"
                        title={isPinned ? "Открепить" : "Закрепить"}
                      >
                        {isPinned ? "★" : "☆"}
                      </button>
                    )}
                    {isComparison && onApplyAsA && onApplyAsB ? (
                      <>
                        <button
                          type="button"
                          onClick={() => valid && onApplyAsA(scenario)}
                          className="rounded border border-slate-700 px-1.5 py-0.5 text-[9px] text-indigo-400 hover:border-slate-500 disabled:opacity-50"
                        >
                          {slotLabelA ?? "Как A"}
                        </button>
                        <button
                          type="button"
                          onClick={() => valid && onApplyAsB(scenario)}
                          className="rounded border border-slate-700 px-1.5 py-0.5 text-[9px] text-indigo-400 hover:border-slate-500 disabled:opacity-50"
                        >
                          {slotLabelB ?? "Как B"}
                        </button>
                      </>
                    ) : hasApply && onApply ? (
                      <button
                        type="button"
                        onClick={() => {
                          if (valid) {
                            onApply(scenario);
                          }
                        }}
                        className="rounded border border-slate-700 px-1.5 py-0.5 text-[9px] text-slate-200 hover:border-slate-500 disabled:opacity-50"
                      >
                        Применить
                      </button>
                    ) : null}
                    <button
                      type="button"
                      onClick={() => handleExportOne(scenario)}
                      className="rounded border border-transparent px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-slate-100"
                      title={getExportSingleScenarioHint()}
                    >
                      {getExportSingleScenarioLabel()}
                    </button>
                    <button
                      type="button"
                      onClick={() => handleRename(scenario)}
                      className="rounded border border-transparent px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-slate-100"
                    >
                      Переименовать
                    </button>
                    <button
                      type="button"
                      onClick={() => onDuplicate(scenario)}
                      className="rounded border border-transparent px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-slate-100"
                    >
                      Дублировать
                    </button>
                    <button
                      type="button"
                      onClick={() => onRemove(scenario)}
                      className="rounded border border-transparent px-1.5 py-0.5 text-[9px] text-slate-500 hover:text-red-400"
                    >
                      Удалить
                    </button>
                  </div>
      </li>
    );
  }
}
