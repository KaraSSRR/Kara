import type { TaxRefundCategoryId } from "./types";
import {
  getRefundCategoriesForCountry,
  getRefundCategoryDisplayLabel,
  getRefundCountryNotes,
  getRefundScenarioPresets,
  type RefundScenarioPreset
} from "./refund-config";

export type RefundScenarioOrigin = "preset" | "custom" | "saved";

export interface RefundScenarioPayload {
  id: string;
  label: string;
  countryId: string;
  categoryId: TaxRefundCategoryId;
  deductibleAmount: number;
  origin: RefundScenarioOrigin;
  /** Если сценарий основан на пресете, сохраняем его id для будущих сценариев. */
  presetId?: RefundScenarioPreset["id"];
}

export interface SavedRefundScenario extends RefundScenarioPayload {
  /** Метка времени создания — пригодится для сортировки в будущем. */
  createdAt: number;
  /** Foundation: время последнего изменения (опционально). */
  updatedAt?: number;
  /** Foundation: источник — preset | custom | saved (опционально). */
  sourceType?: string;
  /** Foundation: id пресета, от которого произошёл сценарий (опционально). */
  derivedFromPresetId?: string;
  /** Foundation: пользовательская заметка (опционально). */
  note?: string;
}

export function buildRefundScenarioId(input: {
  countryId: string;
  categoryId: TaxRefundCategoryId;
  deductibleAmount: number;
  origin: RefundScenarioOrigin;
}): string {
  const rounded = Math.round(input.deductibleAmount);
  return `${input.countryId}-${input.categoryId}-${rounded}-${input.origin}`;
}

export function detectPresetForScenario(countryId: string, categoryId: TaxRefundCategoryId, deductibleAmount: number):
  | RefundScenarioPreset
  | null {
  const presets = getRefundScenarioPresets(countryId);
  const rounded = Math.round(deductibleAmount);
  for (const preset of presets) {
    if (!preset.categoryId || preset.defaultDeductible == null) continue;
    if (preset.categoryId === categoryId && Math.round(preset.defaultDeductible) === rounded) {
      return preset;
    }
  }
  return null;
}

export function buildRefundScenarioPayload(input: {
  countryId: string;
  categoryId: TaxRefundCategoryId;
  deductibleAmount: number;
  origin?: RefundScenarioOrigin;
}): RefundScenarioPayload {
  const effectiveOrigin: RefundScenarioOrigin = input.origin ?? "custom";
  const preset = detectPresetForScenario(
    input.countryId,
    input.categoryId,
    input.deductibleAmount
  );

  const categoryLabel =
    getRefundCategoryDisplayLabel(input.countryId, input.categoryId) ?? input.categoryId;

  const baseLabel =
    preset && effectiveOrigin !== "custom"
      ? preset.label
      : `${categoryLabel}, ~${Math.round(input.deductibleAmount)}`;

  return {
    id: buildRefundScenarioId({
      countryId: input.countryId,
      categoryId: input.categoryId,
      deductibleAmount: input.deductibleAmount,
      origin: effectiveOrigin
    }),
    label: baseLabel,
    countryId: input.countryId,
    categoryId: input.categoryId,
    deductibleAmount: input.deductibleAmount,
    origin: preset ? "preset" : effectiveOrigin,
    presetId: preset?.id
  };
}

export function buildRefundScenarioSummaryLine(
  scenario: RefundScenarioPayload,
  currencyCode: string,
  locale: string
): string {
  const categoryLabel =
    getRefundCategoryDisplayLabel(scenario.countryId, scenario.categoryId) ??
    scenario.categoryId;
  const formatter = new Intl.NumberFormat(locale, {
    maximumFractionDigits: 0
  });

  const amountText = `${formatter.format(Math.round(scenario.deductibleAmount))} ${currencyCode}`;

  return `Сценарий вычетов: ${scenario.label} (категория: ${categoryLabel}, годовая сумма вычетов: ${amountText}).`;
}

