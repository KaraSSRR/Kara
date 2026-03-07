import { ResultSnapshotCard } from "./ResultSnapshotCard";

interface SnapshotSummary {
  label: string;
  gross: string;
  net: string;
  totalTax: string;
  effectiveTaxRate: string;
}

interface ComparisonSnapshotCardProps {
  baseline: SnapshotSummary;
  current: SnapshotSummary;
}

export function ComparisonSnapshotCard({
  baseline,
  current
}: ComparisonSnapshotCardProps) {
  return (
    <section className="mt-4 space-y-3 rounded-lg border border-slate-800/80 bg-slate-950/70 p-3 text-[11px] text-slate-200 print:border print:bg-white print:text-black">
      <div className="flex items-center justify-between">
        <span className="font-semibold">Сравнение сценариев</span>
        <span className="text-[10px] text-slate-400 print:text-slate-600">
          Подходит для печати и шаринга
        </span>
      </div>
      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        <ResultSnapshotCard
          title="Базовый сценарий"
          contextLabel={baseline.label}
          summaryLines={[
            <>Gross: {baseline.gross}</>,
            <>Net: {baseline.net}</>,
            <>Налоги: {baseline.totalTax}</>,
            <>Эффективная ставка: {baseline.effectiveTaxRate}</>
          ]}
        />
        <ResultSnapshotCard
          title="Текущий сценарий"
          contextLabel={current.label}
          summaryLines={[
            <>Gross: {current.gross}</>,
            <>Net: {current.net}</>,
            <>Налоги: {current.totalTax}</>,
            <>Эффективная ставка: {current.effectiveTaxRate}</>
          ]}
        />
      </div>
    </section>
  );
}

