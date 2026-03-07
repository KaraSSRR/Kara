"use client";

import { useState, useEffect, useCallback } from "react";
import {
  loadPinnedScenarioIds,
  savePinnedScenarioIds,
  PINNED_SCENARIOS_STORAGE_KEY,
  type PinnedScenarioId
} from "@/lib/calculations/scenario-library-foundation";

export function usePinnedScenarioIds(): [
  PinnedScenarioId[],
  (next: PinnedScenarioId[] | ((prev: PinnedScenarioId[]) => PinnedScenarioId[])) => void
] {
  const [ids, setIds] = useState<PinnedScenarioId[]>([]);

  useEffect(() => {
    setIds(loadPinnedScenarioIds());
  }, []);

  useEffect(() => {
    if (typeof window === "undefined") return;
    const refresh = () => setIds(loadPinnedScenarioIds());
    const onStorage = (e: StorageEvent) => {
      if (e.key === PINNED_SCENARIOS_STORAGE_KEY) refresh();
    };
    const onVisibility = () => {
      if (document.visibilityState === "visible") refresh();
    };
    window.addEventListener("storage", onStorage);
    document.addEventListener("visibilitychange", onVisibility);
    return () => {
      window.removeEventListener("storage", onStorage);
      document.removeEventListener("visibilitychange", onVisibility);
    };
  }, []);

  const persistAndSet = useCallback(
    (next: PinnedScenarioId[] | ((prev: PinnedScenarioId[]) => PinnedScenarioId[])) => {
      setIds((prev) => {
        const nextVal = typeof next === "function" ? next(prev) : next;
        savePinnedScenarioIds(nextVal);
        return nextVal;
      });
    },
    []
  );

  return [ids, persistAndSet];
}