/** Короткое пояснение, почему даже для сохранённого сценария результат остаётся оценочным. */
export function buildRefundScenarioDisclaimer(countryId: string): string {
  const notes = getRefundCountryNotes(countryId);
  return (
    notes.genericDisclaimer ??
    "Даже при сохранённом сценарии вычетов расчёт остаётся ориентировочным: фактический возврат зависит от подтверждённых расходов и правил страны."
  );
}

/** Foundation для сравнения нескольких сохранённых сценариев в будущем. */
export interface MultiScenarioComparisonPayload {
  scenarios: RefundScenarioPayload[];
}

export function buildMultiScenarioComparisonPayload(
  scenarios: RefundScenarioPayload[]
): MultiScenarioComparisonPayload {
  return {
    scenarios
  };
}

/** Версия структуры сохранённых сценариев для безопасной миграции. */
export const SAVED_REFUND_SCENARIOS_STORAGE_VERSION = 1;

/** Ключ localStorage для синхронизации между вкладками и страницами (storage event). */
export const SAVED_REFUND_SCENARIOS_STORAGE_KEY = "salaryCalc.savedRefundScenarios";

function isSavedRefundScenarioShape(x: unknown): x is SavedRefundScenario {
  if (!x || typeof x !== "object") return false;
  const o = x as Record<string, unknown>;
  const required =
    typeof o.id === "string" &&
    typeof o.label === "string" &&
    typeof o.countryId === "string" &&
    typeof o.categoryId === "string" &&
    typeof o.deductibleAmount === "number" &&
    typeof o.origin === "string" &&
    typeof o.createdAt === "number";
  if (!required) return false;
  if (o.updatedAt != null && typeof o.updatedAt !== "number") return false;
  if (o.sourceType != null && typeof o.sourceType !== "string") return false;
  if (o.derivedFromPresetId != null && typeof o.derivedFromPresetId !== "string") return false;
  if (o.note != null && typeof o.note !== "string") return false;
  return true;
}

/** Нормализация при миграции: дополняет старые записи опциональными полями. */
export function normalizeSavedScenario(raw: SavedRefundScenario): SavedRefundScenario {
  return {
    ...raw,
    updatedAt: raw.updatedAt ?? raw.createdAt,
    sourceType: raw.sourceType ?? raw.origin,
    derivedFromPresetId: raw.derivedFromPresetId ?? raw.presetId,
    note: raw.note
  };
}

/** Загрузка с версией и мягким fallback при несовместимых данных. */
export function loadSavedRefundScenarios(): SavedRefundScenario[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(SAVED_REFUND_SCENARIOS_STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw) as unknown;
    if (!parsed || typeof parsed !== "object") return [];
    const withVersion = parsed as { version?: number; items?: unknown[] };
    const version = withVersion.version;
    const items = Array.isArray(withVersion.items) ? withVersion.items : Array.isArray(parsed) ? parsed : [];
    const result: SavedRefundScenario[] = [];
    const pushValid = (item: unknown) => {
      if (!isSavedRefundScenarioShape(item)) return;
      const normalized = normalizeSavedScenario(item);
      const health = validateRefundScenario(normalized);
      if (!health.isValid) return;
      result.push(normalized);
    };
    if (version === SAVED_REFUND_SCENARIOS_STORAGE_VERSION) {
      for (const item of items) pushValid(item);
    } else if (Array.isArray(parsed)) {
      for (const item of parsed) pushValid(item);
    }
    return result;
  } catch {
    return [];
  }
}

/** Сохранение с версией для будущей миграции. */
export function saveSavedRefundScenarios(scenarios: SavedRefundScenario[]): void {
  if (typeof window === "undefined") return;
  try {
    const payload = {
      version: SAVED_REFUND_SCENARIOS_STORAGE_VERSION,
      items: scenarios
    };
    window.localStorage.setItem(SAVED_REFUND_SCENARIOS_STORAGE_KEY, JSON.stringify(payload));
  } catch {
    // no-op
  }
}

