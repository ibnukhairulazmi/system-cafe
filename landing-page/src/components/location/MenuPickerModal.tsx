import { useState, useMemo } from "react";
import { Search, X, Plus, Minus, Check } from "lucide-react";
import type { Category, MenuItem } from "../../types/menu";
import { formatRupiah } from "../../lib/format";
import { serif } from "../../lib/styles";

export type OrderItem = {
    id?: number;
    product_id?: number;
    image: string;
    name: string;
    price: number;
    quantity: number;
    note?: string;
};

type MenuPickerModalProps = {
    isOpen: boolean;
    onClose: () => void;
    categories: Category[];
    selectedItems: OrderItem[];
    onUpdateQuantity: (item: MenuItem, delta: number) => void;
};

export default function MenuPickerModal({
    isOpen,
    onClose,
    categories,
    selectedItems,
    onUpdateQuantity,
}: MenuPickerModalProps) {
    const [search, setSearch] = useState("");
    const [activeCategory, setActiveCategory] = useState<string>("all");

    const quantityMap = useMemo(() => {
        const map = new Map<string, number>();
        for (const item of selectedItems) {
            map.set(item.name, item.quantity);
        }
        return map;
    }, [selectedItems]);

    const filteredItems = useMemo(() => {
        const q = search.trim().toLowerCase();
        const itemsWithCategory: Array<{ item: MenuItem; categoryName: string }> = [];

        for (const cat of categories) {
            if (activeCategory !== "all" && cat.id !== activeCategory && cat.label !== activeCategory) {
                continue;
            }
            for (const it of cat.items) {
                if (
                    !q ||
                    it.name.toLowerCase().includes(q) ||
                    (it.desc && it.desc.toLowerCase().includes(q))
                ) {
                    itemsWithCategory.push({ item: it, categoryName: cat.label });
                }
            }
        }
        return itemsWithCategory;
    }, [categories, activeCategory, search]);

    const totalCount = useMemo(
        () => selectedItems.reduce((acc, it) => acc + it.quantity, 0),
        [selectedItems]
    );

    const totalAmount = useMemo(
        () => selectedItems.reduce((acc, it) => acc + it.quantity * it.price, 0),
        [selectedItems]
    );

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
            <div
                className="fixed inset-0 bg-[#2B2623]/40 backdrop-blur-xs transition-opacity"
                onClick={onClose}
                aria-hidden="true"
            />
            <div className="relative flex max-h-[90vh] w-full max-w-2xl flex-col rounded-xl border border-[#EAE4DC] bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-[#EAE4DC] px-5 py-4">
                    <div>
                        <h3 className={`${serif} text-xl text-[#2B2623]`}>Pilih Makanan &amp; Minuman</h3>
                        <p className="mt-0.5 text-xs text-[#7A726B]">
                            Pilih minimal 1 menu yang ingin kamu nikmati saat berkunjung.
                        </p>
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

                <div className="border-b border-[#EAE4DC] bg-[#FAF8F5] px-5 py-3 space-y-2.5">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#7A726B]" />
                        <input
                            type="text"
                            placeholder="Cari menu kopi, makanan, pastry..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full rounded-md border border-[#D8D0C5] bg-white py-2 pl-9 pr-3 text-xs text-[#2B2623] placeholder-[#A39B93] focus:border-[#7A5238] focus:outline-none"
                        />
                    </div>

                    {}
                    <div className="flex flex-wrap gap-1.5 text-xs">
                        <button
                            type="button"
                            onClick={() => setActiveCategory("all")}
                            className={`rounded-full px-3 py-1 font-medium transition-colors ${
                                activeCategory === "all"
                                    ? "bg-[#7A5238] text-white"
                                    : "bg-white border border-[#EAE4DC] text-[#7A726B] hover:bg-[#F0ECE6] hover:text-[#2B2623]"
                            }`}
                        >
                            Semua
                        </button>
                        {categories.map((c) => (
                            <button
                                key={c.id || c.label}
                                type="button"
                                onClick={() => setActiveCategory(c.id || c.label)}
                                className={`rounded-full px-3 py-1 font-medium transition-colors ${
                                    activeCategory === (c.id || c.label)
                                        ? "bg-[#7A5238] text-white"
                                        : "bg-white border border-[#EAE4DC] text-[#7A726B] hover:bg-[#F0ECE6] hover:text-[#2B2623]"
                                }`}
                            >
                                {c.label}
                            </button>
                        ))}
                    </div>
                </div>

                {}
                <div className="flex-1 overflow-y-auto px-5 py-4 divide-y divide-[#EAE4DC]/60">
                    {filteredItems.length === 0 ? (
                        <div className="py-12 text-center text-xs text-[#A39B93]">
                            Tidak ada menu yang sesuai dengan pencarian.
                        </div>
                    ) : (
                        filteredItems.map(({ item }) => {
                            const qty = quantityMap.get(item.name) || 0;
                            const isUnavailable = item.available === false;

                            return (
                                <div
                                    key={item.name}
                                    className="flex items-center justify-between py-3 first:pt-0 last:pb-0 gap-4"
                                >
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-medium text-[#2B2623]">
                                                {item.name}
                                            </span>
                                            {isUnavailable && (
                                                <span className="text-[10px] text-[#A39B93] bg-[#F0ECE6] px-1.5 py-0.5 rounded">
                                                    Habis
                                                </span>
                                            )}
                                        </div>
                                        {item.desc && (
                                            <p className="line-clamp-1 text-xs text-[#7A726B] mt-0.5">
                                                {item.desc}
                                            </p>
                                        )}
                                        <div className="mt-1 text-xs font-semibold text-[#7A5238]">
                                            {formatRupiah(item.price)}
                                        </div>
                                    </div>

                                    {}
                                    <div className="shrink-0">
                                        {isUnavailable ? (
                                            <button
                                                type="button"
                                                disabled
                                                className="rounded-md border border-[#EAE4DC] px-3 py-1.5 text-xs text-[#A39B93] cursor-not-allowed"
                                            >
                                                Habis
                                            </button>
                                        ) : qty === 0 ? (
                                            <button
                                                type="button"
                                                onClick={() => onUpdateQuantity(item, 1)}
                                                className="inline-flex items-center gap-1 rounded-md border border-[#7A5238]/40 bg-white px-3 py-1.5 text-xs font-medium text-[#7A5238] hover:bg-[#7A5238] hover:text-white transition-colors"
                                            >
                                                <Plus className="h-3.5 w-3.5" />
                                                <span>Tambah</span>
                                            </button>
                                        ) : (
                                            <div className="inline-flex items-center rounded-md border border-[#D8D0C5] bg-white shadow-xs">
                                                <button
                                                    type="button"
                                                    onClick={() => onUpdateQuantity(item, -1)}
                                                    className="p-1.5 text-[#7A726B] hover:text-[#2B2623] hover:bg-[#FAF8F5] transition-colors"
                                                    aria-label="Kurang satu"
                                                >
                                                    <Minus className="h-3.5 w-3.5" />
                                                </button>
                                                <span className="w-7 text-center text-xs font-semibold text-[#2B2623]">
                                                    {qty}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => onUpdateQuantity(item, 1)}
                                                    className="p-1.5 text-[#7A726B] hover:text-[#2B2623] hover:bg-[#FAF8F5] transition-colors"
                                                    aria-label="Tambah satu"
                                                >
                                                    <Plus className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>

                {}
                <div className="flex items-center justify-between border-t border-[#EAE4DC] bg-[#FAF8F5] px-5 py-3.5">
                    <div>
                        <div className="text-xs text-[#7A726B]">
                            {totalCount > 0 ? (
                                <>
                                    <span className="font-semibold text-[#2B2623]">{totalCount} item</span>{" "}
                                    dipilih
                                </>
                            ) : (
                                "Belum ada item dipilih"
                            )}
                        </div>
                        <div className="text-sm font-semibold text-[#7A5238]">
                            {formatRupiah(totalAmount)}
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex items-center gap-1.5 rounded-md bg-[#7A5238] px-4 py-2 text-xs font-medium text-white hover:bg-[#654229] transition-colors"
                    >
                        <Check className="h-4 w-4" />
                        <span>Selesai Memilih</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
