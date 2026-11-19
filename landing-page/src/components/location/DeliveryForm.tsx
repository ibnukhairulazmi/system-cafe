import { useState, type ChangeEvent, type FormEvent, type RefObject } from "react";
import { createPortal } from "react-dom";
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock,
    CreditCard,
    MapPin,
    Minus,
    Plus,
    QrCode,
    ShieldCheck,
    Trash2,
    UtensilsCrossed,
    Wallet,
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

const emptyDeliveryForm = {
    nama: "",
    wa: "",
    alamat: "",
    patokan: "",
    jadwalTipe: "now",
    tanggal: "",
    jam: "",
    catatan: "",
};

type DeliveryFormProps = {
    isActive: boolean;
    firstInputRef: RefObject<HTMLInputElement | null>;
    selectedItems: OrderItem[];
    onOpenPicker: () => void;
    onUpdateQuantity: (item: MenuItem | OrderItem, delta: number) => void;
    onRemoveItem: (name: string) => void;
    onPaymentSuccess: (details: PaymentDetails) => void;
};

export default function DeliveryForm({
    isActive,
    firstInputRef,
    selectedItems,
    onOpenPicker,
    onUpdateQuantity,
    onRemoveItem,
    onPaymentSuccess,
}: DeliveryFormProps) {
    const [step, setStep] = useState<1 | 2 | 3>(1);
    const [submitting, setSubmitting] = useState(false);
    const [form, setForm] = useState(emptyDeliveryForm);
    const [status, setStatus] = useState<Status>(null);
    const [deliveryTier, setDeliveryTier] = useState<"saving" | "fast">("saving");
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethodType>("qris");

    const update =
        (key: keyof typeof emptyDeliveryForm) =>
            (e: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
                setForm((prev) => ({ ...prev, [key]: e.target.value }));

    const totalMenuAmount = selectedItems.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
    );

    const deliveryFee = deliveryTier === "fast" ? 18000 : 8000;
    const grandTotal = totalMenuAmount + deliveryFee;
    const minDeliveryAmount = 100000;

    const goToStep2 = () => {
        setStatus(null);
        if (selectedItems.length === 0) {
            return setStatus({
                type: "error",
                message: "Pilih minimal 1 menu makanan atau minuman untuk diantar.",
            });
        }
        if (totalMenuAmount < minDeliveryAmount) {
            return setStatus({
                type: "error",
                message: `Minimum pemesanan delivery ${formatRupiah(minDeliveryAmount)} (saat ini ${formatRupiah(totalMenuAmount)}).`,
            });
        }
        setStep(2);
    };

    const goToStep3 = () => {
        setStatus(null);
        const digits = form.wa.replace(/\D/g, "");
        if (!form.nama.trim()) {
            return setStatus({ type: "error", message: "Nama penerima wajib diisi." });
        }
        if (digits.length < 11 || digits.length > 13) {
            return setStatus({
                type: "error",
                message: "Nomor WhatsApp belum valid (11–13 digit).",
            });
        }
        if (!form.alamat.trim()) {
            return setStatus({ type: "error", message: "Alamat lengkap pengiriman wajib diisi." });
        }
        if (form.jadwalTipe === "scheduled" && (!form.tanggal || !form.jam)) {
            return setStatus({
                type: "error",
                message: "Pilih tanggal dan jam pengantaran terjadwal.",
            });
        }
        setStep(3);
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setStatus(null);

        const digits = form.wa.replace(/\D/g, "");
        const now = new Date();
        const today = now.toLocaleDateString("en-CA");
        const defaultTime = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;

        const isAsap = form.jadwalTipe === "now";
        const orderDate = isAsap ? today : form.tanggal || today;
        const orderTime = isAsap ? defaultTime : form.jam || defaultTime;

        setSubmitting(true);
        try {
            const res = await api.delivery({
                nama: form.nama.trim(),
                wa: digits,
                alamat: form.alamat.trim(),
                patokan: form.patokan.trim() || undefined,
                delivery_tier: deliveryTier,
                tanggal: orderDate,
                jam: orderTime,
                jadwal_tipe: isAsap ? "asap" : "scheduled",
                payment_method: paymentMethod,
                catatan: form.catatan.trim() || undefined,
                items: selectedItems.map((it) => ({
                    product_id: it.product_id,
                    name: it.name,
                    price: it.price,
                    quantity: it.quantity,
                })),
            });

            const paymentInfo: PaymentDetails = {
                code: res.code,
                orderType: "delivery",
                deliveryTier,
                customerName: form.nama.trim(),
                waNumber: digits,
                itemsCount: selectedItems.reduce((acc, it) => acc + it.quantity, 0),
                subtotal: totalMenuAmount,
                deliveryFee,
                totalAmount: grandTotal,
                paymentMethod,
                date: form.tanggal || "Hari Ini",
                time: form.jam || "Secepatnya",
                address: form.alamat.trim(),
                landmark: form.patokan.trim() || undefined,
                notes: form.catatan.trim() || undefined,
                items: selectedItems.map((it) => ({
                    name: it.name,
                    quantity: it.quantity,
                    price: it.price,
                    subtotal: it.price * it.quantity,
                })),
            };

            onPaymentSuccess(paymentInfo);

            if (paymentMethod === "cash") {
                setStatus({
                    type: "success",
                    message: `Pesanan antar berhasil diterima (Kode: ${res.code}). Siapkan uang pas ${formatRupiah(grandTotal)} saat kurir tiba.`,
                });
            }

            setForm(emptyDeliveryForm);
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
                            Doorstep Delivery
                        </span>
                    </div>

                    <h3 className={`${serif} mt-1 text-xl sm:text-2xl text-[#2B2623]`}>
                        {step === 1 && "Pilih Menu & Layanan Kirim"}
                        {step === 2 && "Data Penerima & Alamat Antar"}
                        {step === 3 && "Konfirmasi & Pembayaran"}
                    </h3>
                </div>

                {status && status.type !== "error" && (
                    <div className="mt-3">
                        <StatusAlert status={status} />
                    </div>
                )}

                {step === 1 && (
                    <div className="mt-3.5 space-y-3.5">
                        <div className="space-y-1.5 pt-1">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-semibold text-[#2B2623]">
                                    Pilihan Menu Makanan &amp; Minuman
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
                                        Pilih Menu yang Ingin Diantar
                                    </span>
                                    <span className="text-[11px] text-[#7A726B]">
                                        Min. pemesanan delivery {formatRupiah(minDeliveryAmount)}.
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
                                        <span className="text-[#7A726B]">Subtotal Menu:</span>
                                        <span className={totalMenuAmount >= minDeliveryAmount ? "text-[#7A5238]" : "text-rose-600"}>
                                            {formatRupiah(totalMenuAmount)}
                                            {totalMenuAmount < minDeliveryAmount && (
                                                <span className="text-[10px] font-normal ml-1 text-rose-500">
                                                    (Min. {formatRupiah(minDeliveryAmount)})
                                                </span>
                                            )}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>

                        <FormField id="deliv-catatan" label="Catatan / Pesan Khusus (Opsional)">
                            <input
                                id="deliv-catatan"
                                type="text"
                                placeholder="Misal: sambal dipisah, titip di satpam..."
                                value={form.catatan}
                                onChange={update("catatan")}
                                className={inputClass}
                                tabIndex={isActive ? 0 : -1}
                            />
                        </FormField>
                    </div>
                )}

                {step === 2 && (
                    <div className="mt-4 space-y-3">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField id="deliv-nama" label="Nama Penerima">
                                <input
                                    ref={firstInputRef}
                                    id="deliv-nama"
                                    type="text"
                                    placeholder="Nama lengkap penerima"
                                    value={form.nama}
                                    onChange={update("nama")}
                                    className={inputClass}
                                    tabIndex={isActive ? 0 : -1}
                                />
                            </FormField>

                            <FormField id="deliv-wa" label="Nomor WhatsApp">
                                <input
                                    id="deliv-wa"
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

                        <FormField id="deliv-alamat" label="Alamat Pengiriman Lengkap">
                            <input
                                id="deliv-alamat"
                                type="text"
                                placeholder="Jalan, nomor rumah/kantor, RT/RW, kelurahan"
                                value={form.alamat}
                                onChange={update("alamat")}
                                className={inputClass}
                                tabIndex={isActive ? 0 : -1}
                            />
                        </FormField>

                        <FormField id="deliv-patokan" label="Patokan Lokasi (Opsional)">
                            <div className="relative">
                                <input
                                    id="deliv-patokan"
                                    type="text"
                                    placeholder="Contoh: Depan pos satpam, pagar hitam"
                                    value={form.patokan}
                                    onChange={update("patokan")}
                                    className={`${inputClass} pr-9`}
                                    tabIndex={isActive ? 0 : -1}
                                />
                                <MapPin
                                    className="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#7A726B]"
                                    strokeWidth={1.5}
                                />
                            </div>
                        </FormField>

                        <div className="space-y-1.5 pt-0.5">
                            <label className="block text-[11px] font-semibold text-[#2B2623]">
                                Waktu Pengantaran
                            </label>
                            <div className="grid grid-cols-2 gap-2 text-xs">
                                <button
                                    type="button"
                                    onClick={() => setForm((prev) => ({ ...prev, jadwalTipe: "now" }))}
                                    className={`rounded-md border py-2 text-center transition-colors ${form.jadwalTipe === "now"
                                            ? "border-[#7A5238] bg-[#FAF8F5] text-[#7A5238] font-semibold"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:border-[#7A5238]"
                                        }`}
                                >
                                    Kirim Sekarang
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setForm((prev) => ({ ...prev, jadwalTipe: "scheduled" }))}
                                    className={`rounded-md border py-2 text-center transition-colors ${form.jadwalTipe === "scheduled"
                                            ? "border-[#7A5238] bg-[#FAF8F5] text-[#7A5238] font-semibold"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:border-[#7A5238]"
                                        }`}
                                >
                                    Jadwalkan Waktu
                                </button>
                            </div>

                            {form.jadwalTipe === "scheduled" && (
                                <div className="grid grid-cols-2 gap-2 pt-1">
                                    <PickerInput
                                        id="deliv-tanggal"
                                        type="date"
                                        value={form.tanggal}
                                        onChange={update("tanggal")}
                                        icon={CalendarDays}
                                    />
                                    <PickerInput
                                        id="deliv-jam"
                                        type="time"
                                        value={form.jam}
                                        onChange={update("jam")}
                                        icon={Clock}
                                    />
                                </div>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <label className="block text-[11px] font-semibold text-[#2B2623]">
                                Kecepatan Pengantaran
                            </label>
                            <div className="grid grid-cols-2 gap-2 text-xs">
                                <button
                                    type="button"
                                    onClick={() => setDeliveryTier("saving")}
                                    className={`rounded-lg border p-2.5 text-left transition-all ${deliveryTier === "saving"
                                            ? "border-[#7A5238] bg-[#FAF8F5] ring-1 ring-[#7A5238]"
                                            : "border-[#EAE4DC] bg-white hover:border-[#DDD6CC]"
                                        }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-1.5 font-semibold text-[#2B2623]">
                                            <span>Hemat</span>
                                        </div>
                                        <span className="font-bold text-[#7A5238]">Rp 8.000</span>
                                    </div>
                                    <p className="mt-1 text-[10px] text-[#7A726B]">~ 45-60 mnt</p>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setDeliveryTier("fast")}
                                    className={`rounded-lg border p-2.5 text-left transition-all ${deliveryTier === "fast"
                                            ? "border-[#7A5238] bg-[#FAF8F5] ring-1 ring-[#7A5238]"
                                            : "border-[#EAE4DC] bg-white hover:border-[#DDD6CC]"
                                        }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-1.5 font-semibold text-[#2B2623]">
                                            <span>Fast Track</span>
                                        </div>
                                        <span className="font-bold text-[#7A5238]">Rp 18.000</span>
                                    </div>
                                    <p className="mt-1 text-[10px] text-[#7A726B]">~ 20–30 mnt</p>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {step === 3 && (
                    <div className="mt-3.5 space-y-3.5">
                        <div className="rounded-lg border border-[#EAE4DC] bg-[#FAF8F5] p-3 text-xs space-y-2">
                            <div className="flex items-center justify-between border-b border-[#EAE4DC] pb-2 font-semibold">
                                <span className="text-[#2B2623]">{form.nama}</span>
                                <span className="text-[#7A726B]">+{form.wa}</span>
                            </div>

                            <div className="space-y-1 text-[11px] text-[#7A726B]">
                                <div>
                                    <span>Alamat Antar:</span>
                                    <div className="font-semibold text-[#2B2623]">{form.alamat}</div>
                                    {form.patokan && (
                                        <div className="text-[10px] text-[#A39B93]">Patokan: {form.patokan}</div>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-2 pt-1 border-t border-[#EAE4DC]/60">
                                    <div>
                                        <span>Waktu:</span>
                                        <div className="font-medium text-[#2B2623]">
                                            {form.jadwalTipe === "now" ? "Kirim Sekarang" : `${form.tanggal} · ${form.jam} WIB`}
                                        </div>
                                    </div>
                                    <div>
                                        <span>Layanan:</span>
                                        <div className="font-medium text-[#2B2623]">
                                            {deliveryTier === "fast" ? "Fast Track (20-30 mnt)" : "Hemat (45-60 mnt)"}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-[#EAE4DC] pt-2 space-y-1 text-xs">
                                <div className="flex justify-between text-[#7A726B]">
                                    <span>Subtotal Menu ({selectedItems.reduce((acc, it) => acc + it.quantity, 0)} item):</span>
                                    <span className="text-[#2B2623]">{formatRupiah(totalMenuAmount)}</span>
                                </div>
                                <div className="flex justify-between text-[#7A726B]">
                                    <span>Ongkos Kirim ({deliveryTier === "fast" ? "Fast Track" : "Hemat"}):</span>
                                    <span className="text-[#2B2623]">{formatRupiah(deliveryFee)}</span>
                                </div>
                                <div className="flex justify-between font-bold text-[#7A5238] border-t border-[#EAE4DC]/60 pt-1 text-sm">
                                    <span>Total Tagihan:</span>
                                    <span>{formatRupiah(grandTotal)}</span>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <label className="text-xs font-semibold text-[#2B2623]">
                                Pilih Metode Pembayaran
                            </label>
                            <div className="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod("qris")}
                                    className={`flex flex-col items-center justify-center rounded-lg border p-2.5 text-center transition-all ${paymentMethod === "qris"
                                            ? "border-[#7A5238] bg-[#FAF3EC] text-[#7A5238] shadow-xs"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:bg-[#F5F2EB]"
                                        }`}
                                >
                                    <QrCode className="h-4 w-4" />
                                    <span className="mt-1 text-[11px] font-semibold">QRIS</span>
                                    <span className="text-[9px] text-[#A39B93]">Instan GPN</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod("transfer")}
                                    className={`flex flex-col items-center justify-center rounded-lg border p-2.5 text-center transition-all ${paymentMethod === "transfer"
                                            ? "border-[#7A5238] bg-[#FAF3EC] text-[#7A5238] shadow-xs"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:bg-[#F5F2EB]"
                                        }`}
                                >
                                    <CreditCard className="h-4 w-4" />
                                    <span className="mt-1 text-[11px] font-semibold">Transfer Bank</span>
                                    <span className="text-[9px] text-[#A39B93]">BCA / Mandiri</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod("cash")}
                                    className={`flex flex-col items-center justify-center rounded-lg border p-2.5 text-center transition-all ${paymentMethod === "cash"
                                            ? "border-[#7A5238] bg-[#FAF3EC] text-[#7A5238] shadow-xs"
                                            : "border-[#DDD6CC] bg-white text-[#7A726B] hover:bg-[#F5F2EB]"
                                        }`}
                                >
                                    <Wallet className="h-4 w-4" />
                                    <span className="mt-1 text-[11px] font-semibold">COD (Tunai)</span>
                                    <span className="text-[9px] text-[#A39B93]">Bayar ke Kurir</span>
                                </button>
                            </div>
                            <p className="text-[11px] text-[#7A726B] pt-0.5">
                                {paymentMethod === "qris" && "Kode QRIS akan langsung ditampilkan setelah konfirmasi."}
                                {paymentMethod === "transfer" && "Nomor rekening resmi BCA & Mandiri akan diberikan setelah konfirmasi."}
                                {paymentMethod === "cash" && "Siapkan uang pas saat kurir tiba mengantarkan pesanan ke alamatmu."}
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
                        <span>Isi Data Penerima</span>
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
                                    <span>
                                        {paymentMethod === "cash"
                                            ? `Konfirmasi Pesanan · ${formatRupiah(grandTotal)} (COD)`
                                            : `Konfirmasi & Bayar · ${formatRupiah(grandTotal)}`}
                                    </span>
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