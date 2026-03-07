interface CalculatorHeroProps {
  title: string;
  subtitle: string;
  eyebrow?: string;
}

export function CalculatorHero({
  title,
  subtitle,
  eyebrow
}: CalculatorHeroProps) {
  return (
    <section className="space-y-4" aria-labelledby="hero-title">
      {eyebrow && (
        <span className="inline-flex items-center rounded-full bg-slate-900/80 px-3 py-1 text-[11px] font-medium uppercase tracking-wide text-slate-400 ring-1 ring-slate-700/80">
          {eyebrow}
        </span>
      )}
      <h1 id="hero-title" className="text-balance text-3xl font-semibold tracking-tight text-slate-50 sm:text-4xl lg:text-5xl">
        {title}
      </h1>
      <p className="max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">
        {subtitle}
      </p>
    </section>
  );
}

