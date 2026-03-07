import type { SavedRefundScenario } from "./refund-scenarios";

/**
 * Foundation для библиотеки сценариев:
 * сортировка, группировка по стране/категории, pinned/favorite (без UI).
 */

export interface ScenarioLibraryGroup {
  countryId: string;
  categoryId: string;
  scenarioIds: string[];
}

export type PinnedScenarioId = string;

export interface PinnedScenariosState {
  ids: PinnedScenarioId[];
}

/** Ключ localStorage для id закреплённых сценариев (foundation, без UI). */
export const PINNED_SCENARIOS_STORAGE_KEY = "salaryCalc.pinnedRefundScenarios";

export function loadPinnedScenarioIds(): PinnedScenarioId[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(PINNED_SCENARIOS_STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw) as unknown;
    return Array.isArray(parsed) ? parsed.filter((id): id is string => typeof id === "string") : [];
  } catch {
    return [];
  }
}

export function savePinnedScenarioIds(ids: PinnedScenarioId[]): void {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(PINNED_SCENARIOS_STORAGE_KEY, JSON.stringify(ids));
  } catch {
    // no-op
  }
}

function scenarioSortKey(s: SavedRefundScenario): number {
  return -(s.updatedAt ?? s.createdAt);
}

/** Сортировка для отображения: сначала закреплённые (если переданы), затем по дате обновления (новые выше). */
export function sortSavedScenariosForDisplay(
  scenarios: SavedRefundScenario[],
  pinnedIds?: Set<string>
): SavedRefundScenario[] {
  const list = [...scenarios];
  if (pinnedIds?.size) {
    list.sort((a, b) => {
      const aPinned = pinnedIds.has(a.id) ? 1 : 0;
      const bPinned = pinnedIds.has(b.id) ? 1 : 0;
      if (aPinned !== bPinned) return bPinned - aPinned;
      return scenarioSortKey(a) - scenarioSortKey(b);
    });
  } else {
    list.sort((a, b) => scenarioSortKey(a) - scenarioSortKey(b));
  }
  return list;
}

/** Сценарии для страны, отсортированные для отображения (опционально с учётом pinned). */
export function getSavedScenariosForCountry(
  scenarios: SavedRefundScenario[],
  countryId: string,
  pinnedIds?: PinnedScenarioId[]
): SavedRefundScenario[] {
  const forCountry = scenarios.filter((s) => s.countryId === countryId);
  const pinnedSet = pinnedIds?.length ? new Set(pinnedIds) : undefined;
  return sortSavedScenariosForDisplay(forCountry, pinnedSet);
}

/** Группировка по стране. */
export function groupSavedScenariosByCountry(
  scenarios: SavedRefundScenario[]
): Map<string, SavedRefundScenario[]> {
  const map = new Map<string, SavedRefundScenario[]>();
  for (const s of scenarios) {
    const list = map.get(s.countryId) ?? [];
    list.push(s);
    map.set(s.countryId, list);
  }
  return map;
}

/** По стране и категории: массив { categoryId, scenarios } для одной страны (для компактного UI). */
export function getSavedScenariosForCountryGroupedByCategory(
  scenarios: SavedRefundScenario[],
  countryId: string,
  pinnedIds?: PinnedScenarioId[]
): { categoryId: string; scenarios: SavedRefundScenario[] }[] {
  const forCountry = scenarios.filter((s) => s.countryId === countryId);
  const pinnedSet = pinnedIds?.length ? new Set(pinnedIds) : undefined;
  const sorted = sortSavedScenariosForDisplay(forCountry, pinnedSet);
  const byCat = new Map<string, SavedRefundScenario[]>();
  for (const s of sorted) {
    const list = byCat.get(s.categoryId) ?? [];
    list.push(s);
    byCat.set(s.categoryId, list);
  }
  return Array.from(byCat.entries()).map(([categoryId, scenarios]) => ({ categoryId, scenarios }));
}

