import { request } from "./api";

type SnapCallbacks = {
    onSuccess?: (result: unknown) => void;
    onPending?: (result: unknown) => void;
    onError?: (result: unknown) => void;
    onClose?: () => void;
};

export type SnapApi = {
    pay: (token: string, callbacks?: SnapCallbacks) => void;
};

export type PaymentConfig = {
    client_key: string;
    is_production: boolean;
};

declare global {
    interface Window {
        snap?: SnapApi;
    }
}

let loading: Promise<SnapApi> | null = null;
let cachedConfig: Promise<PaymentConfig> | null = null;

export function getPaymentConfig(): Promise<PaymentConfig> {
    if (!cachedConfig) {
        cachedConfig = request<PaymentConfig>("/payments/config").catch((err) => {
            cachedConfig = null;
            throw err;
        });
    }
    return cachedConfig;
}

export function loadSnap(): Promise<SnapApi> {
    if (window.snap) return Promise.resolve(window.snap);
    if (loading) return loading;

    loading = (async () => {
        let config: PaymentConfig;
        try {
            config = await getPaymentConfig();
        } catch {
            loading = null;
            throw new Error("Gagal mengambil konfigurasi pembayaran dari server.");
        }

        const clientKey = config.client_key?.trim();
        if (!clientKey) {
            loading = null;
            throw new Error("Konfigurasi pembayaran belum lengkap (client key tidak disetel di server).");
        }

        const isProduction = Boolean(config.is_production);

        return new Promise<SnapApi>((resolve, reject) => {
            const existingScript = document.querySelector<HTMLScriptElement>('script[src*="snap/snap.js"]');
            if (existingScript && window.snap) {
                return resolve(window.snap);
            }

            const script = document.createElement("script");
            script.src = isProduction
                ? "https://app.midtrans.com/snap/snap.js"
                : "https://app.sandbox.midtrans.com/snap/snap.js";
            script.setAttribute("data-client-key", clientKey);
            script.async = true;
            script.onload = () => {
                if (window.snap) {
                    resolve(window.snap);
                } else {
                    loading = null;
                    reject(new Error("Snap script dimuat namun objek snap tidak tersedia."));
                }
            };
            script.onerror = () => {
                loading = null;
                reject(new Error("Tidak dapat memuat halaman pembayaran. Periksa koneksi internet."));
            };
            document.head.appendChild(script);
        });
    })();

    return loading;
}
