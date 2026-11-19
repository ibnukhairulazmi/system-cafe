import { request } from "./api";

export { ApiError } from "./api";

export type QrisCharge = {
    code: string;
    amount: number;
    qrString: string;
    expiresAt: string | null;
};

export type QrisStatus = { paid: boolean; expired: boolean };

export async function createQrisCharge(
    code: string,
    type: "table" | "delivery"
): Promise<QrisCharge> {
    const r = await request<{
        code: string;
        amount: number;
        qr_string: string;
        expires_at: string | null;
    }>("/payments/qris", {
        method: "POST",
        body: JSON.stringify({ code, type }),
    });

    return {
        code: r.code,
        amount: r.amount,
        qrString: r.qr_string,
        expiresAt: r.expires_at,
    };
}

export function getQrisStatus(code: string, type: "table" | "delivery") {
    return request<QrisStatus>(
        `/payments/qris/${encodeURIComponent(code)}/status?type=${type}`
    );
}

export function simulateQrisPayment(code: string, type: "table" | "delivery") {
    return request<{ success: boolean; paid: boolean }>("/payments/qris/simulate", {
        method: "POST",
        body: JSON.stringify({ code, type }),
    });
}