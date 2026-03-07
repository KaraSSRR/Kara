\"use client\";

import type { PageReadinessCheck } from \"@/lib/seo/page-readiness\";

interface PageReadinessDebugProps {
  label?: string;
  readiness: PageReadinessCheck;
}

export function PageReadinessDebug({
  label,
  readiness
}: PageReadinessDebugProps) {
  if (process.env.NODE_ENV === \"production\") return null;

  const missing: string[] = [];

  if (!readiness.hasMetadata) missing.push(\"metadata\");
  if (!readiness.hasTrust) missing.push(\"trust\");
  if (!readiness.hasExplanation) missing.push(\"explanation\");
  if (!readiness.hasRelated) missing.push(\"related\");
  if (!readiness.hasLocaleCurrency) missing.push(\"locale/currency\");

  if (missing.length === 0) return null;

  const text = `${label ?? \"page\"}: missing ${missing.join(\", \")}`;

  return (
    <div className=\"pointer-events-none fixed bottom-3 right-3 z-50 max-w-xs rounded-md bg-slate-950/90 px-3 py-2 text-[10px] text-slate-300 ring-1 ring-slate-700/80\">
      <span className=\"pointer-events-auto font-mono uppercase tracking-wide text-slate-400\">
        {text}
      </span>
    </div>
  );
}

