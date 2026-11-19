import { useEffect, useRef, useState } from "react";
import type { LucideIcon } from "lucide-react";
import { inputClass } from "../../lib/styles";

type TimePickerProps = {
    id: string;
    value: string; //
    onChange: (value: string) => void;
    icon: LucideIcon;
};

const OPEN_HOUR = 8;
const CLOSE_HOUR = 22;

const pad = (n: number) => String(n).padStart(2, "0");

const HOURS = Array.from({ length: CLOSE_HOUR - OPEN_HOUR + 1 }, (_, i) => pad(OPEN_HOUR + i));
const MINUTES = Array.from({ length: 60 }, (_, i) => pad(i));

export default function TimePicker({ id, value, onChange, icon: Icon }: TimePickerProps) {
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const hourColRef = useRef<HTMLDivElement>(null);
    const minuteColRef = useRef<HTMLDivElement>(null);

    const [hour, minute] = value ? value.split(":") : ["", ""];

    useEffect(() => {
        if (!open) return;

        const onPointerDown = (e: MouseEvent) => {
            if (!wrapperRef.current?.contains(e.target as Node)) setOpen(false);
        };
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === "Escape") setOpen(false);
        };

        document.addEventListener("mousedown", onPointerDown);
        document.addEventListener("keydown", onKeyDown);
        return () => {
            document.removeEventListener("mousedown", onPointerDown);
            document.removeEventListener("keydown", onKeyDown);
        };
    }, [open]);

    useEffect(() => {
        if (!open) return;
        [hourColRef.current, minuteColRef.current].forEach((col) => {
            const selected = col?.querySelector<HTMLElement>('[data-selected="true"]');
            if (col && selected) {
                col.scrollTop = selected.offsetTop - col.clientHeight / 2 + selected.clientHeight / 2;
            }
        });
    }, [open]);

    const pickHour = (h: string) => onChange(`${h}:${minute || "00"}`);
    const pickMinute = (m: string) => { onChange(`${hour || HOURS[0]}:${m}`); setOpen(false); };

    const optionClass = (selected: boolean) =>
        `flex h-9 w-full items-center justify-center rounded-md text-sm transition-colors ${selected
            ? "bg-[#7A5238] font-semibold text-white"
            : "text-[#2B2623] hover:bg-[#EAE4DC]/70"
        }`;

    return (
        <div ref={wrapperRef} className="relative">
            <button
                id={id}
                type="button"
                onClick={() => setOpen((o) => !o)}
                aria-haspopup="dialog"
                aria-expanded={open}
                className={`${inputClass} flex items-center justify-between text-left ${open ? "border-[#7A5238]" : ""
                    }`}
            >
                <span className={value ? "text-[#2B2623]" : "text-[#7A726B]"}>
                    {value ? `${value} WIB` : "Pilih jam"}
                </span>
                <Icon
                    className={`h-3.5 w-3.5 transition-colors ${open ? "text-[#7A5238]" : "text-[#7A726B]"}`}
                    strokeWidth={1.5}
                />
            </button>

            {open && (
                <div
                    role="dialog"
                    aria-label="Pilih jam"
                    className="absolute left-0 right-0 top-full z-50 mt-2 min-w-[240px] origin-top animate-[fadeIn_150ms_ease-out] rounded-lg border border-[#EAE4DC] bg-[#FDFCFA] p-3 shadow-[0_8px_24px_rgba(43,38,35,0.12)]"
                >

                    <div className="my-3 border-t border-[#EAE4DC]" />

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <p className="mb-1.5 text-center text-[10px] font-semibold uppercase tracking-widest text-[#7A726B]">
                                Jam
                            </p>
                            <div
                                ref={hourColRef}
                                className="relative h-44 space-y-1 overflow-y-auto rounded-md bg-[#FAF8F5] p-1 [scrollbar-width:thin]"
                            >
                                {HOURS.map((h) => (
                                    <button
                                        key={h}
                                        type="button"
                                        data-selected={h === hour}
                                        aria-pressed={h === hour}
                                        onClick={() => pickHour(h)}
                                        className={optionClass(h === hour)}
                                    >
                                        {h}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <p className="mb-1.5 text-center text-[10px] font-semibold uppercase tracking-widest text-[#7A726B]">
                                Menit
                            </p>
                            <div
                                ref={minuteColRef}
                                className="relative h-44 space-y-1 overflow-y-auto rounded-md bg-[#FAF8F5] p-1 [scrollbar-width:thin]"
                            >
                                {MINUTES.map((m) => (
                                    <button
                                        key={m}
                                        type="button"
                                        data-selected={m === minute}
                                        aria-pressed={m === minute}
                                        onClick={() => pickMinute(m)}
                                        className={optionClass(m === minute)}
                                    >
                                        {m}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}