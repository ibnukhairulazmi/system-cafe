import { useRef, useState } from "react";
import { formatRupiah } from "../../lib/format";
import { serif } from "../../lib/styles";
import type { MenuItem } from "../../types/menu";

export default function MenuCard({ items }: { items: MenuItem[] }) {
    const [active, setActive] = useState(0);
    const touchStartX = useRef<number | null>(null);

    const n = items.length;
    if (n === 0) return null;

    const half = Math.floor(n / 2);
    const prev = () => setActive((a) => (a - 1 + n) % n);
    const next = () => setActive((a) => (a + 1) % n);

    const current = items[active];

    return (
        <div className="mx-auto max-w-[1280px] mt-8 select-none">
            <div
                className="relative h-[240px] overflow-hidden sm:h-[340px]"
                onTouchStart={(e) => (touchStartX.current = e.touches[0].clientX)}
                onTouchEnd={(e) => {
                    if (touchStartX.current === null) return;
                    const diff = e.changedTouches[0].clientX - touchStartX.current;
                    if (diff > 50) prev();
                    else if (diff < -50) next();
                    touchStartX.current = null;
                }}
            >
                {items.map((item, i) => {
                    const offset = ((i - active + half + n) % n) - half;
                    const abs = Math.abs(offset);
                    const isCenter = offset === 0;
                    const isVisible = abs <= 1;

                    return (
                        <button
                            key={item.name}
                            type="button"
                            onClick={() => setActive(i)}
                            aria-label={item.name}
                            tabIndex={isVisible ? 0 : -1}
                            className="absolute left-1/2 top-0 w-[50%] transition-all duration-500 ease-out sm:w-[300px]"
                            style={{
                                transform: `translateX(calc(-50% + ${offset * 105}%)) scale(${isCenter ? 1 : 0.7})`,
                                opacity: isCenter ? 1 : isVisible ? 0.95 : 0,
                                zIndex: 10 - abs,
                                pointerEvents: isVisible ? "auto" : "none",
                            }}
                        >
                            <img
                                src={item.image}
                                alt={item.name}
                                draggable={false}
                                className="aspect-square w-full rounded-sm object-cover shadow-[0_1px_3px_rgba(43,38,35,0.06)]"
                            />
                        </button>
                    );
                })}
            </div>

            <div className="relative mx-auto mt-6 flex max-w-md flex-col items-center text-center">
                {n > 1 && (
                    <>
                        <button
                            type="button"
                            onClick={prev}
                            aria-label="Sebelumnya"
                            className="absolute left-0 top-6 flex h-10 w-10 items-center justify-center rounded-full bg-[#2B2623] text-white transition hover:bg-[#7A5238]"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            onClick={next}
                            aria-label="Berikutnya"
                            className="absolute right-0 top-6 flex h-10 w-10 items-center justify-center rounded-full bg-[#2B2623] text-white transition hover:bg-[#7A5238]"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </button>
                    </>
                )}

                <div key={current.name} className="px-14 min-h-30">
                    <h4 className={`${serif} text-xl text-[#2B2623]`}>{current.name}</h4>
                    <p className="mt-2.5 text-xs text-[#7A726B]">{current.desc}</p>
                    <p className="mt-3 text-sm font-semibold text-[#7A5238]">
                        {formatRupiah(current.price)}
                        {current.available === false && (
                            <p className="mt-1 text-[11px] font-medium text-[#B4533A]">Sedang habis</p>
                        )}
                    </p>
                </div>
            </div>
        </div>
    );
}