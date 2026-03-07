import type { SeoRouteType } from "@/lib/seo/decisions";

export interface CalculatorPreset {
  defaultMode: "gross-to-net" | "net-to-gross";
  defaultAmount: string;
  defaultPeriod: "monthly" | "yearly";
  helperText?: string;
  comparisonHint?: string;
}

export function getCalculatorPresetForRoute(
  routeType: SeoRouteType
): CalculatorPreset {
  switch (routeType) {
    case "gross-to-net":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "5500",
        defaultPeriod: "monthly",
        helperText:
          "Укажите размер gross-зарплаты и период — мы оценим, сколько останется «на руки» после типичных налогов и взносов. Такой сценарий полезен при сравнении офферов и вилок.",
        comparisonHint:
          "Сохраните один оффер как базовый сценарий и сравните его с альтернативными вариантами gross или периодами выплаты."
      };
    case "net-to-gross":
      return {
        defaultMode: "net-to-gross",
        defaultAmount: "3200",
        defaultPeriod: "monthly",
        helperText:
          "Введите желаемый net-доход и период — калькулятор оценит необходимый gross с учётом налогов и взносов. Это помогает сформулировать реалистичные ожидания по офферу и переговорам.",
        comparisonHint:
          "Попробуйте несколько целей по net, сохраняя сценарии, чтобы увидеть реалистичный диапазон gross-ожиданий."
      };
    case "profession":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "6500",
        defaultPeriod: "monthly",
        helperText:
          "Задайте типичный оффер для этой профессии и посмотрите, какой нетто-доход он даёт. Затем сравните результаты между городами.",
        comparisonHint:
          "Сохраните текущий сценарий, а затем меняйте сумму или период, чтобы понять, как меняется нетто-доход по профессии."
      };
    case "city":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "4800",
        defaultPeriod: "monthly",
        helperText:
          "Введите оффер или текущую зарплату и оцените, какой нетто-доход получится в этом городе с учётом локальных налогов и стоимости жизни.",
        comparisonHint:
          "Зафиксируйте одну сумму как базовый сценарий и сравните её с альтернативами, чтобы оценить, насколько вам комфортен доход в этом городе."
      };
    case "profession-city":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "5500",
        defaultPeriod: "monthly",
        helperText:
          "Используйте этот сценарий, чтобы оценить конкретную профессию в выбранном городе и сравнить её с другими направлениями или локациями.",
        comparisonHint:
          "Сохраните один сценарий для профессии в этом городе и сравните его с другими значениями, чтобы понять чувствительность нетто-дохода."
      };
    case "city-comparison":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "5000",
        defaultPeriod: "monthly",
        helperText:
          "Сравните, как один и тот же gross-оффер будет выглядеть по net и эффективной ставке в двух разных городах.",
        comparisonHint:
          "Используйте одинаковую сумму для обоих городов, чтобы увидеть разницу в налоговой нагрузке и нетто-доходе."
      };
    case "profession-comparison":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "6000",
        defaultPeriod: "monthly",
        helperText:
          "Сравните, как базовый gross-уровень превращается в net для двух разных профессий при схожем уровне дохода.",
        comparisonHint:
          "Попробуйте несколько сумм, чтобы понять, какая профессия даёт более предсказуемый и устойчивый нетто-доход."
      };
    case "generic-calculator":
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "5000",
        defaultPeriod: "monthly",
        helperText:
          "Введите сумму оффера и период, чтобы получить базовый gross ↔ net расчёт. Далее вы сможете перейти на страницы городов и профессий для более точного контекста.",
        comparisonHint:
          "Сохраните один базовый сценарий и сравните его с альтернативными суммами или периодами, прежде чем переходить к деталям по городам и профессиям."
      };
    default:
      return {
        defaultMode: "gross-to-net",
        defaultAmount: "5000",
        defaultPeriod: "monthly"
      };
  }
}

