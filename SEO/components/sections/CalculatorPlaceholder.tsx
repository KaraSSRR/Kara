interface CalculatorPlaceholderProps {
  contextLabel?: string;
}

export function CalculatorPlaceholder({ contextLabel }: CalculatorPlaceholderProps) {
  return (
    <div className="rounded-2xl border border-slate-800/80 bg-slate-900/70 p-5 shadow-card backdrop-blur">
      <div className="mb-3 flex items-center justify-between">
        <h2 className="text-sm font-semibold text-slate-100">
          Калькулятор зарплаты
        </h2>
        {contextLabel && (
          <span className="text-[11px] text-slate-400">{contextLabel}</span>
        )}
      </div>
      <p className="text-xs text-slate-400">
        Здесь будет интерактивный калькулятор gross ↔ net с учётом налоговых
        правил и выбранного контекста (страна, город, профессия). На этом
        этапе реализован только шаблон страницы и SEO-логика.
      </p>
    </div>
  );
}