/** Текст для интерпретации: сохранённый сценарий — пользовательский ориентир, отличается от пресета, итог остаётся estimate. */
export function getSavedScenarioInterpretationText(_countryId: string): string {
  return "Сохранённый сценарий — ваш ориентир для повторного сравнения; в отличие от быстрого пресета вы задаёте ему название. Результат по-прежнему оценочный.";
}

/** Короткий hint: локальность и синхронизация — только этот браузер, можно использовать в калькуляторе и на сравнении. */
export function getSavedScenariosLocalityHint(): string {
  return "Сохранённые сценарии хранятся только в этом браузере и доступны в калькуляторе и на страницах сравнения.";
}

/** Короткий hint про управление сценариями: сохранить, переименовать, использовать повторно; локальное хранение; ориентировочные сравнения. */
export function getScenarioManagementHint(): string {
  return "Сценарий вычетов можно сохранить, переименовать и использовать повторно. Он хранится локально в браузере — это инструмент для ориентировочных сравнений.";
}

/** Когда стоит сохранить сценарий (калькулятор и сравнение). */
export function getWhenToSaveScenarioHint(): string {
  return "Сохраните сценарий, если хотите быстро возвращаться к одному и тому же набору вычетов для сравнения.";
}

/** Когда удобнее использовать пресет. */
export function getWhenToUsePresetHint(): string {
  return "Пресет удобен для разового расчёта по типовой категории; для повторных сравнений лучше сохранить свой сценарий.";
}

/** Когда сценарий лучше подходит для сравнения. */
export function getWhenScenarioForComparisonHint(): string {
  return "Сохранённый сценарий удобен для сравнения «до/после» и для страниц сравнения городов и профессий.";
}

/** Короткий hint про закреплённые сценарии: показываются первыми, хранятся локально. */
export function getPinnedScenariosHint(): string {
  return "Закреплённые сценарии отображаются первыми и тоже хранятся только в этом браузере.";
}

/** Подпись поля поиска сценариев. */
export function getScenarioSearchPlaceholder(): string {
  return "Поиск по названию";
}

/** Подпись фильтра «только закреплённые». */
export function getPinnedOnlyFilterLabel(): string {
  return "Только закреплённые";
}

/** Подпись фильтра по категории. */
export function getCategoryFilterLabel(): string {
  return "По категории";
}

/** Сообщение, когда по поиску/фильтру ничего не найдено. */
export function getNoScenariosFoundLabel(): string {
  return "Ничего не найдено";
}

/** Когда фильтры включены и результат пустой. */
export function getNoScenariosFoundWithFiltersLabel(): string {
  return "По фильтрам ничего не найдено. Сбросьте фильтры.";
}

/** Подпись кнопки сброса фильтров. */
export function getResetFiltersLabel(): string {
  return "Сбросить фильтры";
}

/** Подпись действия «экспорт одного сценария». */
export function getExportSingleScenarioLabel(): string {
  return "Экспорт";
}

/** Подпись действия «экспорт видимых». */
export function getExportVisibleLabel(): string {
  return "Экспорт видимых";
}

/** Подсказка: экспорт видимых использует текущие фильтры. */
export function getExportVisibleHint(): string {
  return "Экспорт видимых использует текущие фильтры. В буфер попадёт только отображаемый список.";
}

/** Подсказка: экспорт одного сценария сохраняет только его параметры. */
export function getExportSingleScenarioHint(): string {
  return "Скопировать параметры этого сценария в буфер.";
}

/** Краткая подсказка: сценарии локальные и ориентировочные. */
export function getScenariosLocalIndicativeHint(): string {
  return "Сценарии хранятся локально в браузере, расчёты ориентировочные.";
}

/** Заголовок блока сценариев A/B при печати/экспорте. */
export function getScenarioComparisonHeadingLabel(): string {
  return "Сценарии сравнения";
}

/** Подпись: выбрать сценарий (чекбокс). */
export function getSelectScenarioLabel(): string {
  return "Выбрать";
}

