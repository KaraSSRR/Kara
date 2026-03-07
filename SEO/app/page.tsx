import type { Metadata } from "next";
import { buildSeoMetadata } from "../lib/seo/metadata";
import { buildWebApplicationSchema } from "../lib/seo/schema";

export function generateMetadata(): Metadata {
  const { metadata } = buildSeoMetadata({ pageType: "home" });
  const webAppSchema = buildWebApplicationSchema({
    name: "Salary Calculator",
    url: "https://www.example-salary-calculator.com",
    description:
      "Modern salary calculator for gross to net and net to gross salary comparisons across countries, cities, and professions."
  });

  return {
    ...metadata,
    other: {
      ...(metadata as any).other,
      "ld+json": JSON.stringify(webAppSchema)
    }
  };
}

export default function HomePage() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-10 px-4 py-16 sm:px-6 lg:px-8">
        <section className="space-y-6 text-center">
          <span className="inline-flex items-center rounded-full bg-slate-900/80 px-3 py-1 text-xs font-medium text-slate-300 ring-1 ring-slate-700/80">
            Built for programmatic SEO & real users
          </span>
          <h1 className="text-balance text-4xl font-semibold tracking-tight text-slate-50 sm:text-5xl lg:text-6xl">
            Salary calculator for real-world{" "}
            <span className="bg-gradient-to-r from-indigo-400 via-sky-400 to-cyan-300 bg-clip-text text-transparent">
              gross &amp; net
            </span>{" "}
            decisions.
          </h1>
          <p className="mx-auto max-w-2xl text-balance text-sm text-slate-300 sm:text-base">
            Compare gross and net salary across countries, cities, and
            professions. Built for job seekers, developers, marketers, HR, and
            anyone who needs accurate, transparent salary insights.
          </p>
        </section>

        <section className="grid gap-6 lg:grid-cols-[2fr,1.2fr]">
          <div className="rounded-2xl border border-slate-800/80 bg-slate-900/70 p-5 shadow-card backdrop-blur">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-sm font-semibold text-slate-100">
                Start with a quick calculation
              </h2>
              <span className="text-xs text-slate-400">
                Core calculator UI coming next
              </span>
            </div>
            <p className="text-xs text-slate-400">
              This is a placeholder for the interactive calculator module.
              Subsequent steps will add the full calculation engine, data layer,
              and dynamic routing.
            </p>
          </div>

          <aside className="space-y-4">
            <div className="rounded-2xl border border-slate-800/80 bg-slate-950/70 p-4 shadow-card backdrop-blur">
              <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Platform highlights
              </h3>
              <ul className="mt-3 space-y-2 text-xs text-slate-300">
                <li>Programmatic SEO for salary queries</li>
                <li>High-quality, non-spammy landing pages</li>
                <li>Data-driven cities, professions, and countries</li>
              </ul>
            </div>
          </aside>
        </section>
      </div>
    </main>
  );
}

