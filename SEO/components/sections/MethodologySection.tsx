interface MethodologySectionProps {
  title?: string;
  paragraphs: string[];
}

export function MethodologySection({
  title = "Как устроен калькулятор",
  paragraphs
}: MethodologySectionProps) {
  if (!paragraphs.length) return null;

  return (
    <section className="rounded-2xl border border-slate-800/80 bg-slate-950/70 p-5 shadow-card backdrop-blur">
      <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
        {title}
      </h2>
      <div className="space-y-3 text-[11px] text-slate-300">
        {paragraphs.map((p, idx) => (
          <p key={idx}>{p}</p>
        ))}
      </div>
    </section>
  );
}

