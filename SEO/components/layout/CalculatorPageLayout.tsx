import type { ReactNode } from "react";

interface CalculatorPageLayoutProps {
  breadcrumbsSlot?: ReactNode;
  heroSlot: ReactNode;
  calculatorSlot: ReactNode;
  explanationSlot?: ReactNode;
  faqSlot?: ReactNode;
  relatedSlot?: ReactNode;
}

export function CalculatorPageLayout({
  breadcrumbsSlot,
  heroSlot,
  calculatorSlot,
  explanationSlot,
  faqSlot,
  relatedSlot
}: CalculatorPageLayoutProps) {
  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        {breadcrumbsSlot && (
          <div className="text-xs text-slate-400">{breadcrumbsSlot}</div>
        )}

        <header className="space-y-1">{heroSlot}</header>

        <section className="grid gap-8 lg:grid-cols-[2fr,1.1fr]">
          <div className="space-y-6">
            {calculatorSlot}
            {explanationSlot}
          </div>
          <aside className="space-y-4">
            {faqSlot}
            {relatedSlot}
          </aside>
        </section>
      </div>
    </main>
  );
}

