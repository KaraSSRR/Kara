import type { SeoRouteType, SeoDecision } from "./decisions";
import type { PageReadinessCheck, PageReadinessInput } from "./page-readiness";
import { checkPageReadiness } from "./page-readiness";
import type { CountryReadiness } from "@/lib/data/country-context";
import { getCountryReadiness } from "@/lib/data/country-context";
import type { Country } from "@/lib/data/types";

export interface PageAuditInput extends PageReadinessInput {
  routeType: SeoRouteType;
  /** Явное знание о наличии metadata (если доступно). */
  hasMetadataExplicit?: boolean;
  /** Присутствует ли json-ld на странице (если известно). */
  hasJsonLd?: boolean;
  /** SEO-решение для маршрута (если уже посчитано). */
  seoDecision?: SeoDecision | null;
  /** Идентификатор страны и список стран для country-level аудита. */
  countryId?: string | null;
  countries?: Country[];
}

export interface PageAuditReport {
  routeType: SeoRouteType;
  readiness: PageReadinessCheck;
  issues: string[];
  hasJsonLd?: boolean;
  seoDecision?: SeoDecision | null;
  countryReadiness?: CountryReadiness;
}

export type PageIssueSeverity = "ok" | "low" | "medium" | "high";

export interface PageHealthSummary {
  routeType: SeoRouteType;
  issues: string[];
  severity: PageIssueSeverity;
  readiness: PageReadinessCheck;
  seoDecision?: SeoDecision | null;
  hasJsonLd?: boolean;
  countryReadiness?: CountryReadiness;
}

export function runPageAudit(input: PageAuditInput): PageAuditReport {
  const readiness = checkPageReadiness(input);
  const issues: string[] = [];

  if (input.hasMetadataExplicit === false || !readiness.hasMetadata) {
    issues.push("metadata");
  }
  if (!readiness.hasTrust) {
    issues.push("trust");
  }
  if (!readiness.hasExplanation) {
    issues.push("explanation");
  }
  if (!readiness.hasRelated) {
    issues.push("related");
  }
  if (!readiness.hasLocaleCurrency) {
    issues.push("locale/currency");
  }
  if (input.hasJsonLd === false) {
    issues.push("json-ld");
  }
  if (!input.seoDecision) {
    issues.push("seo-decision");
  }

  let countryReadiness: CountryReadiness | undefined;
  if (input.countryId && input.countries) {
    countryReadiness = getCountryReadiness(input.countries, input.countryId);
    if (!countryReadiness.hasCountry) {
      issues.push("country-data");
    }
    if (!countryReadiness.hasTaxConfig) {
      issues.push("tax-config");
    }
  }

  return {
    routeType: input.routeType,
    readiness,
    issues,
    hasJsonLd: input.hasJsonLd,
    seoDecision: input.seoDecision,
    countryReadiness
  };
}

export function buildPageHealthSummary(report: PageAuditReport): PageHealthSummary {
  let severity: PageIssueSeverity = "ok";
  if (report.issues.length > 0) {
    const critical = report.issues.some((issue) =>
      ["metadata", "seo-decision", "country-data", "tax-config"].includes(issue)
    );
    if (critical) {
      severity = "high";
    } else if (report.issues.length >= 3) {
      severity = "medium";
    } else {
      severity = "low";
    }
  }

  return {
    routeType: report.routeType,
    issues: report.issues,
    severity,
    readiness: report.readiness,
    seoDecision: report.seoDecision,
    hasJsonLd: report.hasJsonLd,
    countryReadiness: report.countryReadiness
  };
}

export function logPageAuditInDev(label: string, input: PageAuditInput): void {
  if (process.env.NODE_ENV !== "development") return;
  const report = runPageAudit(input);
  if (!report.issues.length) return;

  // Логируем компактно, без спама
  // eslint-disable-next-line no-console
  console.warn("[page-audit]", label, {
    routeType: report.routeType,
    issues: report.issues
  });
}

export interface BatchPageAuditItem {
  label: string;
  report: PageAuditReport;
  summary: PageHealthSummary;
}

export function runBatchPageAudit(
  items: { label: string; input: PageAuditInput }[]
): BatchPageAuditItem[] {
  return items.map((item) => {
    const report = runPageAudit(item.input);
    const summary = buildPageHealthSummary(report);
    return {
      label: item.label,
      report,
      summary
    };
  });
}


