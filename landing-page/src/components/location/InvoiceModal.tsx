import { useEffect } from "react";
import { X } from "lucide-react";
import ReservationTicket from "./ReservationTicket";
import type { PaymentDetails } from "./PaymentModal";

type InvoiceModalProps = {
    isOpen: boolean;
    onClose: () => void;
    details: PaymentDetails | null;
};

export default function InvoiceModal({ isOpen, onClose, details }: InvoiceModalProps) {
    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e: KeyboardEvent) => {
            if (e.key === "Escape") onClose();
        };
        window.addEventListener("keydown", onKey);
        return () => window.removeEventListener("keydown", onKey);
    }, [isOpen, onClose]);

    if (!isOpen || !details) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
            <div
                className="fixed inset-0 bg-[#2B2623]/50 backdrop-blur-xs"
                onClick={onClose}
                aria-hidden="true"
            />

            <div className="relative flex max-h-[96vh] w-full max-w-md flex-col rounded-xl border border-[#EAE4DC] bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-[#EAE4DC] bg-[#FAF8F5] px-6 py-4">
                    <div>
                        <span className="text-xs font-semibold uppercase tracking-[0.15em] text-[#5E7A45]">
                            Pembayaran Berhasil
                        </span>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md p-1.5 text-[#7A726B] transition-colors hover:bg-[#F5F2EB] hover:text-[#2B2623]"
                        aria-label="Tutup"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-6 py-5">
                    <ReservationTicket details={details} onClose={onClose} />
                </div>
            </div>
        </div>
    );
}