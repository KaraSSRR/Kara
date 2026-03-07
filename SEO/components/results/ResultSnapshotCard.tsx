import type { ReactNode } from "react";

interface ResultSnapshotCardProps {
  title: string;
  summaryLines: ReactNode[];
  contextLabel?: string;
}

export function ResultSnapshotCard({
  title,
  summaryLines,
  contextLabel
}: ResultSnapshotCardProps) {
  return (
    <section className="space-y-2 rounded-xl border border-slate-800/80 bg-slate-950/70 p-3.5 text-[11px] text-slate-100 print:border print:bg-white print:text-black">
      <div className="flex items-center justify-between gap-2">
        <span className="font-semibold text-slate-50">{title}</span>
        {contextLabel && (
          <span className="truncate text-[10px] text-slate-400 print:text-slate-600">
            {contextLabel}
          </span>
        )}
      </div>
      <ul className="space-y-1">
        {summaryLines.map((line, idx) => (
          <li key={idx}>{line}</li>
        ))}
      </ul>
    </section>
  );
}

