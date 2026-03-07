interface LastUpdatedSectionProps {
  label?: string;
  value: string;
}

export function LastUpdatedSection({
  label = "Данные и методология обновлены",
  value
}: LastUpdatedSectionProps) {
  if (!value) return null;

  return (
    <section className="rounded-xl border border-slate-800/80 bg-slate-950/60 px-3 py-2 text-[11px] text-slate-400">
      <span className="font-medium text-slate-200">{label}: </span>
      <span>{value}</span>
    </section>
  );
}

