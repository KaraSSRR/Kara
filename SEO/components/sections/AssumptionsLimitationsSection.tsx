interface AssumptionsLimitationsSectionProps {
  titleAssumptions?: string;
  titleLimitations?: string;
  assumptions: string[];
  limitations: string[];
}

export function AssumptionsLimitationsSection({
  titleAssumptions = "Допущения",
  titleLimitations = "Ограничения",
  assumptions,
  limitations
}: AssumptionsLimitationsSectionProps) {
  const hasAssumptions = assumptions.length > 0;
  const hasLimitations = limitations.length > 0;
  if (!hasAssumptions && !hasLimitations) return null;

  return (
    <section className="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-5 shadow-card backdrop-blur">
      <div className="space-y-4">
        {hasAssumptions && (
          <div>
            <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
              {titleAssumptions}
            </h2>
            <ul className="space-y-1.5 text-[11px] text-slate-300">
              {assumptions.map((item, idx) => (
                <li key={idx} className="flex gap-2">
                  <span className="text-slate-500">•</span>
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
        )}
        {hasLimitations && (
          <div>
            <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
              {titleLimitations}
            </h2>
            <ul className="space-y-1.5 text-[11px] text-slate-300">
              {limitations.map((item, idx) => (
                <li key={idx} className="flex gap-2">
                  <span className="text-slate-500">•</span>
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </section>
  );
}
