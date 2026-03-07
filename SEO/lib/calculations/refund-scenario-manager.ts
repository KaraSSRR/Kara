import type { TaxRefundCategoryId } from "./types";
import { getRefundCategoryDisplayLabel } from "./refund-config";
import {
  type RefundScenarioPayload,
  type SavedRefundScenario,
  normalizeSavedScenario,
  validateRefundScenario,
  toSavedScenario,
  renameSavedScenario,
  duplicateSavedScenario,
  buildRefundScenarioPayload
} from "./refund-scenarios";

/** Максимальное количество сохранённых сценариев в компактном списке. */
const MAX_SAVED_SCENARIOS = 5;

export interface ScenarioOperationResult {
  scenarios: SavedRefundScenario[];
  /** Короткое сообщение об ошибке/ограничении операции (опционально). */
  error?: string;
}

function upsertScenario(
  list: SavedRefundScenario[],
  next: SavedRefundScenario
): ScenarioOperationResult {
  const normalized = normalizeSavedScenario(next);
  const health = validateRefundScenario(normalized);
  if (!health.isValid) {
    return {
      scenarios: list,
      error: health.reasons[0] ?? "Сценарий не может быть сохранён для выбранной страны."
    };
  }
  const existingIndex = list.findIndex((s) => s.id === normalized.id);
  if (existingIndex >= 0) {
    const clone = [...list];
    clone[existingIndex] = normalized;
    return { scenarios: clone };
  }
  const merged = [normalized, ...list];
  return {
    scenarios: merged.slice(0, MAX_SAVED_SCENARIOS)
  };
}

export function createSavedScenarioFromPayload(options: {
  list: SavedRefundScenario[];
  payload: RefundScenarioPayload;
  label?: string;
}): ScenarioOperationResult {
  const candidate = toSavedScenario(options.payload, options.label);
  return upsertScenario(options.list, candidate);
}

export function renameSavedScenarioInList(options: {
  list: SavedRefundScenario[];
  id: string;
  newLabel: string;
}): ScenarioOperationResult {
  const trimmed = options.newLabel.trim();
  if (!trimmed) {
    return {
      scenarios: options.list,
      error: "Укажите название сценария."
    };
  }
  const index = options.list.findIndex((s) => s.id === options.id);
  if (index < 0) {
    return { scenarios: options.list };
  }
  const current = options.list[index];
  const renamed = renameSavedScenario(current, trimmed);
  const health = validateRefundScenario(renamed);
  if (!health.isValid) {
    return {
      scenarios: options.list,
      error: health.reasons[0] ?? "Сценарий с таким названием не может быть сохранён."
    };
  }
  const clone = [...options.list];
  clone[index] = renamed;
  return { scenarios: clone };
}

export function duplicateSavedScenarioInList(options: {
  list: SavedRefundScenario[];
  id: string;
}): ScenarioOperationResult {
  const index = options.list.findIndex((s) => s.id === options.id);
  if (index < 0) {
    return { scenarios: options.list };
  }
  const current = options.list[index];
  const duplicated = duplicateSavedScenario(current);
  return upsertScenario(options.list, duplicated);
}

export function removeSavedScenarioFromList(options: {
  list: SavedRefundScenario[];
  id: string;
}): ScenarioOperationResult {
  return {
    scenarios: options.list.filter((s) => s.id !== options.id)
  };
}

export function normalizeSavedScenariosList(
  list: SavedRefundScenario[]
): SavedRefundScenario[] {
  return list
    .map((item) => normalizeSavedScenario(item))
    .filter((item) => validateRefundScenario(item).isValid);
}

export function isScenarioValid(
  scenario: RefundScenarioPayload | SavedRefundScenario | null
): boolean {
  if (!scenario) return false;
  return validateRefundScenario(scenario).isValid;
}

/** Сохранить текущий сценарий вычетов как saved-сценарий для страны/категории. */
export function saveCurrentRefundScenario(options: {
  list: SavedRefundScenario[];
  countryId: string;
  categoryId: TaxRefundCategoryId;
  deductibleAmount: number;
}): ScenarioOperationResult {
  const payload = buildRefundScenarioPayload({
    countryId: options.countryId,
    categoryId: options.categoryId,
    deductibleAmount: options.deductibleAmount,
    origin: "saved"
  });
  return createSavedScenarioFromPayload({
    list: options.list,
    payload
  });
}

export interface AppliedScenarioInputs {
  categoryId: TaxRefundCategoryId;
  deductibleInput: string;
}

/** Подготовить значения полей формы для применения сценария. */
export function buildAppliedScenarioInputs(
  scenario: RefundScenarioPayload | SavedRefundScenario
): AppliedScenarioInputs {
  return {
    categoryId: scenario.categoryId,
    deductibleInput: String(Math.round(scenario.deductibleAmount))
  };
}

/** Короткое сообщение об успешном применении сценария (название, страна, категория). */
export function getApplyScenarioSuccessMessage(
  scenario: RefundScenarioPayload | SavedRefundScenario,
  countryId: string
): string {
  const categoryLabel =
    getRefundCategoryDisplayLabel(countryId, scenario.categoryId) ?? scenario.categoryId;
  return `Применён сценарий «${scenario.label}» (${categoryLabel}).`;
}

/** Сообщение, если сценарий несовместим с текущей страной. */
export function getApplyScenarioIncompatibleMessage(): string {
  return "Этот сценарий не подходит для выбранной страны или категории.";
}


