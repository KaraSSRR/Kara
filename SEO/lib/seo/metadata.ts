import type { Metadata } from "next";
import type { SeoContext, SeoMetadataResult } from "./types";
import { resolveCanonicalFromContext } from "./canonical";

function buildTitle(context: SeoContext): string {
  const year = context.year ?? new Date().getFullYear();

  switch (context.pageType) {
    case "home":
      return "Salary Calculator – Gross to Net & Net to Gross";
    case "gross-to-net":
      return `Gross to Net Salary Calculator ${year}`;
    case "net-to-gross":
      return `Net to Gross Salary Calculator ${year}`;
    case "generic-calculator":
      return `Salary Calculator ${year} – Gross & Net`;
    case "country": {
      const country = (context as any).country;
      return `${country.name} Salary Calculator – Gross & Net ${year}`;
    }
    case "city": {
      const { country, city } = context as any;
      return `${city.name} Salary Calculator in ${country.name} – Gross & Net ${year}`;
    }
    case "profession": {
      const { profession, country } = context as any;
      if (country) {
        return `${profession.name} Salary in ${country.name} – Gross & Net ${year}`;
      }
      return `${profession.name} Salary Calculator – Gross & Net ${year}`;
    }
    case "profession-city": {
      const { profession, city, country } = context as any;
      return `${profession.name} Salary in ${city.name}, ${country.name} – Gross & Net ${year}`;
    }
    case "faq":
      return "Salary FAQs – Gross vs Net, Taxes & More";
    default:
      return "Salary Calculator";
  }
}

function buildDescription(context: SeoContext): string {
  const year = context.year ?? new Date().getFullYear();

  switch (context.pageType) {
    case "home":
      return `Compare gross and net salary for ${year} across countries, cities and professions with a modern, high-quality calculator and clear tax explanations.`;
    case "gross-to-net":
      return `Convert gross salary to net for ${year}. Get instant results with tax breakdowns and explanations tailored to your location.`;
    case "net-to-gross":
      return `Convert net salary to gross for ${year}. Understand how much you need to earn before tax to reach your target take-home pay.`;
    case "generic-calculator":
      return `Flexible salary calculator for ${year}. Switch between gross to net and net to gross, with localized tax logic for supported countries.`;
    case "country": {
      const { country } = context as any;
      return `Calculate gross and net salary in ${country.name} for ${year}. Local tax rules, contributions and clear explanations in one place.`;
    }
    case "city": {
      const { country, city } = context as any;
      return `Check salaries in ${city.name}, ${country.name} for ${year}. Compare gross and net pay with local tax and cost-of-living context.`;
    }
    case "profession": {
      const { profession, country } = context as any;
      if (country) {
        return `See ${profession.name} salary ranges in ${country.name} for ${year}. Calculate gross and net pay with localized tax logic.`;
      }
      return `Discover typical ${profession.name} salaries for ${year}. Calculate gross and net pay and understand what affects your compensation.`;
    }
    case "profession-city": {
      const { profession, city, country } = context as any;
      return `Explore ${profession.name} salaries in ${city.name}, ${country.name} for ${year}. Get gross and net figures plus local market context.`;
    }
    case "faq":
      return `Answers to common questions about gross vs net salary, income tax, and how our salary calculators work in different countries.`;
    default:
      return `Modern salary calculator with localized tax logic, built for accurate gross and net salary comparisons.`;
  }
}

export function buildSeoMetadata(context: SeoContext): SeoMetadataResult {
  const canonicalUrl = resolveCanonicalFromContext(context);
  const title = buildTitle(context);
  const description = buildDescription(context);

  const metadata: Metadata = {
    title,
    description,
    alternates: {
      canonical: canonicalUrl
    },
    openGraph: {
      title,
      description,
      url: canonicalUrl,
      type: "website"
    },
    twitter: {
      card: "summary_large_image",
      title,
      description
    }
  };

  return { metadata, canonicalUrl };
}

