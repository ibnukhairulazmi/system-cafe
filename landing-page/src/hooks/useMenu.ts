import { useEffect, useState } from "react";
import { api } from "../lib/api";
import type { Category } from "../types/menu";

export type MenuStatus = "loading" | "ready" | "empty" | "error";

export function useMenu() {
    const [categories, setCategories] = useState<Category[]>([]);
    const [status, setStatus] = useState<MenuStatus>("loading");

    useEffect(() => {
        let cancelled = false;

        api.menu()
            .then((r) => {
                if (cancelled) return;
                setCategories(r.categories);
                setStatus(r.categories.length ? "ready" : "empty");
            })
            .catch((err) => {
                if (cancelled) return;
                console.error("[useMenu] Gagal memuat menu dari API:", err);
                setStatus("error");
            });

        return () => {
            cancelled = true;
        };
    }, []);

    return { categories, status };
}