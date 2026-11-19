import { useState, type ChangeEvent, type FormEvent, type RefObject } from "react";
import { createPortal } from "react-dom";
import {
    CalendarDays,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CreditCard,
    Minus,
    Plus,
    QrCode,
    ShieldCheck,
    Trash2,
    UtensilsCrossed,
} from "lucide-react";

import { inputClass, serif } from "../../lib/styles";
import { formatRupiah } from "../../lib/format";
import FormField from "./FormField";
import PickerInput from "./PickerInput";
import StatusAlert, { type Status } from "./StatusAlert";
import { api, ApiError } from "../../lib/api";
import type { OrderItem } from "./MenuPickerModal";
import type { MenuItem } from "../../types/menu";
import type { PaymentDetails, PaymentMethodType } from "./PaymentModal";
import ReservationSlotPicker, { type SlotSelection } from "./ReservationSlotPicker";

const emptyForm = {
    nama: "",
    wa: "",
    tanggal: "",
    jumlah: "",
    jam: "",
    catatan: "",
};

const toISODate = (d: Date) => d.toLocaleDateString("en-CA");
const DP_PERCENT = 50;
const MIN_RESERVATION_AMOUNT = 200000;

type ReservationFormProps = {
    isActive: boolean;
    firstInputRef: RefObject<HTMLInputElement | null>;
    selectedItems: OrderItem[];
    onOpenPicker: () => void;
    onUpdateQuantity: (item: MenuItem | OrderItem, delta: number) => void;
    onRemoveItem: (name: string) => void;
    onPaymentSuccess: (details: PaymentDetails) => void;
};

