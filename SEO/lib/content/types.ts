/**
 * Контекст для подстановки токенов в шаблоны контента и FAQ.
 * Расширяемый: новые поля добавляются здесь и в renderTemplate.
 */
export interface TemplateContext {
  countryName?: string;
  cityName?: string;
  professionName?: string;
  professionCategory?: string;
  calculatorMode?: string;
  grossLabel?: string;
  netLabel?: string;
  year?: number;
  currencySymbol?: string;
  currencyCode?: string;
}