/** Подпись: выбрать все видимые. */
export function getSelectAllVisibleLabel(): string {
  return "Выбрать все видимые";
}

/** Подпись: снять выбор. */
export function getClearSelectionLabel(): string {
  return "Снять выбор";
}

/** Подпись: экспортировать выбранные. */
export function getExportSelectedLabel(): string {
  return "Экспорт выбранных";
}

/** Подпись: закрепить выбранные. */
export function getPinSelectedLabel(): string {
  return "Закрепить выбранные";
}

/** Подпись: открепить выбранные. */
export function getUnpinSelectedLabel(): string {
  return "Открепить выбранные";
}

/** Подпись: удалить выбранные. */
export function getDeleteSelectedLabel(): string {
  return "Удалить выбранные";
}

/** Текст «Выбрано N» для панели. */
export function getSelectedCountLabel(count: number): string {
  return `Выбрано: ${count}`;
}

/** Префикс при экспорте выбранных (user-side). */
export function getExportSelectedPrefixLabel(count: number): string {
  return `Экспортированы выбранные сценарии (${count}).`;
}

/** Результат проверки сценария вычетов. */
export interface RefundScenarioHealth {
  isValid: boolean;
  reasons: string[];
}

/** Базовая валидация сценария вычетов для хранения/применения. */
export function validateRefundScenario(
  scenario: RefundScenarioPayload | SavedRefundScenario
): RefundScenarioHealth {
  const reasons: string[] = [];

  if (!scenario.label || !scenario.label.trim()) {
    if (scenario.origin === "saved") {
      reasons.push("Пустой label для сохранённого сценария.");
    }
  }

  if (!Number.isFinite(scenario.deductibleAmount) || scenario.deductibleAmount <= 0) {
    reasons.push("Некорректная сумма вычета.");
  }

  const categories = getRefundCategoriesForCountry(scenario.countryId);
  const validCategoryIds = new Set(categories.map((c) => c.id));
  if (!validCategoryIds.has(scenario.categoryId)) {
    reasons.push("Категория вычета не поддерживается для выбранной страны.");
  }

  return {
    isValid: reasons.length === 0,
    reasons
  };
}

/** Foundation: helper для rename сохранённого сценария. */
export function renameSavedScenario(
  scenario: SavedRefundScenario,
  newLabel: string,
  now: number = Date.now()
): SavedRefundScenario {
  const label = newLabel.trim();
  return {
    ...scenario,
    label: label || scenario.label,
    updatedAt: now
  };
}

/** Foundation: helper для дублирования сохранённого сценария. */
export function duplicateSavedScenario(
  scenario: SavedRefundScenario,
  now: number = Date.now()
): SavedRefundScenario {
  return {
    ...scenario,
    id: `${scenario.id}-copy-${now}`,
    createdAt: now,
    updatedAt: now
  };
}

/** Foundation: helper для конвертации payload (preset/custom) в saved-сценарий. */
export function toSavedScenario(
  payload: RefundScenarioPayload,
  label?: string,
  now: number = Date.now()
): SavedRefundScenario {
  return {
    ...payload,
    origin: "saved",
    label: (label ?? payload.label).trim() || payload.label,
    createdAt: now,
    updatedAt: now,
    sourceType: payload.origin,
    derivedFromPresetId: payload.presetId
  };
}

/** Найти сохранённый сценарий по параметрам (для подписи в share/export). */
export function findSavedScenarioLabel(
  saved: SavedRefundScenario[],
  countryId: string,
  categoryId: string,
  deductibleAmount: number
): string | null {
  const rounded = Math.round(deductibleAmount);
  const match = saved.find(
    (s) =>
      s.countryId === countryId &&
      s.categoryId === categoryId &&
      Math.round(s.deductibleAmount) === rounded
  );
  return match ? match.label : null;
}

