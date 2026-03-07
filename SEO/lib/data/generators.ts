import {
  loadCountries,
  loadCities,
  loadProfessions
} from "./loaders";
import {
  type ProfessionCityPageInput,
  type Profession,
  type City,
  type Country,
  type CountryRouteInput,
  type CityRouteInput,
  type ProfessionRouteInput,
  type ProfessionCityRouteInput
} from "./types";

function computeProfessionCityQualityScore(options: {
  country: Country;
  city: City;
  profession: Profession;
}): number {
  const countryPriority =
    options.profession.searchPriorityByCountry[options.country.id] ?? 0;
  const cityPriority = options.city.searchPriority;
  const supported =
    options.city.supportedProfessionIds.includes(options.profession.id) ? 1 : 0;

  const score =
    0.4 * countryPriority +
    0.4 * cityPriority +
    0.2 * supported;

  return Math.max(0, Math.min(1, score));
}

export async function buildProfessionCityPageInputFromSlugs(params: {
  countrySlug: string;
  citySlug: string;
  professionSlug: string;
}): Promise<ProfessionCityPageInput | null> {
  const [countries, cities, professions] = await Promise.all([
    loadCountries(),
    loadCities(),
    loadProfessions()
  ]);

  const country = countries.find((c) => c.slug === params.countrySlug);
  if (!country) return null;

  const city = cities.find(
    (c) => c.slug === params.citySlug && c.countryId === country.id
  );
  if (!city) return null;

  const profession = professions.find((p) => p.slug === params.professionSlug);
  if (!profession) return null;

  const qualityScore = computeProfessionCityQualityScore({
    country,
    city,
    profession
  });

  return {
    country,
    city,
    profession,
    qualityScore
  };
}

export async function generateCountryRouteInputs(): Promise<CountryRouteInput[]> {
  const countries = await loadCountries();
  return countries.map((country) => ({
    type: "country",
    country
  }));
}

export async function generateCityRouteInputs(): Promise<CityRouteInput[]> {
  const [countries, cities] = await Promise.all([
    loadCountries(),
    loadCities()
  ]);

  return cities
    .map((city) => {
      const country = countries.find((c) => c.id === city.countryId);
      if (!country) return null;
      return {
        type: "city" as const,
        city,
        country
      };
    })
    .filter((item): item is CityRouteInput => item !== null);
}

export async function generateProfessionRouteInputs(): Promise<ProfessionRouteInput[]> {
  const [countries, professions] = await Promise.all([
    loadCountries(),
    loadProfessions()
  ]);

  const inputs: ProfessionRouteInput[] = [];

  for (const country of countries) {
    for (const profession of professions) {
      const priority = profession.searchPriorityByCountry[country.id] ?? 0;
      if (priority < 0.5) continue;

      inputs.push({
        type: "profession",
        country,
        profession
      });
    }
  }

  return inputs;
}

export async function generateProfessionCityRouteInputs(options?: {
  minQualityScore?: number;
}): Promise<ProfessionCityRouteInput[]> {
  const minScore = options?.minQualityScore ?? 0.6;
  const [countries, cities, professions] = await Promise.all([
    loadCountries(),
    loadCities(),
    loadProfessions()
  ]);

  const inputs: ProfessionCityRouteInput[] = [];

  for (const country of countries) {
    const countryCities = cities.filter((city) => city.countryId === country.id);

    for (const city of countryCities) {
      for (const profession of professions) {
        if (!city.supportedProfessionIds.includes(profession.id)) continue;

        const qualityScore = computeProfessionCityQualityScore({
          country,
          city,
          profession
        });

        if (qualityScore < minScore) continue;

        inputs.push({
          type: "professionCity",
          country,
          city,
          profession,
          qualityScore
        });
      }
    }
  }

  return inputs;
}

export async function generateCountrySlugParams(): Promise<
  { countrySlug: string }[]
> {
  const countries = await loadCountries();
  return countries.map((country) => ({ countrySlug: country.slug }));
}

export async function generateCitySlugParams(): Promise<
  { countrySlug: string; citySlug: string }[]
> {
  const [countries, cities] = await Promise.all([
    loadCountries(),
    loadCities()
  ]);

  return cities
    .map((city) => {
      const country = countries.find((c) => c.id === city.countryId);
      if (!country) return null;
      return {
        countrySlug: country.slug,
        citySlug: city.slug
      };
    })
    .filter(
      (item): item is { countrySlug: string; citySlug: string } =>
        item !== null
    );
}

export async function generateProfessionSlugParams(): Promise<
  { countrySlug: string; professionSlug: string }[]
> {
  const inputs = await generateProfessionRouteInputs();
  return inputs.map((input) => ({
    countrySlug: input.country.slug,
    professionSlug: input.profession.slug
  }));
}

export async function generateProfessionCitySlugParams(options?: {
  minQualityScore?: number;
}): Promise<
  { countrySlug: string; citySlug: string; professionSlug: string }[]
> {
  const inputs = await generateProfessionCityRouteInputs(options);
  return inputs.map((input) => ({
    countrySlug: input.country.slug,
    citySlug: input.city.slug,
    professionSlug: input.profession.slug
  }));
}

