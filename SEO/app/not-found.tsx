import Link from "next/link";

export default function NotFound() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-16 sm:px-6 lg:px-8">
        <section className="space-y-4">
          <h1 className="text-2xl font-semibold tracking-tight text-slate-50 sm:text-3xl">
            Страница не найдена
          </h1>
          <p className="max-w-xl text-sm text-slate-300">
            Такой страницы нет или она ещё не готова. Проще всего начать с основного
            калькулятора или перейти к сравнению сценариев.
          </p>
        </section>
        <nav className="flex flex-wrap gap-3">
          <Link
            href="/salary-calculator"
            className="rounded-lg border border-slate-700 bg-slate-950/80 px-4 py-2 text-sm font-medium text-slate-100 transition hover:border-slate-500 hover:bg-slate-900/80"
          >
            Калькулятор зарплаты
          </Link>
          <Link
            href="/gross-to-net"
            className="rounded-lg border border-slate-700 bg-slate-950/80 px-4 py-2 text-sm font-medium text-slate-100 transition hover:border-slate-500 hover:bg-slate-900/80"
          >
            Gross → Net
          </Link>
          <Link
            href="/"
            className="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-400 transition hover:border-slate-600 hover:text-slate-100"
          >
            На главную
          </Link>
        </nav>
      </div>
    </main>
  );
}