/** Собрать краткую сводку для comparison share с учётом сохранённых сценариев (foundation для richer cards/preview). */
export function buildComparisonRefundScenarioSummary(options: {
  countryId: string;
  currencyCode: string;
  locale: string;
  mode: string;
  catA: string | null;
  catB: string | null;
  deductible: string | null;
  savedScenarios: SavedRefundScenario[];
}): string {
  const parts: string[] = [];
  const modeLabel =
    options.mode === "category-vs-category" ? "категория A vs категория B" : "без вычета vs с вычетом";
  parts.push(`Сценарий вычетов: ${modeLabel}.`);

  const labelA =
    options.catA && options.deductible
      ? findSavedScenarioLabel(
          options.savedScenarios,
          options.countryId,
          options.catA,
          Number(options.deductible)
        )
      : null;
  const labelB =
    options.catB && options.deductible
      ? findSavedScenarioLabel(
          options.savedScenarios,
          options.countryId,
          options.catB,
          Number(options.deductible)
        )
      : null;

  if (options.catA) {
    const display =
      labelA ?? `${getRefundCategoryDisplayLabel(options.countryId, options.catA as TaxRefundCategoryId) ?? options.catA}`;
    parts.push(`Сценарий A: ${display}.`);
  }
  if (options.catB) {
    const display =
      labelB ?? `${getRefundCategoryDisplayLabel(options.countryId, options.catB as TaxRefundCategoryId) ?? options.catB}`;
    parts.push(`Сценарий B: ${display}.`);
  }
  if (options.deductible) {
    parts.push(`Годовая сумма вычета: ${options.deductible} ${options.currencyCode}.`);
  }
  return parts.join(" ");
}

/** Тип метки сценария для share/preview metadata (foundation для richer flows). */
export type ScenarioLabelType = "saved" | "preset" | "custom";

/** Foundation: payload для share cards / preview metadata (без реализации соц-фич). */
export interface ScenarioAwareSharePayload {
  scenarioLabel: string | null;
  presetLabel: string | null;
  categoryLabel: string | null;
  deductibleSummary: string;
  countryAwareNote: string | null;
  /** Foundation: тип метки для preview/share (saved / preset / custom). */
  scenarioLabelType?: ScenarioLabelType;
}

export function buildScenarioAwareSharePayload(options: {
  scenario: RefundScenarioPayload | null;
  countryId: string;
  currencyCode: string;
  locale: string;
}): ScenarioAwareSharePayload {
  if (!options.scenario) {
    return {
      scenarioLabel: null,
      presetLabel: null,
      categoryLabel: null,
      deductibleSummary: "",
      countryAwareNote: null
    };
  }
  const s = options.scenario;
  const categoryLabel = getRefundCategoryDisplayLabel(options.countryId, s.categoryId);
  const formatter = new Intl.NumberFormat(options.locale, { maximumFractionDigits: 0 });
  const deductibleSummary = `${formatter.format(Math.round(s.deductibleAmount))} ${options.currencyCode}`;
  const countryAwareNote = getRefundCountryNotes(options.countryId).genericDisclaimer ?? null;
  const scenarioLabelType: ScenarioLabelType =
    s.origin === "saved" ? "saved" : s.presetId ? "preset" : "custom";
  return {
    scenarioLabel: s.origin === "saved" ? s.label : null,
    presetLabel: s.presetId ? s.label : null,
    categoryLabel: categoryLabel ?? null,
    deductibleSummary,
    countryAwareNote,
    scenarioLabelType
  };
}

/** Тип метки сценария по payload (foundation для preview/share metadata). */
export function getScenarioLabelType(
  payload: ScenarioAwareSharePayload | null
): ScenarioLabelType | null {
  if (!payload?.scenarioLabelType) return null;
  return payload.scenarioLabelType;
}