/** Сгруппировать по стране и категории (все сценарии). */
export function groupSavedScenariosByCountryAndCategory(
  scenarios: SavedRefundScenario[]
): ScenarioLibraryGroup[] {
  const byKey = new Map<string, string[]>();
  for (const s of scenarios) {
    const key = `${s.countryId}:${s.categoryId}`;
    const ids = byKey.get(key) ?? [];
    if (!ids.includes(s.id)) ids.push(s.id);
    byKey.set(key, ids);
  }
  const result: ScenarioLibraryGroup[] = [];
  byKey.forEach((scenarioIds, key) => {
    const [countryId, categoryId] = key.split(":");
    result.push({ countryId, categoryId, scenarioIds });
  });
  return result;
}

/** Foundation для будущего поиска/фильтра: фильтр по стране. */
export function filterScenariosByCountry(
  scenarios: SavedRefundScenario[],
  countryId: string
): SavedRefundScenario[] {
  return scenarios.filter((s) => s.countryId === countryId);
}

/** Foundation: фильтр по категории вычета. */
export function filterScenariosByCategory(
  scenarios: SavedRefundScenario[],
  categoryId: string
): SavedRefundScenario[] {
  return scenarios.filter((s) => s.categoryId === categoryId);
}

/** Foundation: поиск по label (подстрока, без учёта регистра). */
export function filterScenariosByLabel(
  scenarios: SavedRefundScenario[],
  query: string
): SavedRefundScenario[] {
  if (!query.trim()) return scenarios;
  const q = query.trim().toLowerCase();
  return scenarios.filter((s) => s.label.toLowerCase().includes(q));
}

/** Foundation: только закреплённые сценарии. */
export function filterPinnedOnly(
  scenarios: SavedRefundScenario[],
  pinnedIds: PinnedScenarioId[]
): SavedRefundScenario[] {
  if (!pinnedIds.length) return [];
  const set = new Set(pinnedIds);
  return scenarios.filter((s) => set.has(s.id));
}

export interface ApplyScenarioFiltersOptions {
  query?: string;
  categoryId?: string;
  pinnedOnly?: boolean;
  pinnedIds?: PinnedScenarioId[];
}

/** Применить поиск и фильтры к списку (по стране уже отфильтрованному). */
export function applyScenarioFilters(
  scenarios: SavedRefundScenario[],
  options: ApplyScenarioFiltersOptions
): SavedRefundScenario[] {
  let result = scenarios;
  if (options.query?.trim()) {
    result = filterScenariosByLabel(result, options.query);
  }
  if (options.categoryId) {
    result = filterScenariosByCategory(result, options.categoryId);
  }
  if (options.pinnedOnly && options.pinnedIds?.length) {
    result = filterPinnedOnly(result, options.pinnedIds);
  }
  return result;
}

/** Foundation: массовое закрепление (добавить id к списку pinned). */
export function bulkPinScenarioIds(
  currentPinnedIds: PinnedScenarioId[],
  idsToAdd: string[]
): PinnedScenarioId[] {
  const set = new Set(currentPinnedIds);
  idsToAdd.forEach((id) => set.add(id));
  return Array.from(set);
}

/** Foundation: массовое снятие закрепления. */
export function bulkUnpinScenarioIds(
  currentPinnedIds: PinnedScenarioId[],
  idsToRemove: string[]
): PinnedScenarioId[] {
  const set = new Set(idsToRemove);
  return currentPinnedIds.filter((id) => !set.has(id));
}

/** Foundation: массовое удаление из списка сценариев. */
export function bulkDeleteScenariosFromList(
  list: SavedRefundScenario[],
  idsToRemove: string[]
): SavedRefundScenario[] {
  const set = new Set(idsToRemove);
  return list.filter((s) => !set.has(s.id));
}

