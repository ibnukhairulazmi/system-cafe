import { useEffect, useRef, useState } from "react";
import {
    Check,
    Clock,
    Copy,
    CreditCard,
    Download,
    Loader2,
    MessageCircle,
    ShieldCheck,
    X,
} from "lucide-react";
import { QRCodeCanvas } from "qrcode.react";
import { formatRupiah } from "../../lib/format";
import { serif } from "../../lib/styles";
import { api } from "../../lib/api";
import { loadSnap } from "../../lib/snap";
import { createQrisCharge, getQrisStatus, simulateQrisPayment, type QrisCharge } from "../../lib/qris";
import InvoiceModal from "./InvoiceModal";

export type PaymentMethodType = "qris" | "transfer" | "cash";

export type TicketItem = {
    name: string;
    quantity: number;
    price: number;
    subtotal?: number;
    note?: string;
};

export type PaymentDetails = {
    code: string;
    orderType: "table" | "delivery";
    deliveryTier?: "saving" | "fast";
    customerName: string;
    waNumber: string;
    itemsCount: number;
    subtotal: number;
    deliveryFee: number;
    totalAmount: number;
    paymentMethod: PaymentMethodType;
    snapToken?: string;
    dpAmount?: number;
    holdExpiresAt?: string | null;
    date?: string;
    time?: string;
    tableName?: string;
    partySize?: number;
    notes?: string;
    items?: TicketItem[];
    address?: string;
    landmark?: string;
};

type PaymentModalProps = {
    isOpen: boolean;
    onClose: () => void;
    details: PaymentDetails | null;
};

type ContentProps = PaymentModalProps & {
    onPaid: (details: PaymentDetails) => void;
};

type Phase = "idle" | "opening" | "waiting" | "confirmed" | "expired";
type QrPhase = "pending" | "paid" | "expired";

const WA_NUMBER = "6281200000000";
const DP_PERCENT = 0.5;

function formatCountdown(ms: number): string {
    const total = Math.ceil(ms / 1000);
    const m = Math.floor(total / 60);
    const s = total % 60;
    return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
}

export default function PaymentModal(props: PaymentModalProps) {
    
    const [invoice, setInvoice] = useState<PaymentDetails | null>(null);

    const handlePaid = (d: PaymentDetails) => {
        setInvoice(d);
        props.onClose();
    };

    return (
        <>
            <PaymentModalContent
                key={props.details?.code ?? "none"}
                {...props}
                onPaid={handlePaid}
            />
            <InvoiceModal
                isOpen={invoice !== null}
                details={invoice}
                onClose={() => setInvoice(null)}
            />
        </>
    );
}