/** Выбрать стабильный пресетный сценарий вычетов для страны (для metadata/share previews). */
export function pickDefaultRefundScenarioForCountry(
  countryId: string
): RefundScenarioPayload | null {
  const presets = getRefundScenarioPresets(countryId);
  const candidate = presets.find(
    (p) => p.categoryId && p.defaultDeductible != null && p.defaultDeductible > 0
  );
  if (!candidate || !candidate.categoryId || candidate.defaultDeductible == null) {
    return null;
  }
  return buildRefundScenarioPayload({
    countryId,
    categoryId: candidate.categoryId,
    deductibleAmount: candidate.defaultDeductible,
    origin: "preset"
  });
}

/** Единая строка для share/export из scenario-aware payload (single и comparison). */
export function formatScenarioAwareShareLine(payload: ScenarioAwareSharePayload): string {
  const label =
    payload.scenarioLabel ?? payload.presetLabel ?? payload.categoryLabel ?? "вычет";
  const parts = [`Сценарий: ${label}`, `годовая сумма вычета: ${payload.deductibleSummary}`];
  if (payload.countryAwareNote) parts.push(payload.countryAwareNote);
  return parts.join(". ");
}

/** Foundation для richer share cards / preview metadata / email (без реализации соц-фич). */
export function scenarioSummaryForMetadata(payload: ScenarioAwareSharePayload): string {
  const label =
    payload.scenarioLabel ?? payload.presetLabel ?? payload.categoryLabel ?? "вычет";
  return `${label}, ${payload.deductibleSummary}${payload.countryAwareNote ? `. ${payload.countryAwareNote}` : ""}`;
}

/** Foundation для richer preview/email: тип сценария, country note, summary без локальных label в canonical SEO. */
export interface ScenarioMetadataNote {
  scenarioType: ScenarioLabelType | null;
  countryNote: string | null;
  scenarioSummary: string;
}

export function buildScenarioMetadataNote(
  payload: ScenarioAwareSharePayload | null,
  options: { includeLocalLabel?: boolean }
): ScenarioMetadataNote {
  if (!payload) {
    return { scenarioType: null, countryNote: null, scenarioSummary: "" };
  }
  const scenarioType = payload.scenarioLabelType ?? null;
  const countryNote = payload.countryAwareNote;
  const label =
    options.includeLocalLabel !== false
      ? payload.scenarioLabel ?? payload.presetLabel ?? payload.categoryLabel ?? "вычет"
      : payload.presetLabel ?? payload.categoryLabel ?? "вычет";
  const scenarioSummary =
    label && payload.deductibleSummary
      ? `${label}, ${payload.deductibleSummary}`
      : payload.deductibleSummary || "";
  return { scenarioType, countryNote, scenarioSummary };
}

/** Foundation для учёта выбора сценариев в share/print (поиск, фильтр, A/B). */
export interface ScenarioSelectionContext {
  activeScenarioId?: string | null;
  slotAScenarioId?: string | null;
  slotBScenarioId?: string | null;
}

/** Краткая строка для share/print по контексту выбора (без canonical SEO). При передаче pinnedIds добавляет «(закреплён)» для A/B. */
export function buildScenarioShareSummary(
  context: ScenarioSelectionContext,
  scenarios: SavedRefundScenario[],
  options: { countryId: string; pinnedIds?: string[] }
): string {
  const parts: string[] = [];
  const pinnedSet = options.pinnedIds?.length ? new Set(options.pinnedIds) : null;
  const getLabel = (id: string) => scenarios.find((s) => s.id === id)?.label ?? id;
  const pinned = (id: string) => (pinnedSet?.has(id) ? " (закреплён)" : "");
  if (context.activeScenarioId) {
    parts.push(`Активный: ${getLabel(context.activeScenarioId)}${pinned(context.activeScenarioId)}`);
  }
  if (context.slotAScenarioId) {
    parts.push(`В A: ${getLabel(context.slotAScenarioId)}${pinned(context.slotAScenarioId)}`);
  }
  if (context.slotBScenarioId) {
    parts.push(`В B: ${getLabel(context.slotBScenarioId)}${pinned(context.slotBScenarioId)}`);
  }
  return parts.length ? parts.join(". ") : "";
}

