import type { City, Country, Profession } from "./types";

export interface RelatedLink {
  href: string;
  label: string;
  context?: string;
}

function normalizeRelatedLinks(
  links: RelatedLink[],
  options: { currentHref?: string; maxItems: number }
): RelatedLink[] {
  const seen = new Set<string>();
  const result: RelatedLink[] = [];

  for (const link of links) {
    if (options.currentHref && link.href === options.currentHref) continue;
    if (seen.has(link.href)) continue;
    seen.add(link.href);
    result.push(link);
    if (result.length >= options.maxItems) break;
  }

  return result;
}

export function getRelatedForGenericCalculator(options: {
  country: Country | null;
  topCities: City[];
  topProfessions: Profession[];
  currentHref?: string;
}): RelatedLink[] {
  const links: RelatedLink[] = [];

  const cityLinks = options.topCities.slice(0, 3).map((city) => ({
    href: `/salary-calculator/${city.slug}`,
    label: `Калькулятор зарплаты в ${city.name}`,
    context: options.country
      ? `${options.country.name}, локальный gross ↔ net`
      : "локальный gross ↔ net"
  }));

  const professionLinks = options.topProfessions.slice(0, 3).map((profession) => ({
    href: `/salary-calculator/profession/${profession.slug}`,
    label: `Зарплата ${profession.name}`,
    context: "вилка по профессии и gross ↔ net"
  }));

  links.push(...cityLinks, ...professionLinks);

  return normalizeRelatedLinks(links, {
    currentHref: options.currentHref,
    maxItems: 6
  });
}

export function getRelatedForCityPage(options: {
  country: Country;
  city: City;
  professions: Profession[];
  currentHref?: string;
  siblingCities?: City[];
}): RelatedLink[] {
  const links: RelatedLink[] = [];

  for (const profession of options.professions.slice(0, 5)) {
    links.push({
      href: `/salary-calculator/${profession.slug}/${options.city.slug}`,
      label: `${profession.name} в ${options.city.name}`,
      context: `${options.country.name}, локальные вилки и gross ↔ net`
    });
  }

  if (options.siblingCities) {
    for (const sibling of options.siblingCities.slice(0, 2)) {
      links.push({
        href: `/salary-calculator/${sibling.slug}`,
        label: `Зарплаты в ${sibling.name}`,
        context: `${options.country.name}, сравнение городов`
      });
    }
  }

  links.push({
    href: `/compare/cities/${options.city.slug}-vs-${options.siblingCities?.[0]?.slug ?? options.city.slug}`,
    label: `Сравнить ${options.city.name} с другим городом`,
    context: "сравнение gross ↔ net и налоговой нагрузки"
  });

  return normalizeRelatedLinks(links, {
    currentHref: options.currentHref,
    maxItems: 6
  });
}

export function getRelatedForProfessionPage(options: {
  country: Country;
  profession: Profession;
  cities: City[];
  currentHref?: string;
  relatedProfessions?: Profession[];
}): RelatedLink[] {
  const links: RelatedLink[] = [];

  for (const city of options.cities.slice(0, 5)) {
    links.push({
      href: `/salary-calculator/${options.profession.slug}/${city.slug}`,
      label: `${options.profession.name} в ${city.name}`,
      context: `${options.country.name}, сравнение с другими городами`
    });
  }

  if (options.relatedProfessions) {
    for (const prof of options.relatedProfessions.slice(0, 2)) {
      links.push({
        href: `/salary-calculator/profession/${prof.slug}`,
        label: `Зарплата ${prof.name}`,
        context: "родственная профессия"
      });
    }
  }

  links.push({
    href: `/compare/professions/${options.profession.slug}-vs-${options.relatedProfessions?.[0]?.slug ?? options.profession.slug}`,
    label: `Сравнить профессию ${options.profession.name}`,
    context: "сравнение профессий по gross/net"
  });

  return normalizeRelatedLinks(links, {
    currentHref: options.currentHref,
    maxItems: 7
  });
}

export function getRelatedForProfessionCityPage(options: {
  country: Country;
  city: City;
  profession: Profession;
  siblingCities: City[];
  currentHref?: string;
}): RelatedLink[] {
  const links: RelatedLink[] = [
    {
      href: `/salary-calculator/${options.city.slug}`,
      label: `Калькулятор зарплаты в ${options.city.name}`,
      context: `${options.country.name}, общий рынок и cost of living`
    },
    {
      href: `/salary-calculator/profession/${options.profession.slug}`,
      label: `Зарплата ${options.profession.name} по ${options.country.name}`,
      context: "национальные вилки и сравнение с другими городами"
    }
  ];

  for (const city of options.siblingCities.slice(0, 3)) {
    links.push({
      href: `/salary-calculator/${options.profession.slug}/${city.slug}`,
      label: `${options.profession.name} в ${city.name}`,
      context: "быстрое сравнение городов"
    });
  }

  return normalizeRelatedLinks(links, {
    currentHref: options.currentHref,
    maxItems: 7
  });
}

