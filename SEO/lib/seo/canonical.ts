import type { CanonicalParams, SeoContext } from "./types";

const BASE_URL = "https://www.example-salary-calculator.com";

export function buildCanonicalPath(params: CanonicalParams): string {
  const path = params.pathname.startsWith("/")
    ? params.pathname
    : `/${params.pathname}`;
  return path.replace(/\/+/g, "/").replace(/\/$/, "") || "/";
}

export function buildCanonicalUrl(params: CanonicalParams): string {
  const path = buildCanonicalPath(params);
  return `${BASE_URL}${path}`;
}

export function resolveCanonicalFromContext(context: SeoContext): string {
  switch (context.pageType) {
    case "home":
      return buildCanonicalUrl({ pathname: "/" });
    default:
      return buildCanonicalUrl({
        pathname:
          (context as any).canonicalPath ??
          (context as any).pathname ??
          "/"
      });
  }
}

