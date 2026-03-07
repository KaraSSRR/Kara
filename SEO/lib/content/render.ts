import type { TemplateContext } from "./types";

const TOKEN_REGEX = /\{\{(\w+)\}\}/g;

const DEFAULT_LABELS = {
  grossLabel: "gross",
  netLabel: "net",
  year: new Date().getFullYear()
};

/**
 * Подставляет значения из context в строку, заменяя {{token}} на значение.
 * Неизвестные токены остаются как есть. Расширение: добавить поле в TemplateContext и маппинг ниже.
 */
export function renderTemplate(
  text: string,
  context: Partial<TemplateContext> = {}
): string {
  const ctx = { ...DEFAULT_LABELS, ...context };
  const year = ctx.year ?? DEFAULT_LABELS.year;

  const map: Record<string, string> = {
    countryName: ctx.countryName ?? "",
    cityName: ctx.cityName ?? "",
    professionName: ctx.professionName ?? "",
    professionCategory: ctx.professionCategory ?? "",
    calculatorMode: ctx.calculatorMode ?? "",
    grossLabel: ctx.grossLabel ?? DEFAULT_LABELS.grossLabel,
    netLabel: ctx.netLabel ?? DEFAULT_LABELS.netLabel,
    year: String(year),
    currencySymbol: ctx.currencySymbol ?? "",
    currencyCode: ctx.currencyCode ?? ""
  };

  return text.replace(TOKEN_REGEX, (_, key) => map[key] ?? `{{${key}}}`);
}
