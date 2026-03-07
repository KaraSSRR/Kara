"use client";

import { useState, useEffect, useCallback } from "react";
import {
  loadSavedRefundScenarios,
  saveSavedRefundScenarios,
  SAVED_REFUND_SCENARIOS_STORAGE_KEY,
  type SavedRefundScenario
} from "@/lib/calculations/refund-scenarios";

/** Синхронизация списка сохранённых сценариев: storage event (другая вкладка) + visibility (возврат на вкладку). */
export function useSavedRefundScenariosSync(): [
  SavedRefundScenario[],
  (next: SavedRefundScenario[] | ((prev: SavedRefundScenario[]) => SavedRefundScenario[])) => void
] {
  const [scenarios, setScenarios] = useState<SavedRefundScenario[]>([]);

  useEffect(() => {
    setScenarios(loadSavedRefundScenarios());
  }, []);

  useEffect(() => {
    if (typeof window === "undefined") return;
    const refresh = () => setScenarios(loadSavedRefundScenarios());
    const onStorage = (e: StorageEvent) => {
      if (e.key === SAVED_REFUND_SCENARIOS_STORAGE_KEY) refresh();
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
    (next: SavedRefundScenario[] | ((prev: SavedRefundScenario[]) => SavedRefundScenario[])) => {
      setScenarios((prev) => {
        const nextVal = typeof next === "function" ? next(prev) : next;
        saveSavedRefundScenarios(nextVal);
        return nextVal;
      });
    },
    []
  );

  return [scenarios, persistAndSet];
}
