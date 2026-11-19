import { useRef, useState } from "react";
import { Download } from "lucide-react";
import { toPng } from "html-to-image";
import { QRCodeCanvas } from "qrcode.react";
import { formatRupiah } from "../../lib/format";
import { serif } from "../../lib/styles";
import type { PaymentDetails } from "./PaymentModal";

type ReservationInvoiceProps = {
    details: PaymentDetails;
    onClose?: () => void;
};

export default function ReservationInvoice({ details, onClose }: ReservationInvoiceProps) {
    const invoiceRef = useRef<HTMLDivElement>(null);
    const [isSaving, setIsSaving] = useState(false);

    const isTable = details.orderType === "table";
    const dpAmount = details.dpAmount ?? Math.ceil(details.totalAmount * 0.5);
    const amountPaid = isTable ? (details.paymentMethod === "cash" ? 0 : dpAmount) : details.totalAmount;
    const remainingBill = Math.max(0, details.totalAmount - amountPaid);

    const downloadInvoice = async () => {
        const node = invoiceRef.current;
        if (!node || isSaving) return;

        setIsSaving(true);
        await new Promise<void>((resolve) =>
            requestAnimationFrame(() => requestAnimationFrame(() => resolve())),
        );

        try {
            const dataUrl = await toPng(node, {
                pixelRatio: 3,
                cacheBust: true,
                backgroundColor: "#FAF8F5",
            });
            const link = document.createElement("a");
            link.href = dataUrl;
            link.download = `Invoice-${details.code}.png`;
            link.click();
        } catch (error) {
            console.error("Gagal menyimpan invoice:", error);
            alert("Invoice gagal disimpan. Silakan coba lagi.");
        } finally {
            setIsSaving(false);
        }
    };

    const infoRows: { label: string; value: string }[] = [
        { label: "Atas Nama", value: details.customerName },
        { label: "No. WhatsApp", value: `${details.waNumber}` },
        ...(details.date ? [{ label: "Tanggal", value: details.date }] : []),
        ...(details.time ? [{ label: "Waktu", value: `${details.time} WIB` }] : []),
        ...(isTable && details.tableName ? [{ label: "Meja", value: details.tableName }] : []),
        ...(isTable && details.partySize ? [{ label: "Tamu", value: `${details.partySize} orang` }] : []),
    ];

    return (
        <div className="space-y-3">
            <div
                ref={invoiceRef}
                id="printable-reservation-ticket"
                className="overflow-hidden rounded-2xl border border-[#DDD6CC] bg-[#FAF8F5] text-[#2B2623]"
            >
                {}
                <div className="flex items-start justify-between gap-3 px-5 pt-5 pb-4">
                    <div>
                        <p className="text-[11px] uppercase tracking-widest text-[#7A726B]">Ruang Teduh Coffee</p>
                        <h3 className={`${serif} mt-0.5 text-xl font-bold`}>
                            {isTable ? "Reservasi Meja" : "Pesanan Antar"}
                        </h3>
                    </div>
                </div>

                {}
                <div className="mx-5 rounded-xl border border-[#EAE4DC] bg-white px-4 py-3 text-center">
                    <p className="text-[10px] uppercase tracking-wider text-[#7A726B]">Kode Booking</p>
                    <p className="font-mono text-2xl font-bold tracking-widest text-[#7A5238]">{details.code}</p>
                </div>

                {}
                <dl className="mx-5 mt-4 divide-y divide-[#EAE4DC] text-xs">
                    {infoRows.map((row) => (
                        <div key={row.label} className="flex justify-between gap-4 py-2">
                            <dt className="text-[#7A726B]">{row.label}</dt>
                            <dd className="text-right font-semibold">{row.value}</dd>
                        </div>
                    ))}
                </dl>

                {}
                {details.items && details.items.length > 0 && (
                    <div className="mx-5 mt-4 border-t border-dashed border-[#DDD6CC] pt-3 text-xs">
                        <p className="mb-1.5 font-semibold text-[#7A5238]">Pesanan ({details.items.length} menu)</p>
                        <ul
                            className={`space-y-1.5 pr-1 ${isSaving ? "" : "max-h-32 overflow-y-auto"}`}
                        >
                            {details.items.map((item, idx) => (
                                <li key={idx} className="flex justify-between gap-3">
                                    <span>
                                        {item.quantity}x {item.name}
                                        {item.note && (
                                            <span className="block text-[10px] italic text-[#A39B93]">
                                                "{item.note}"
                                            </span>
                                        )}
                                    </span>
                                    <span className="shrink-0 text-[#7A726B]">
                                        {formatRupiah(item.subtotal || item.price * item.quantity)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {}
                <div className="mx-5 mt-4 space-y-1 border-t border-dashed border-[#DDD6CC] pt-3 text-xs">
                    <div className="flex justify-between text-[#7A726B]">
                        <span>Total</span>
                        <span className="font-semibold text-[#2B2623]">{formatRupiah(details.totalAmount)}</span>
                    </div>
                    <div className="flex justify-between text-[#5E7A45]">
                        <span>Dibayar</span>
                        <span className="font-semibold">{formatRupiah(amountPaid)}</span>
                    </div>
                    {remainingBill > 0 && (
                        <div className="flex justify-between font-bold text-[#7A5238]">
                            <span>Sisa di kafe</span>
                            <span>{formatRupiah(remainingBill)}</span>
                        </div>
                    )}
                </div>

                {}
                <div className="mt-4 flex items-center gap-3 border-t border-[#EAE4DC] bg-white px-5 py-4">
                    <QRCodeCanvas value={`RUANG-TEDUH:${details.code}`} size={72} level="M" marginSize={1} />
                    <p className="text-[11px] leading-relaxed text-[#7A726B]">
                        Tunjukkan QR ini atau sebutkan kode{" "}
                        <span className="font-mono font-bold text-[#7A5238]">{details.code}</span> ke kasir saat tiba.
                    </p>
                </div>
            </div>

            {}
            <button
                type="button"
                onClick={downloadInvoice}
                disabled={isSaving}
                className="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#7A5238] py-2.5 text-xs font-semibold text-white transition-colors hover:bg-[#654229] disabled:opacity-60"
            >
                <Download className="h-4 w-4" />
                {isSaving ? "Menyimpan..." : "Simpan Invoice (PNG)"}
            </button>

            {onClose && (
                <button
                    type="button"
                    onClick={onClose}
                    className="w-full rounded-lg border border-[#DDD6CC] bg-white py-2 text-xs font-medium text-[#7A726B] transition-colors hover:bg-[#F5F2EB] hover:text-[#2B2623]"
                >
                    Selesai &amp; Tutup
                </button>
            )}
        </div>
    );
}