function PaymentModalContent({ isOpen, onClose, details, onPaid }: ContentProps) {
    const [copiedAccount, setCopiedAccount] = useState(false);
    const [copiedAmount, setCopiedAmount] = useState(false);
    const [copiedQr, setCopiedQr] = useState(false);
    const [simulating, setSimulating] = useState(false);

    const [phase, setPhase] = useState<Phase>("idle");
    const [payError, setPayError] = useState<string | null>(null);

    const [qr, setQr] = useState<QrisCharge | null>(null);
    const [qrError, setQrError] = useState<string | null>(null);
    const [qrPhase, setQrPhase] = useState<QrPhase>("pending");
    const [retryKey, setRetryKey] = useState(0);
    const qrCanvasRef = useRef<HTMLCanvasElement>(null);
    const paidNotifiedRef = useRef(false);

    const [now, setNow] = useState(() => Date.now());

    const code = details?.code;
    const orderType = details?.orderType;
    const isQris = details?.paymentMethod === "qris";
    const isTransfer = details?.paymentMethod === "transfer";
    const isCash = details?.paymentMethod === "cash";
    const useSnap = !isQris && !isTransfer && !isCash && details?.orderType === "table" && !!details?.snapToken;
    const holdExpiresAt = details?.holdExpiresAt ?? null;
    const expiresAt = useSnap ? holdExpiresAt : (qr?.expiresAt ?? null);

    const paid = (useSnap && phase === "confirmed") || (isQris && qrPhase === "paid");

    useEffect(() => {
        if (!isOpen || !expiresAt) return;
        const tick = () => setNow(Date.now());
        const first = setTimeout(tick, 0);
        const t = setInterval(tick, 1000);
        return () => {
            clearTimeout(first);
            clearInterval(t);
        };
    }, [isOpen, expiresAt]);

    useEffect(() => {
        if (!isOpen || !useSnap || !code || phase !== "waiting") return;
        let cancelled = false;

        const check = async () => {
            try {
                const r = await api.reservationStatus(code);
                if (cancelled) return;
                const status = String(r.status);
                if (status === "confirmed") {
                    setPhase("confirmed");
                } else if (status === "expired" || status === "cancelled" || status === "rejected") {
                    setPhase("expired");
                }
            } catch {
            }
        };

        check();
        const t = setInterval(check, 4000);
        return () => {
            cancelled = true;
            clearInterval(t);
        };
    }, [isOpen, useSnap, code, phase]);

    useEffect(() => {
        if (!isOpen || !isQris || !code || !orderType) return;
        let cancelled = false;

        createQrisCharge(code, orderType)
            .then((r) => {
                if (!cancelled) setQr(r);
            })
            .catch((err) => {
                if (cancelled) return;
                setQrError(
                    err instanceof Error ? err.message : "Gagal membuat QRIS. Silakan coba lagi."
                );
            });

        return () => {
            cancelled = true;
        };
    }, [isOpen, isQris, code, orderType, retryKey]);

    useEffect(() => {
        if (!isOpen || !isQris || !code || !orderType || !qr || qrPhase !== "pending") return;
        let cancelled = false;

        const check = async () => {
            try {
                const s = await getQrisStatus(code, orderType);
                if (cancelled) return;
                if (s.paid) setQrPhase("paid");
                else if (s.expired) setQrPhase("expired");
            } catch {
            }
        };

        const t = setInterval(check, 4000);
        return () => {
            cancelled = true;
            clearInterval(t);
        };
    }, [isOpen, isQris, code, orderType, qr, qrPhase]);

    useEffect(() => {
        if (!isOpen || !paid || !details || paidNotifiedRef.current) return;
        paidNotifiedRef.current = true;
        onPaid(details);
    }, [isOpen, paid, details, onPaid]);

    if (!isOpen || !details) return null;

    const isTable = details.orderType === "table";
    const remainingMs = expiresAt ? Math.max(0, Date.parse(expiresAt) - now) : null;

    const timedOut = remainingMs === 0 && phase !== "confirmed" && phase !== "waiting";
    const view: Phase = timedOut ? "expired" : phase;

    const qrView: QrPhase =
        qrPhase === "paid" ? "paid" : qrPhase === "expired" || remainingMs === 0 ? "expired" : "pending";
    const qrLoading = isQris && !qr && !qrError;

    const dpAmount =
        details.dpAmount != null
            ? details.dpAmount
            : Math.ceil(details.totalAmount * DP_PERCENT);
    const amountToPay = isTable
        ? (isCash ? details.totalAmount : Math.min(dpAmount, details.totalAmount))
        : details.totalAmount;

    const handleCopy = (text: string, type: "account" | "amount") => {
        navigator.clipboard.writeText(text);
        if (type === "account") {
            setCopiedAccount(true);
            setTimeout(() => setCopiedAccount(false), 2000);
        } else {
            setCopiedAmount(true);
            setTimeout(() => setCopiedAmount(false), 2000);
        }
    };

    const downloadQr = () => {
        const canvas = qrCanvasRef.current;
        if (!canvas) return;
        const link = document.createElement("a");
        link.href = canvas.toDataURL("image/png");
        link.download = `QRIS-${details.code}.png`;
        link.click();
    };

    const handleCopyQr = () => {
        if (!qr?.qrString) return;
        navigator.clipboard.writeText(qr.qrString);
        setCopiedQr(true);
        setTimeout(() => setCopiedQr(false), 2000);
    };

    const handleSimulatePayment = async () => {
        if (!code || !orderType || simulating) return;
        setSimulating(true);
        try {
            await simulateQrisPayment(code, orderType);
            setQrPhase("paid");
        } catch (e) {
            alert(e instanceof Error ? e.message : "Simulasi pembayaran gagal.");
        } finally {
            setSimulating(false);
        }
    };

    const retryQr = () => {
        setQr(null);
        setQrError(null);
        setRetryKey((k) => k + 1);
    };

    const payNow = async () => {
        if (!details.snapToken) return;
        setPayError(null);
        setPhase("opening");
        try {
            const snap = await loadSnap();
            snap.pay(details.snapToken, {
                onSuccess: () => setPhase("waiting"),
                onPending: () => setPhase("waiting"),
                onError: () => {
                    setPhase("idle");
                    setPayError("Pembayaran gagal. Silakan coba lagi.");
                },
                onClose: () => setPhase((p) => (p === "opening" ? "idle" : p)),
            });
        } catch (err) {
            setPhase("idle");
            setPayError(
                err instanceof Error ? err.message : "Tidak dapat membuka halaman pembayaran."
            );
        }
    };

    const waMessage =
        useSnap || isQris
            ? `Halo Ruang Teduh, saya ingin bertanya tentang ${details.orderType === "table" ? "reservasi" : "pesanan"} ${details.code} atas nama ${details.customerName}.`
            : `Halo Ruang Teduh, saya telah melakukan pemesanan & pembayaran:
• Kode: ${details.code}
• Nama: ${details.customerName}
• Tipe: ${details.orderType === "table" ? "Reservasi Meja" : `Pesan Antar (${details.deliveryTier === "fast" ? "Fast Track" : "Hemat"})`}
• Metode Pembayaran: ${details.paymentMethod === "transfer" ? "Transfer Bank (BCA)" : "Bayar di Tempat"}
• ${isCash ? "Total Tagihan" : "DP Dibayar"}: ${formatRupiah(amountToPay)}
${isCash ? "" : `• Total Pesanan: ${formatRupiah(details.totalAmount)}\n`}
Mohon dicek dan dikonfirmasi, terima kasih!`;

    const waUrl = `https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(waMessage)}`;

    const title = useSnap
        ? view === "confirmed"
            ? "Reservasi Dikonfirmasi"
            : view === "expired"
                ? "Reservasi Hangus"
                : "Selesaikan Pembayaran DP"
        : isQris
            ? qrView === "paid"
                ? "DP Diterima"
                : qrView === "expired"
                    ? "QRIS Kedaluwarsa"
                    : "Bayar DP via QRIS"
            : isCash
                ? "Konfirmasi Pesanan"
                : "Selesaikan Pembayaran DP";

    const remainingNote =
        details.orderType === "table"
            ? "dibayar saat tiba di cafe"
            : "dibayar saat pesanan diterima";

    const closeLabel =
        (useSnap && (view === "idle" || view === "opening")) || (isQris && qrView === "pending")
            ? "Tutup (bayar nanti sebelum waktu habis)"
            : "Tutup & Selesai";

    const paidNotice = (
        <div className="flex items-center gap-2 rounded-lg border border-[#D5DDCB] bg-[#F7FAF3] p-4 text-xs font-semibold text-[#5E7A45]">
            <Loader2 className="h-4 w-4 animate-spin" />
            <span>Pembayaran diterima. Membuka invoice...</span>
        </div>
    );

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
            {}
            <div
                className="fixed inset-0 bg-[#2B2623]/50 backdrop-blur-xs transition-opacity"
                onClick={onClose}
                aria-hidden="true"
            />

            <div className="relative flex max-h-[92vh] w-full max-w-lg flex-col rounded-xl border border-[#EAE4DC] bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-[#EAE4DC] px-6 py-4">
                    <div>
                        <span className="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#7A5238]">
                            Instruksi Pembayaran
                        </span>
                        <h3 className={`${serif} text-xl text-[#2B2623]`}>{title}</h3>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md p-1.5 text-[#7A726B] hover:bg-[#F5F2EB] hover:text-[#2B2623] transition-colors"
                        aria-label="Tutup"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    {}
                    <div className="rounded-lg border border-[#EAE4DC] bg-[#FAF8F5] p-4 space-y-3">
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-[#7A726B]">
                                {details.orderType === "table" ? "Kode Reservasi" : "Kode Pesanan"}
                            </span>
                            <span className="font-mono text-xs font-bold text-[#7A5238] px-2 py-0.5 ">
                                {details.code}
                            </span>
                        </div>

                        <div className="space-y-1.5 text-xs border-t border-[#EAE4DC]/80 pt-2.5">
                            <div className="flex justify-between text-[#7A726B]">
                                <span>Pemesan</span>
                                <span className="font-medium text-[#2B2623]">{details.customerName}</span>
                            </div>
                            <div className="flex justify-between text-[#7A726B]">
                                <span>Jenis Pesanan</span>
                                <span className="font-medium text-[#2B2623]">
                                    {details.orderType === "table" ? "Reservasi Meja" : "Pesan Antar"}
                                    {details.orderType === "delivery" && (
                                        <span className="ml-1 text-[11px] text-[#7A5238]">
                                            ({details.deliveryTier === "fast" ? "Fast Track" : "Hemat"})
                                        </span>
                                    )}
                                </span>
                            </div>
                            <div className="flex justify-between text-[#7A726B]">
                                <span>Pesanan Menu ({details.itemsCount} item)</span>
                                <span className="text-[#2B2623]">{formatRupiah(details.subtotal)}</span>
                            </div>
                            {details.deliveryFee > 0 && (
                                <div className="flex justify-between text-[#7A726B]">
                                    <span>Ongkos Kirim</span>
                                    <span className="text-[#2B2623]">{formatRupiah(details.deliveryFee)}</span>
                                </div>
                            )}
                            {!isCash && (
                                <div className="flex justify-between text-[#7A726B]">
                                    <span>Total Pesanan</span>
                                    <span className="text-[#2B2623]">{formatRupiah(details.totalAmount)}</span>
                                </div>
                            )}
                        </div>

                        <div className="flex items-center justify-between border-t border-[#EAE4DC] pt-2.5">
                            <div>
                                <span className="text-xs font-semibold text-[#2B2623]">
                                    {isCash ? "Total Tagihan" : "Pembayaran Awal"}
                                </span>
                                {!isCash && (
                                    <span className="block text-[10px] text-[#7A726B]">
                                        Sisa pelunasan {remainingNote}
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-lg font-bold text-[#7A5238]">
                                    {formatRupiah(amountToPay)}
                                </span>
                                {!useSnap && !isQris && (
                                    <button
                                        type="button"
                                        onClick={() => handleCopy(String(amountToPay), "amount")}
                                        className="p-1 text-[#7A726B] hover:text-[#2B2623] transition-colors"
                                        title="Salin nominal"
                                        aria-label="Salin nominal tagihan"
                                    >
                                        {copiedAmount ? (
                                            <Check className="h-3.5 w-3.5 text-emerald-600" />
                                        ) : (
                                            <Copy className="h-3.5 w-3.5" />
                                        )}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {useSnap ? (
                        <div className="space-y-3">
                            {(view === "idle" || view === "opening") && (
                                <>
                                    {remainingMs !== null && (
                                        <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                                            <Clock className="mt-0.5 h-4 w-4 shrink-0" />
                                            <p className="leading-relaxed">
                                                Selesaikan pembayaran dalam{" "}
                                                <span className="font-mono font-bold">
                                                    {formatCountdown(remainingMs)}
                                                </span>
                                                . Meja ditahan selama waktu ini. Jika DP tidak dibayar,
                                                reservasi otomatis hangus.
                                            </p>
                                        </div>
                                    )}

                                    {payError && (
                                        <div className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
                                            {payError}
                                        </div>
                                    )}

                                    <button
                                        type="button"
                                        onClick={payNow}
                                        disabled={view === "opening"}
                                        className="flex w-full items-center justify-center gap-2 rounded-md bg-[#7A5238] py-3 text-xs font-semibold text-white transition-colors hover:bg-[#654229] disabled:opacity-60"
                                    >
                                        {view === "opening" ? (
                                            <>
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                <span>Membuka pembayaran...</span>
                                            </>
                                        ) : (
                                            <>
                                                <CreditCard className="h-4 w-4" />
                                                <span>Bayar DP {formatRupiah(amountToPay)}</span>
                                            </>
                                        )}
                                    </button>
                                    <p className="text-center text-[11px] text-[#7A726B]">
                                        Pilih QRIS, transfer bank, atau e-wallet di halaman pembayaran.
                                        Reservasi otomatis dikonfirmasi setelah DP masuk.
                                    </p>
                                </>
                            )}

                            {view === "waiting" && (
                                <div className="space-y-3 rounded-lg border border-sky-200 bg-sky-50 p-4 text-xs text-sky-900">
                                    <div className="flex items-center gap-2 font-semibold">
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        <span>Menunggu konfirmasi pembayaran...</span>
                                    </div>
                                    <p className="leading-relaxed">
                                        Jangan tutup halaman ini. Jika kamu memilih transfer/VA dan belum
                                        membayar, kamu bisa membuka kembali halaman pembayaran.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={payNow}
                                        className="rounded-md border border-sky-300 bg-white px-3 py-1.5 font-medium text-sky-800 hover:bg-sky-100"
                                    >
                                        Buka halaman pembayaran lagi
                                    </button>
                                </div>
                            )}

                            {view === "confirmed" && paidNotice}

                            {view === "expired" && (
                                <div className="space-y-2 rounded-lg border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
                                    <div className="font-semibold">Waktu pembayaran DP telah habis</div>
                                    <p className="leading-relaxed">
                                        Reservasi ini hangus dan meja dilepas. Silakan buat reservasi baru.
                                        Jika kamu sudah membayar, hubungi kami via WhatsApp dengan menyebut kode
                                        reservasi.
                                    </p>
                                </div>
                            )}
                        </div>
                    ) : isCash ? (
                        <div className="rounded-lg border border-[#D5DDCB] bg-[#F7FAF3] p-4 space-y-2 text-xs">
                            <div className="flex items-center gap-2 text-[#5E7A45] font-semibold">
                                <ShieldCheck className="h-4 w-4" />
                                <span>
                                    {details.orderType === "table"
                                        ? "Pembayaran Saat Kedatangan"
                                        : "Bayar Tunai ke Kurir (COD)"}
                                </span>
                            </div>
                            <p className="text-[#7A726B] leading-relaxed">
                                {details.orderType === "table"
                                    ? "Meja dan hidangan pesananmu telah dicatat. Mohon tunjukkan kode reservasi ini ke kasir Ruang Teduh saat kamu tiba."
                                    : "Siapkan uang pas saat kurir mengantarkan pesanan ke alamatmu. Barista kami sedang menyiapkan sajian terbaik untukmu."}
                            </p>
                        </div>
                    ) : isQris ? (
                        <div className="space-y-3">
                            {qrView === "pending" && (
                                <>
                                    {remainingMs !== null && (
                                        <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                                            <Clock className="mt-0.5 h-4 w-4 shrink-0" />
                                            <p className="leading-relaxed">
                                                Bayar DP sebelum{" "}
                                                <span className="font-mono font-bold">
                                                    {formatCountdown(remainingMs)}
                                                </span>{" "}
                                                berakhir. QR hanya berlaku untuk pesanan ini.
                                            </p>
                                        </div>
                                    )}

                                    {qrLoading && (
                                        <div className="flex items-center justify-center gap-2 rounded-lg border border-[#EAE4DC] bg-white p-8 text-xs text-[#7A726B]">
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                            <span>Membuat kode QRIS...</span>
                                        </div>
                                    )}

                                    {qrError && (
                                        <div className="space-y-2 rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
                                            <p>{qrError}</p>
                                            <button
                                                type="button"
                                                onClick={retryQr}
                                                className="rounded-md border border-rose-300 bg-white px-3 py-1.5 font-medium text-rose-800 hover:bg-rose-100"
                                            >
                                                Coba lagi
                                            </button>
                                        </div>
                                    )}

                                    {qr && (
                                        <div className="flex flex-col items-center rounded-lg border border-[#EAE4DC] bg-white p-5 text-center">
                                            <div className="w-full max-w-[260px] rounded-lg border border-stone-200 bg-white p-3.5 shadow-xs">
                                                <div className="flex items-center justify-between border-b border-stone-100 pb-1.5">
                                                    <span className="text-[11px] font-bold tracking-wider text-rose-700">
                                                        QRIS
                                                    </span>
                                                    <span className="text-[9px] font-medium text-stone-400">GPN / ASPI</span>
                                                </div>

                                                <div className="my-3 flex items-center justify-center">
                                                    <QRCodeCanvas
                                                        ref={qrCanvasRef}
                                                        value={qr.qrString}
                                                        size={440}
                                                        level="M"
                                                        marginSize={3}
                                                        bgColor="#FFFFFF"
                                                        fgColor="#000000"
                                                        style={{ width: 210, height: 210 }}
                                                    />
                                                </div>

                                                <div className="text-[11px] font-semibold text-stone-800">
                                                    RUANG TEDUH COFFEE
                                                </div>
                                            </div>

                                            <div className="mt-3 flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={downloadQr}
                                                    className="inline-flex items-center gap-1.5 rounded-md border border-[#DDD6CC] bg-white px-3 py-1.5 text-xs font-medium text-[#7A5238] transition-colors hover:bg-[#F5F2EB]"
                                                >
                                                    <Download className="h-3.5 w-3.5" />
                                                    <span>Unduh QR</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={handleCopyQr}
                                                    className="inline-flex items-center gap-1.5 rounded-md border border-[#DDD6CC] bg-white px-3 py-1.5 text-xs font-medium text-[#7A5238] transition-colors hover:bg-[#F5F2EB]"
                                                >
                                                    {copiedQr ? (
                                                        <>
                                                            <Check className="h-3.5 w-3.5 text-emerald-600" />
                                                            <span>Tersalin</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy className="h-3.5 w-3.5" />
                                                            <span>Salin Kode QR</span>
                                                        </>
                                                    )}
                                                </button>
                                            </div>

                                            {}
                                            <div className="mt-3 w-full border-t border-dashed border-[#EAE4DC] pt-3">
                                                <button
                                                    type="button"
                                                    onClick={handleSimulatePayment}
                                                    disabled={simulating}
                                                    className="inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-[#D5DDCB] bg-[#F7FAF3] py-2 text-xs font-semibold text-[#5E7A45] transition-colors hover:bg-[#EDF4E7] disabled:opacity-50"
                                                >
                                                    {simulating ? (
                                                        <>
                                                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                                            <span>Memverifikasi Pembayaran...</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <ShieldCheck className="h-4 w-4" />
                                                            <span>Simulasikan Pembayaran Sukses (Sandbox)</span>
                                                        </>
                                                    )}
                                                </button>
                                                <p className="mt-1 text-[10px] text-stone-500">
                                                    Klik tombol di atas untuk mensimulasikan pembayaran tanpa membuka simulator Midtrans.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}

                            {qrView === "paid" && paidNotice}

                            {qrView === "expired" && (
                                <div className="space-y-2 rounded-lg border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
                                    <div className="font-semibold">Waktu pembayaran DP telah habis</div>
                                    <p className="leading-relaxed">
                                        QR sudah tidak berlaku dan pesanan ini dibatalkan. Silakan buat pesanan
                                        baru. Jika kamu sudah membayar, hubungi kami via WhatsApp dengan
                                        menyebut kode pesanan.
                                    </p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-3">
                            <div className="rounded-lg border border-[#EAE4DC] bg-white p-4 space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-[#2B2623]">Bank BCA</span>
                                    <span className="text-[10px] text-[#7A726B]">Transfer Manual</span>
                                </div>
                                <div className="flex items-center justify-between rounded bg-[#FAF8F5] p-2.5 border border-[#EAE4DC]">
                                    <div>
                                        <div className="font-mono text-sm font-bold text-[#2B2623]">
                                            8830-1234-5678-90
                                        </div>
                                        <div className="text-[10px] text-[#7A726B]">
                                            a.n. PT Ruang Teduh Nusantara
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => handleCopy("88301234567890", "account")}
                                        className="inline-flex items-center gap-1 rounded bg-white px-2.5 py-1 text-xs font-medium text-[#7A5238] border border-[#DDD6CC] hover:bg-[#F5F2EB] transition-colors"
                                    >
                                        {copiedAccount ? (
                                            <>
                                                <Check className="h-3 w-3 text-emerald-600" />
                                                <span>Tersalin</span>
                                            </>
                                        ) : (
                                            <>
                                                <Copy className="h-3 w-3" />
                                                <span>Salin Rekening</span>
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#EAE4DC] bg-white p-4 space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-[#2B2623]">Bank Mandiri</span>
                                    <span className="text-[10px] text-[#7A726B]">Transfer Manual</span>
                                </div>
                                <div className="flex items-center justify-between rounded bg-[#FAF8F5] p-2.5 border border-[#EAE4DC]">
                                    <div>
                                        <div className="font-mono text-sm font-bold text-[#2B2623]">
                                            137-00-9876-5432
                                        </div>
                                        <div className="text-[10px] text-[#7A726B]">
                                            a.n. Ruang Teduh Cafe
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => handleCopy("1370098765432", "account")}
                                        className="inline-flex items-center gap-1 rounded bg-white px-2.5 py-1 text-xs font-medium text-[#7A5238] border border-[#DDD6CC] hover:bg-[#F5F2EB] transition-colors"
                                    >
                                        <Copy className="h-3 w-3" />
                                        <span>Salin</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {}
                <div className="border-t border-[#EAE4DC] bg-[#FAF8F5] px-6 py-4 space-y-2.5">
                    <a
                        href={waUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex w-full items-center justify-center gap-2 rounded-md bg-[#5E7A45] py-2.5 text-xs font-medium text-white transition-colors hover:bg-[#4d6638]"
                    >
                        <MessageCircle className="h-4 w-4" />
                        <span>
                            {useSnap || isQris ? "Tanya via WhatsApp" : "Kirim Bukti & Konfirmasi ke WhatsApp"}
                        </span>
                    </a>

                    <button
                        type="button"
                        onClick={onClose}
                        className="w-full rounded-md border border-[#DDD6CC] bg-white py-2.5 text-xs font-medium text-[#2B2623] hover:bg-[#F5F2EB] transition-colors"
                    >
                        {closeLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}