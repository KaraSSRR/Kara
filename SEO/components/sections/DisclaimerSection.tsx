interface DisclaimerSectionProps {
  title?: string;
  paragraphs: string[];
}

export function DisclaimerSection({
  title = "Важно помнить",
  paragraphs
}: DisclaimerSectionProps) {
  if (!paragraphs.length) return null;

  return (
    <section className="rounded-2xl border border-amber-700/60 bg-amber-950/40 p-4 shadow-card backdrop-blur">
      <h2 className="mb-2 text-[11px] font-semibold uppercase tracking-wide text-amber-400">
        {title}
      </h2>
      <div className="space-y-2 text-[11px] text-amber-100/90">
        {paragraphs.map((p, idx) => (
          <p key={idx}>{p}</p>
        ))}
      </div>
    </section>
  );
}

