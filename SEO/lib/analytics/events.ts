import type { SeoRouteType } from "@/lib/seo/decisions";
import type { CalculationDirection, SalaryPeriod } from "@/lib/calculations/types";

export type CalculatorEventName =
  | "calculator_used"
  | "comparison_saved"
  | "result_copied"
  | "link_copied"
  | "preset_applied"
  | "comparison_snapshot_copied"
  | "comparison_print_used"
  | "comparison_shared_ready";

export interface CalculatorEventBase {
  name: CalculatorEventName;
  routeType: SeoRouteType;
  mode: CalculationDirection;
  period: SalaryPeriod;
  amount: number | null;
}

export interface CalculatorUsedEvent extends CalculatorEventBase {
  name: "calculator_used";
  hasBaseline: boolean;
}

export interface ComparisonSavedEvent extends CalculatorEventBase {
  name: "comparison_saved";
  baselineGross: number;
  baselineNet: number;
}

export interface ResultCopiedEvent extends CalculatorEventBase {
  name: "result_copied";
}

export interface LinkCopiedEvent extends CalculatorEventBase {
  name: "link_copied";
}

export interface PresetAppliedEvent extends CalculatorEventBase {
  name: "preset_applied";
  presetKey: SeoRouteType;
}

export interface ComparisonSnapshotCopiedEvent extends CalculatorEventBase {
  name: "comparison_snapshot_copied";
}

export interface ComparisonPrintUsedEvent extends CalculatorEventBase {
  name: "comparison_print_used";
}

export interface ComparisonSharedReadyEvent extends CalculatorEventBase {
  name: "comparison_shared_ready";
}

export type CalculatorEvent =
  | CalculatorUsedEvent
  | ComparisonSavedEvent
  | ResultCopiedEvent
  | LinkCopiedEvent
  | PresetAppliedEvent
  | ComparisonSnapshotCopiedEvent
  | ComparisonPrintUsedEvent
  | ComparisonSharedReadyEvent;

export interface AnalyticsAdapter {
  init?: (options?: AnalyticsInitOptions) => void;
  track: (event: CalculatorEvent, context?: AnalyticsContext) => void;
  identify?: (userId: string | null, traits?: Record<string, unknown>) => void;
  setContext?: (context: AnalyticsContext) => void;
}

export interface AnalyticsContext {
  routeType?: SeoRouteType;
  pageType?: SeoRouteType;
  locale?: string;
  [key: string]: unknown;
}

export interface AnalyticsInitOptions {
  context?: AnalyticsContext;
}

let currentAdapter: AnalyticsAdapter | null = null;
let currentContext: AnalyticsContext = {};

export function setAnalyticsAdapter(adapter: AnalyticsAdapter | null): void {
  currentAdapter = adapter;
}

export function initAnalytics(options: AnalyticsInitOptions = {}): void {
  currentContext = options.context ?? {};
  if (!currentAdapter || !currentAdapter.init) return;
  currentAdapter.init(options);
}

export function identifyAnalyticsUser(
  userId: string | null,
  traits?: Record<string, unknown>
): void {
  if (!currentAdapter || !currentAdapter.identify) return;
  currentAdapter.identify(userId, traits);
}

export function updateAnalyticsContext(context: AnalyticsContext): void {
  currentContext = { ...currentContext, ...context };
  if (!currentAdapter || !currentAdapter.setContext) return;
  currentAdapter.setContext(currentContext);
}

export function trackCalculatorEvent(event: CalculatorEvent): void {
  if (!currentAdapter) return;
  currentAdapter.track(event, currentContext);
}

