import type { ReactNode } from "react";

interface PrintableResultSectionProps {
  heading: string;
  children: ReactNode;
}

export function PrintableResultSection({
  heading,
  children
}: PrintableResultSectionProps) {
  return (
    <section className="space-y-2 rounded-xl border border-slate-800/80 bg-slate-950/70 p-4 text-[11px] text-slate-100 print:border print:bg-white print:text-black">
      <div className="flex items-center justify-between">
        <h2 className="text-xs font-semibold uppercase tracking-wide text-slate-300 print:text-slate-700">
          {heading}
        </h2>
        <span className="text-[10px] text-slate-500 print:text-slate-600">
          Можно распечатать или вложить в письмо
        </span>
      </div>
      {children}
    </section>
  );
}