export default function ReservationForm({
    isActive,
    firstInputRef,
    selectedItems,
    onOpenPicker,
    onUpdateQuantity,
    onRemoveItem,
    onPaymentSuccess,
}: ReservationFormProps) {
    const [step, setStep] = useState<1 | 2 | 3>(1);
    const [submitting, setSubmitting] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethodType>("qris");
    const [status, setStatus] = useState<Status>(null);
    const [selection, setSelection] = useState<SlotSelection>(null);

    const [dateRange] = useState(() => {
        const today = new Date();
        const max = new Date();
        max.setDate(max.getDate() + 30);
        return { min: toISODate(today), max: toISODate(max) };
    });

    const partySize = Number(form.jumlah) || 0;

    const update =
        (key: keyof typeof emptyForm) =>
            (e: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
                setForm((prev) => ({ ...prev, [key]: e.target.value }));

    const updateAndResetSlot =
        (key: "tanggal" | "jumlah") =>
            (e: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
                setForm((prev) => ({ ...prev, [key]: e.target.value, jam: "" }));
                setSelection(null);
            };

    const totalMenuAmount = selectedItems.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
    );

    const dpAmount = Math.round((totalMenuAmount * DP_PERCENT) / 100);

    const goToStep2 = () => {
        setStatus(null);
        const digits = form.wa.replace(/\D/g, "");
        if (!form.nama.trim()) {
            return setStatus({ 
                type: "error", 
                message: "Nama lengkap pemesan wajib diisi." });
        }
        if (digits.length < 11 || digits.length > 13) {
            return setStatus({
                type: "error",
                message: "Nomor WhatsApp belum valid (11–13 digit).",
            });
        }
        if (!form.tanggal || !partySize) {
            return setStatus({
                type: "error",
                message: "Tanggal kunjungan dan jumlah tamu wajib dipilih.",
            });
        }
        if (!form.jam) {
            return setStatus({
                type: "error",
                message: "Pilih jam kedatangan yang tersedia terlebih dahulu.",
            });
        }
        setStep(2);
    };

    const goToStep3 = () => {
        setStatus(null);
        if (!selection || !selection.tableId || selection.time !== form.jam) {
            return setStatus({
                type: "error",
                message: "Pilih meja yang kamu inginkan terlebih dahulu.",
            });
        }
        if (selectedItems.length === 0) {
            return setStatus({
                type: "error",
                message: "Pilih minimal 1 menu makanan atau minuman untuk reservasi meja.",
            });
        }
        if (totalMenuAmount < MIN_RESERVATION_AMOUNT) {
            return setStatus({
                type: "error",
                message: `Minimum pesanan ${formatRupiah(MIN_RESERVATION_AMOUNT)} untuk reservasi meja (saat ini ${formatRupiah(totalMenuAmount)}).`,
            });
        }
        setStep(3);
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setStatus(null);

        const digits = form.wa.replace(/\D/g, "");
        if (!selection || selection.time !== form.jam) {
            return setStatus({ type: "error", message: "Jam atau meja berubah. Kembali dan pilih meja lagi." });
        }

        setSubmitting(true);
        try {
            const res = await api.reserve({
                name: form.nama.trim(),
                phone: digits,
                date: form.tanggal,
                time: selection.time,
                party_size: partySize,
                table_id: selection.tableId,
                payment_method: paymentMethod,
                notes: form.catatan.trim() || undefined,
                items: selectedItems.map((it) => ({
                    product_id: it.product_id ?? it.id,
                    quantity: it.quantity,
                })),
            });

            const paymentInfo: PaymentDetails = {
                code: res.code,
                orderType: "table",
                customerName: form.nama.trim(),
                waNumber: digits,
                itemsCount: selectedItems.reduce((acc, it) => acc + it.quantity, 0),
                subtotal: totalMenuAmount,
                deliveryFee: 0,
                totalAmount: res.total_amount ?? totalMenuAmount,
                paymentMethod: paymentMethod,
                snapToken: res.snap_token ?? undefined,
                dpAmount: res.dp_amount ?? dpAmount,
                holdExpiresAt: res.hold_expires_at,
                date: form.tanggal,
                time: form.jam,
                tableName: selection?.tableName || res.table || undefined,
                partySize: partySize,
                notes: form.catatan.trim() || undefined,
                items: selectedItems.map((it) => ({
                    name: it.name,
                    quantity: it.quantity,
                    price: it.price,
                    subtotal: it.price * it.quantity,
                    note: it.note,
                })),
            };

            onPaymentSuccess(paymentInfo);

            setForm(emptyForm);
            setSelection(null);
            setStep(1);
        } catch (err) {
            const message =
                err instanceof ApiError
                    ? err.status === 429
                        ? "Terlalu banyak percobaan. Coba lagi beberapa menit lagi."
                        : (Object.values(err.errors ?? {})[0]?.[0] ?? err.message)
                    : "Tidak dapat terhubung ke server. Coba lagi sebentar lagi.";
            setStatus({ type: "error", message });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div
            aria-hidden={!isActive}
            className={`
                w-full h-full p-5 sm:p-7 md:p-8 flex flex-col justify-between transition-opacity duration-300
                ${isActive ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none"}
            `}
        >
            <div>
                <div className="border-b border-[#EAE4DC] pb-3">
                    <div className="flex items-center justify-between">
                        <span className="text-[10px] font-semibold tracking-wider uppercase text-[#7A5238]">
                            Reservasi Meja
                        </span>
                    </div>

                    <h3 className={`${serif} mt-1 text-xl sm:text-2xl text-[#2B2623]`}>
                        {step === 1 && "Detail Tamu & Waktu"}
                        {step === 2 && "Pilih Meja & Order Menu"}
                        {step === 3 && "Konfirmasi & Pembayaran"}
                    </h3>
                </div>

                {status && (
                    <div className="mt-3">
                        <StatusAlert status={status} />
                    </div>
                )}
                {step === 1 && (
                    <div className="mt-4 space-y-3.5">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField id="resv-nama" label="Nama Lengkap">
                                <input
                                    ref={firstInputRef}
                                    id="resv-nama"
                                    type="text"
                                    placeholder="Nama pemesan"
                                    value={form.nama}
                                    onChange={update("nama")}
                                    className={inputClass}
                                    tabIndex={isActive ? 0 : -1}
                                />
                            </FormField>

                            <FormField id="resv-wa" label="Nomor WhatsApp">
                                <input
                                    id="resv-wa"
                                    type="tel"
                                    inputMode="tel"
                                    placeholder="0812 3456 7890"
                                    value={form.wa}
                                    onChange={update("wa")}
                                    className={inputClass}
                                    tabIndex={isActive ? 0 : -1}
                                />
                            </FormField>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField id="resv-tanggal" label="Tanggal Kunjungan">
                                <PickerInput
                                    id="resv-tanggal"
                                    type="date"
                                    value={form.tanggal}
                                    onChange={updateAndResetSlot("tanggal")}
                                    icon={CalendarDays}
                                    min={dateRange.min}
                                    max={dateRange.max}
                                />
                            </FormField>

                            <FormField id="resv-jumlah" label="Jumlah Tamu">
                                <div className="relative">
                                    <select
                                        id="resv-jumlah"
                                        value={form.jumlah}
                                        onChange={updateAndResetSlot("jumlah")}
                                        className={`${inputClass} appearance-none pr-8 ${
                                            form.jumlah ? "" : "text-[#A39B93]"
                                        }`}
                                        tabIndex={isActive ? 0 : -1}
                                    >
                                        <option value="">Pilih jumlah tamu</option>
                                        {Array.from({ length: 12 }, (_, i) => (
                                            <option key={i + 1} value={i + 1}>
                                                {i + 1} orang
                                            </option>
                                        ))}
                                    </select>
                                    <ChevronDown
                                        className="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#7A726B]"
                                        strokeWidth={1.5}
                                    />
                                </div>
                            </FormField>
                        </div>

                        <div className="pt-0.5">
                            <ReservationSlotPicker
                                date={form.tanggal}
                                minDate={dateRange.min}
                                partySize={partySize}
                                view="time"
                                selectedTime={form.jam}
                                onSelectTime={(t) => {
                                    setForm((prev) => ({ ...prev, jam: t }));
                                    setSelection((prev) => (prev && prev.time === t ? prev : null));
                                }}
                                value={selection}
                                onChange={setSelection}
                            />
                        </div>
                    </div>
                )}

                {step === 2 && (
                    <div className="mt-3.5 space-y-3.5">
                        <div>
                            <ReservationSlotPicker
                                date={form.tanggal}
                                minDate={dateRange.min}
                                partySize={partySize}
                                view="table"
                                selectedTime={form.jam}
                                value={selection}
                                onChange={setSelection}
                            />
                        </div>

                        <div className="space-y-1.5 pt-1">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-semibold text-[#2B2623]">
                                    Pilih Menu Makanan &amp; Minuman
                                </label>
                                {selectedItems.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={onOpenPicker}
                                        tabIndex={isActive ? 0 : -1}
                                        className="text-[11px] font-medium text-[#7A5238] hover:underline"
                                    >
                                        + Ubah Menu
                                    </button>
                                )}
                            </div>

                            {selectedItems.length === 0 ? (
                                <div
                                    onClick={onOpenPicker}
                                    className="group flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-[#D8D0C5] bg-[#FAF8F5] p-3 text-center transition-colors hover:border-[#7A5238] hover:bg-[#F5F1EB]"
                                >
                                    <UtensilsCrossed
                                        className="h-4 w-4 text-[#7A5238] group-hover:scale-105 transition-transform"
                                        strokeWidth={1.5}
                                    />
                                    <span className="mt-1 text-xs font-medium text-[#2B2623]">
                                        Pilih Menu Makanan &amp; Kopi
                                    </span>
                                    <span className="text-[11px] text-[#7A726B]">
                                        Min. pemesanan {formatRupiah(MIN_RESERVATION_AMOUNT)} untuk reservasi meja.
                                    </span>
                                </div>
                            ) : (
                                <div className="space-y-1.5 rounded-lg border border-[#EAE4DC] bg-[#FAF8F5] p-2.5">
                                    <div className="divide-y divide-[#EAE4DC]/70 max-h-24 overflow-y-auto pr-1">
                                        {selectedItems.map((item) => (
                                            <div
                                                key={item.name}
                                                className="flex items-center justify-between py-1 first:pt-0 last:pb-0 gap-2 text-xs"
                                            >
                                                <div className="flex min-w-0 flex-1 items-center gap-2">
                                                    {item.image && (
                                                        <img
                                                            src={item.image}
                                                            alt={item.name}
                                                            className="h-8 w-8 shrink-0 rounded object-cover"
                                                        />
                                                    )}
                                                    <div className="min-w-0 flex-1 truncate">
                                                        <span className="font-medium text-[#2B2623]">{item.name}</span>
                                                        <span className="text-[11px] text-[#7A726B] ml-2">
                                                            {formatRupiah(item.price)}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-1.5">
                                                    <div className="inline-flex items-center rounded border border-[#D8D0C5] bg-white">
                                                        <button
                                                            type="button"
                                                            onClick={() => onUpdateQuantity(item, -1)}
                                                            className="p-0.5 text-[#7A726B] hover:text-[#2B2623]"
                                                            aria-label="Kurang satu"
                                                        >
                                                            <Minus className="h-2.5 w-2.5" />
                                                        </button>
                                                        <span className="w-4 text-center text-[11px] font-semibold text-[#2B2623]">
                                                            {item.quantity}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => onUpdateQuantity(item, 1)}
                                                            className="p-0.5 text-[#7A726B] hover:text-[#2B2623]"
                                                            aria-label="Tambah satu"
                                                        >
                                                            <Plus className="h-2.5 w-2.5" />
                                                        </button>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => onRemoveItem(item.name)}
                                                        className="p-0.5 text-[#A39B93] hover:text-rose-600 transition-colors"
                                                        aria-label="Hapus item"
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    <div className="flex items-center justify-between border-t border-[#EAE4DC] pt-1.5 text-xs font-semibold">
                                        <span className="text-[#7A726B]">Total Pre-Order:</span>
                                        <span className={totalMenuAmount >= MIN_RESERVATION_AMOUNT ? "text-[#7A5238]" : "text-rose-600"}>
                                            {formatRupiah(totalMenuAmount)}
                                            {totalMenuAmount < MIN_RESERVATION_AMOUNT && (
                                                <span className="text-[10px] font-normal ml-1 text-rose-500">
                                                    (Min. {formatRupiah(MIN_RESERVATION_AMOUNT)})
                                                </span>
                                            )}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>

                        <FormField id="resv-catatan" label="Catatan Pelanggan (Opsional)">
                            <input
                                id="resv-catatan"
                                type="text"
                                placeholder="Misal: butuh kursi bayi, perayaan ultah..."
                                value={form.catatan}
                                onChange={update("catatan")}
                                className={inputClass}
                                tabIndex={isActive ? 0 : -1}
                            />
                        </FormField>
                    </div>
                )}

                {step === 3 && (
                    <div className="mt-3.5 space-y-3.5">
                        <div className="rounded-lg border border-[#EAE4DC] bg-[#FAF8F5] p-3 text-xs space-y-2">
                            <div className="flex items-center justify-between border-b border-[#EAE4DC] pb-2 font-semibold">
                                <span className="text-[#2B2623]">{form.nama}</span>
                                <span className="text-[#7A726B]">+{form.wa}</span>
                            </div>

                            <div className="grid grid-cols-2 gap-2 text-[11px] text-[#7A726B]">
                                <div>
                                    <span>Jadwal:</span>
                                    <div className="font-semibold text-[#2B2623]">{form.tanggal} · {form.jam} WIB</div>
                                </div>
                                <div>
                                    <span>Meja Terpilih:</span>
                                    <div className="font-semibold text-[#2B2623]">{selection?.tableName}</div>
                                </div>
                                <div>
                                    <span>Tamu:</span>
                                    <div className="font-medium text-[#2B2623]">{partySize} Orang</div>
                                </div>
                                <div>
                                    <span>Item Menu:</span>
                                    <div className="font-medium text-[#2B2623]">
                                        {selectedItems.reduce((acc, it) => acc + it.quantity, 0)} item ({formatRupiah(totalMenuAmount)})
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-between border-t border-[#EAE4DC] pt-2 font-semibold">
                                <div>
                                    <span className="text-[#7A726B]">DP Dibayar Sekarang ({DP_PERCENT}%):</span>
                                    <span className="block text-[10px] font-normal text-[#7A726B]">
                                        Sisa {formatRupiah(totalMenuAmount - dpAmount)} dibayar saat tiba di cafe
                                    </span>
                                </div>
                                <span className="text-sm font-bold text-[#7A5238]">
                                    {formatRupiah(dpAmount)}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <label className="text-xs font-semibold text-[#2B2623]">
                                Pilih Metode Pembayaran
                            </label>
                            <div className="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod("qris")}
                                    className={`flex flex-col items-center justify-center rounded-lg border p-2.5 text-center transition-all ${
                                        paymentMethod === "qris"
                                            ? "border-[#7A5238] bg-[#FAF3EC] text-[#7A5238] shadow-xs"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:bg-[#F5F2EB]"
                                    }`}
                                >
                                    <QrCode className="h-6 w-6" />
                                    <span className="mt-1 text-[11px] font-semibold">QRIS</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod("transfer")}
                                    className={`flex flex-col items-center justify-center rounded-lg border p-2.5 text-center transition-all ${
                                        paymentMethod === "transfer"
                                            ? "border-[#7A5238] bg-[#FAF3EC] text-[#7A5238] shadow-xs"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:bg-[#F5F2EB]"
                                    }`}
                                >
                                    <CreditCard className="h-6 w-6" />
                                    <span className="mt-1 text-[11px] font-semibold">Transfer Bank</span>
                                </button>
                            </div>
                            <p className="text-[11px] text-[#7A726B] pt-0.5">
                                {paymentMethod === "qris" && "Kode QRIS akan langsung ditampilkan setelah konfirmasi."}
                                {paymentMethod === "transfer" && "Nomor rekening resmi BCA & Mandiri akan diberikan setelah konfirmasi."}
                            </p>
                        </div>
                    </div>
                )}
            </div>

            <div className="pt-4 border-t border-[#EAE4DC] mt-3">
                {step === 1 && (
                    <button
                        type="button"
                        onClick={goToStep2}
                        tabIndex={isActive ? 0 : -1}
                        className="flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#7A5238] py-2.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#654229]"
                    >
                        <span>Pilih Menu</span>
                        <ChevronRight className="h-4 w-4" />
                    </button>
                )}

                {step === 2 && (
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setStep(1)}
                            tabIndex={isActive ? 0 : -1}
                            className="flex items-center justify-center gap-1 rounded-lg border border-[#DDD6CC] bg-white px-3 py-2.5 text-xs font-semibold text-[#2B2623] hover:bg-[#F5F2EB] transition-colors"
                        >
                            <ChevronLeft className="h-4 w-4" />
                            <span>Kembali</span>
                        </button>
                        <button
                            type="button"
                            onClick={goToStep3}
                            tabIndex={isActive ? 0 : -1}
                            className="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-[#7A5238] py-2.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#654229]"
                        >
                            <span>Lanjut ke Pembayaran</span>
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>
                )}

                {step === 3 && (
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setStep(2)}
                            disabled={submitting}
                            tabIndex={isActive ? 0 : -1}
                            className="flex items-center justify-center gap-1 rounded-lg border border-[#DDD6CC] bg-white px-3 py-2.5 text-xs font-semibold text-[#2B2623] hover:bg-[#F5F2EB] transition-colors disabled:opacity-50"
                        >
                            <ChevronLeft className="h-4 w-4" />
                            <span>Kembali</span>
                        </button>
                        <button
                            type="button"
                            onClick={handleSubmit}
                            disabled={submitting}
                            tabIndex={isActive ? 0 : -1}
                            className="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-[#7A5238] py-2.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#654229] disabled:opacity-50"
                        >
                            {submitting ? (
                                <span>Memproses...</span>
                            ) : (
                                <>
                                    <ShieldCheck className="h-4 w-4" />
                                    <span>Konfirmasi &amp; Bayar {formatRupiah(dpAmount)}</span>
                                </>
                            )}
                        </button>
                    </div>
                )}
            </div>
            {status?.type === "error" &&
                            createPortal(
                                <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                    <div
                                        className="fixed inset-0 bg-[#2B2623]/40 backdrop-blur-xs"
                                        onClick={() => setStatus(null)}
                                        aria-hidden="true"
                                    />
                                    <div
                                        role="alertdialog"
                                        aria-modal="true"
                                        className="relative w-full max-w-sm rounded-xl border border-[#EAE4DC] bg-white p-5 shadow-xl"
                                    >
                                        <h4 className={`${serif} text-lg text-[#2B2623]`}>Periksa Kembali</h4>
                                        <p className="mt-1.5 text-xs text-[#7A726B]">{status.message}</p>
                                        <button
                                            type="button"
                                            onClick={() => setStatus(null)}
                                            className="mt-4 w-full rounded-lg bg-[#7A5238] py-2.5 text-xs font-semibold text-white transition-colors hover:bg-[#654229]"
                                        >
                                            Mengerti
                                        </button>
                                    </div>
                                </div>,
                                document.body
                            )}
        </div>
    );
}
