import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { api, ApiError, type ReservationSlot, type TableSlotOption } from "../../lib/api";

export type TableOption = TableSlotOption;
export type Slot = ReservationSlot;
export type SlotSelection = { time: string; tableId: number; tableName: string } | null;

type Props = {
    date: string;
    minDate: string;
    partySize: number;
    view?: "both" | "time" | "table";
    selectedTime?: string;
    onSelectTime?: (time: string) => void;
    value: SlotSelection;
    onChange: (value: SlotSelection) => void;
};

type Result = { key: string; slots: Slot[]; error: string | null };

export default function ReservationSlotPicker({
    date,
    minDate,
    partySize,
    view = "both",
    selectedTime,
    onSelectTime,
    value,
    onChange,
}: Props) {
    const pastDate = !!date && date < minDate;
    const key = date && partySize > 0 && !pastDate ? `${date}|${partySize}` : "";

    const [result, setResult] = useState<Result | null>(null);
    const [localPicked, setLocalPicked] = useState<{ key: string; time: string } | null>(null);

    useEffect(() => {
        if (!key) return;
        const ctrl = new AbortController();

        api.availability(date, partySize, ctrl.signal)
            .then((data) => {
                setResult({ key, slots: data.slots ?? [], error: null });
            })
            .catch((err: unknown) => {
                if (err instanceof DOMException && err.name === "AbortError") return;
                const message =
                    err instanceof ApiError
                        ? err.status === 422
                            ? "Tanggal atau jumlah tamu belum valid. Periksa kembali pilihanmu."
                            : (Object.values(err.errors ?? {})[0]?.[0] ?? err.message)
                        : err instanceof TypeError
                            ? "Tidak dapat terhubung ke server. Periksa koneksi atau alamat API."
                            : err instanceof Error
                                ? err.message
                                : "Terjadi kesalahan.";
                setResult({ key, slots: [], error: message });
            });

        return () => ctrl.abort();
    }, [key, date, partySize]);

    if (pastDate) {
        return (
            <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                Tanggal yang dipilih sudah lewat. Pilih tanggal hari ini atau setelahnya.
            </p>
        );
    }

    if (!key) {
        return (
            <p className="rounded-lg border border-dashed border-[#DDD6CC] p-4 text-xs text-[#7A726B]">
                Pilih tanggal dan jumlah tamu terlebih dahulu untuk melihat jam dan meja yang tersedia.
            </p>
        );
    }

    const loading = result?.key !== key;
    const slots = result?.key === key ? result.slots : [];
    const error = result?.key === key ? result.error : null;

    const currentTime = selectedTime || (localPicked?.key === key ? localPicked.time : null);
    const activeSlot = slots.find((s) => s.time === currentTime) ?? null;

    const handlePickTime = (time: string) => {
        setLocalPicked({ key, time });
        onSelectTime?.(time);
        onChange(null);
    };

    const showTime = view === "both" || view === "time";
    const showTable = view === "both" || view === "table";

    return (
        <div className="space-y-3">
            {showTime && (
                <div>
                    <div className="mb-1.5 flex items-center justify-between">
                        <span className="text-xs font-semibold text-[#2B2623]">Pilih Jam Kunjungan</span>
                        {currentTime && (
                            <span className="text-[11px] font-medium text-[#7A5238]">
                                Terpilih: {currentTime} WIB
                            </span>
                        )}
                    </div>

                    {loading && (
                        <div className="flex items-center gap-2 py-3 text-xs text-[#7A726B]">
                            <Loader2 className="h-4 w-4 animate-spin text-[#7A5238]" />
                            <span>Memeriksa ketersediaan jam...</span>
                        </div>
                    )}

                    {error && (
                        <div className="rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-700">
                            {error}
                        </div>
                    )}

                    {!loading && !error && slots.length === 0 && (
                        <p className="text-xs text-[#7A726B]">Tidak ada jam operasional pada tanggal ini.</p>
                    )}

                    {!loading && !error && slots.length > 0 && (
                        <div className="grid grid-cols-4 gap-1.5 sm:grid-cols-8 max-h-32 overflow-y-auto pr-1">
                            {slots.map((s) => {
                                const isSelected = currentTime === s.time;
                                return (
                                    <button
                                        key={s.time}
                                        type="button"
                                        disabled={!s.available}
                                        onClick={() => handlePickTime(s.time)}
                                        className={`rounded-md border py-1.5 text-xs font-medium transition-colors ${
                                            isSelected
                                                ? "border-[#7A5238] bg-[#7A5238] text-white shadow-xs"
                                                : s.available
                                                    ? "border-[#DDD6CC] bg-white text-[#2B2623] hover:bg-[#F5F2EB]"
                                                    : "cursor-not-allowed border-[#EAE4DC] bg-[#FAF8F5] text-[#B8B0A6] line-through"
                                        }`}
                                    >
                                        {s.time}
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    {!loading && !error && slots.length > 0 && !slots.some((s) => s.available) && (
                        <p className="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-900">
                            Tidak ada meja kosong untuk tanggal dan jumlah tamu ini. Coba tanggal lain atau kurangi jumlah tamu.
                        </p>
                    )}
                </div>
            )}

            {showTable && activeSlot && (
                <div>
                    <div className="mb-1.5 flex items-center justify-between">
                        <span className="text-xs font-semibold text-[#2B2623]">
                            Pilih Meja ({activeSlot.time} WIB)
                        </span>
                        {value && (
                            <span className="text-[11px] font-medium text-[#7A5238]">
                                {value.tableName}
                            </span>
                        )}
                    </div>
                    <div className="grid grid-cols-5 gap-2 sm:grid-cols-5 max-h-36 overflow-y-auto pr-1">
                        {activeSlot.tables.map((t) => {
                            const isSelected = value?.tableId === t.id;
                            return (
                                <button
                                    key={t.id}
                                    type="button"
                                    onClick={() => onChange({ time: activeSlot.time, tableId: t.id, tableName: t.name })}
                                    className={`rounded-lg border p-2.5 text-left transition-colors ${
                                        isSelected
                                            ? "border-[#7A5238] bg-[#FAF3EC] shadow-xs"
                                            : "border-[#DDD6CC] bg-white hover:bg-[#F5F2EB]"
                                    }`}
                                >
                                    <div className="text-xs font-semibold text-[#2B2623]">{t.name}</div>
                                    <div className="text-[11px] text-[#7A726B]">
                                        {t.area ? `${t.area} · ` : ""}{t.capacity} kursi
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}