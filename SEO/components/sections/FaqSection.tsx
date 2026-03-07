export interface FaqItemView {
  question: string;
  answer: string;
}

interface FaqSectionProps {
  title?: string;
  items: FaqItemView[];
}

export function FaqSection({ title = "Частые вопросы", items }: FaqSectionProps) {
  if (!items.length) return null;

  return (
    <section className="rounded-2xl border border-slate-800/80 bg-slate-950/70 p-4 shadow-card backdrop-blur">
      <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
        {title}
      </h2>
      <div className="space-y-3">
        {items.map((faq, idx) => (
          <details
            key={idx}
            className="group rounded-xl border border-slate-800/80 bg-slate-950/80 px-3 py-2"
          >
            <summary className="flex cursor-pointer items-center justify-between text-[11px] font-medium text-slate-100">
              <span>{faq.question}</span>
              <span className="ml-2 text-slate-500 group-open:hidden">+</span>
              <span className="ml-2 hidden text-slate-500 group-open:inline">
                −
              </span>
            </summary>
            <p className="mt-2 text-[11px] leading-relaxed text-slate-300">
              {faq.answer}
            </p>
          </details>
        ))}
      </div>
    </section>
  );
}