/** Foundation: экспорт списка сценариев в текст (для будущего bulk export). */
export function bulkExportScenariosToText(
  scenarios: SavedRefundScenario[],
  _locale?: string
): string {
  return scenarios
    .map((s) => `${s.label}\t${s.countryId}\t${s.categoryId}\t${Math.round(s.deductibleAmount)}`)
    .join("\n");
}

/** Экспорт одного сценария в текст (foundation для export UI). */
export function exportSingleScenarioToText(scenario: SavedRefundScenario): string {
  return bulkExportScenariosToText([scenario]);
}

/** Экспорт видимого/отфильтрованного списка (alias для единообразия). */
export function exportVisibleScenariosToText(scenarios: SavedRefundScenario[]): string {
  return bulkExportScenariosToText(scenarios);
}

/** Foundation: выбранные id сценариев (для будущего selected-scenarios UI). */
export type SelectedScenarioIds = Set<string>;

export function filterScenariosByIds(
  scenarios: SavedRefundScenario[],
  selectedIds: SelectedScenarioIds | string[]
): SavedRefundScenario[] {
  const set = typeof selectedIds === "object" && !Array.isArray(selectedIds)
    ? selectedIds
    : new Set(selectedIds as string[]);
  return scenarios.filter((s) => set.has(s.id));
}

/** Экспорт выбранных сценариев по id (foundation для export selected). */
export function exportSelectedScenariosToText(
  scenarios: SavedRefundScenario[],
  selectedIds: SelectedScenarioIds | string[]
): string {
  return bulkExportScenariosToText(filterScenariosByIds(scenarios, selectedIds));
}

/** Закрепить выбранные (foundation для pin selected). */
export function pinSelectedScenarioIds(
  currentPinnedIds: PinnedScenarioId[],
  selectedIds: SelectedScenarioIds | string[]
): PinnedScenarioId[] {
  const toAdd = Array.isArray(selectedIds) ? selectedIds : Array.from(selectedIds);
  return bulkPinScenarioIds(currentPinnedIds, toAdd);
}

/** Снять закрепление с выбранных (foundation для unpin selected). */
export function unpinSelectedScenarioIds(
  currentPinnedIds: PinnedScenarioId[],
  selectedIds: SelectedScenarioIds | string[]
): PinnedScenarioId[] {
  const toRemove = Array.isArray(selectedIds) ? selectedIds : Array.from(selectedIds);
  return bulkUnpinScenarioIds(currentPinnedIds, toRemove);
}

/** Удалить выбранные из списка (foundation для delete selected). */
export function deleteSelectedScenariosFromList(
  list: SavedRefundScenario[],
  selectedIds: SelectedScenarioIds | string[]
): SavedRefundScenario[] {
  const ids = Array.isArray(selectedIds) ? selectedIds : Array.from(selectedIds);
  return bulkDeleteScenariosFromList(list, ids);
}

/** Список выбранных сценариев для сравнения (foundation для bulk comparison). */
export function getSelectedScenariosForComparison(
  scenarios: SavedRefundScenario[],
  selectedIds: SelectedScenarioIds | string[]
): SavedRefundScenario[] {
  return filterScenariosByIds(scenarios, selectedIds);
}

/** Краткий shortlist из выбранных: массив { id, label } (foundation для shortlist UI). */
export function buildShortlistFromSelected(
  scenarios: SavedRefundScenario[],
  selectedIds: SelectedScenarioIds | string[]
): { id: string; label: string }[] {
  return getSelectedScenariosForComparison(scenarios, selectedIds).map((s) => ({
    id: s.id,
    label: s.label
  }));
}

/** Выбранные как источник для comparison presets (foundation: первые два как A/B). */
export function getSelectedAsComparisonPresetPair(
  scenarios: SavedRefundScenario[],
  selectedIds: SelectedScenarioIds | string[]
): { scenarioA: SavedRefundScenario; scenarioB: SavedRefundScenario } | null {
  const list = getSelectedScenariosForComparison(scenarios, selectedIds);
  if (list.length < 2) return null;
  return { scenarioA: list[0], scenarioB: list[1] };
}
