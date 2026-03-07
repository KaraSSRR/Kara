import Link from "next/link";

export interface RelatedPageLink {
  href: string;
  label: string;
  context?: string;
}

interface RelatedPagesSectionProps {
  title?: string;
  items: RelatedPageLink[];
}

export function RelatedPagesSection({
  title = "Полезные страницы по теме",
  items
}: RelatedPagesSectionProps) {
  if (!items.length) return null;

  return (
    <section className="rounded-2xl border border-slate-800/80 bg-slate-950/70 p-4 shadow-card backdrop-blur">
      <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
        {title}
      </h2>
      <ul className="space-y-2 text-xs">
        {items.map((item, idx) => (
          <li key={idx}>
            <Link
              href={item.href}
              className="flex flex-col rounded-lg border border-slate-800/80 bg-slate-950/60 px-3 py-2 text-slate-100 transition hover:border-slate-600 hover:bg-slate-900/80"
            >
              <span className="font-medium">{item.label}</span>
              {item.context && (
                <span className="text-[11px] text-slate-400">
                  {item.context}
                </span>
              )}
            </Link>
          </li>
        ))}
      </ul>
    </section>
  );
}

