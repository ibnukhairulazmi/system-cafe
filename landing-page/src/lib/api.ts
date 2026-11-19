import type { Category } from "../types/menu";

const BASE = import.meta.env.VITE_API_BASE ?? "/api/public";

export class ApiError extends Error {
    status: number;
    errors?: Record<string, string[]>;

    constructor(status: number, message: string, errors?: Record<string, string[]>) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

export type SiteInfo = {
    name: string; tagline: string; address: string[]; hours: string[];
    phone: string; whatsapp: string; maps_url: string; instagram: string;
};

export type ReservationItemPayload = {
    product_id?: number;
    quantity: number;
    note?: string;
};

export type ReservationPayload = {
    name: string;
    phone: string;
    date: string;
    time: string;
    party_size: number;
    table_id: number;
    payment_method?: "qris" | "transfer" | "cash";
    notes?: string;
    items: ReservationItemPayload[];
};

export type ReservationResponse = {
    code: string;
    snap_token?: string | null;
    dp_amount: number;
    total_amount: number;
    hold_expires_at: string | null;
    table?: string | null;
};

export type TableSlotOption = {
    id: number;
    name: string;
    area: string | null;
    capacity: number;
};

export type ReservationSlot = {
    time: string;
    available: boolean;
    tables: TableSlotOption[];
};

export type ReservationStatusResponse = {
    code: string;
    status: "awaiting_payment" | "confirmed" | "expired";
    payment_status: string | null;
    table: string | null;
    date: string;
    time: string;
    dp_amount: number;
    paid_amount: number;
    snap_token: string | null; 
    hold_expires_at: string | null;
    customer_name?: string;
    customer_phone?: string;
    party_size?: number;
    notes?: string;
    total_amount?: number;
    items?: Array<{
        name: string;
        price: number;
        quantity: number;
        subtotal: number;
        note?: string;
    }>;
};

export type DeliveryOrderPayload = {
    nama: string;
    wa: string;
    alamat: string;
    patokan?: string;
    delivery_tier: "saving" | "fast";
    tanggal?: string;
    jam?: string;
    jadwal_tipe?: "asap" | "scheduled";
    payment_method: "qris" | "transfer" | "cash";
    catatan?: string;
    items: ReservationItemPayload[];
};

export async function request<T>(path: string, init?: RequestInit): Promise<T> {
    const res = await fetch(`${BASE}${path}`, {
        ...init,
        headers: { Accept: "application/json", "Content-Type": "application/json" },
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) throw new ApiError(res.status, body.message ?? "Terjadi kesalahan.", body.errors);
    return body as T;
}

export const api = {
    menu: () => request<{ categories: Category[]; tax: { enabled: boolean; rate: number } }>("/menu"),
    site: () => request<SiteInfo>("/site"),

    availability: (date: string, partySize: number, signal?: AbortSignal) =>
        request<{ slots: ReservationSlot[] }>(
            `/reservations/availability?date=${encodeURIComponent(date)}&party_size=${partySize}`,
            { signal }
        ),

    reserve: (p: ReservationPayload) =>
        request<ReservationResponse>("/reservations", {
            method: "POST",
            body: JSON.stringify({
                name: p.name,
                phone: p.phone,
                date: p.date,
                time: p.time,
                party_size: p.party_size,
                table_id: p.table_id,
                payment_method: p.payment_method ?? "qris",
                notes: p.notes,
                items: p.items.map((i) => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    note: i.note,
                })),
            }),
        }),

    reservationStatus: (code: string) =>
        request<ReservationStatusResponse>(`/reservations/${encodeURIComponent(code)}`),

    delivery: (payload: DeliveryOrderPayload) =>
        request<{ code: string; message: string }>("/delivery-orders", {
            method: "POST",
            body: JSON.stringify(payload),
        }),
};