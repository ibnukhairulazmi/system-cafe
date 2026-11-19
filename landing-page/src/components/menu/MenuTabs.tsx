import type { Category } from "../../types/menu";

type MenuTabsProps = {
    categories: Category[];
    active: string;
    onSelect: (id: string) => void;
};

export default function MenuTabs({ categories, active, onSelect }: MenuTabsProps) {
    return (
        <div className="mt-8 flex flex-wrap gap-3">
            {categories.map((c) => (
                <button
                    key={c.id}
                    type="button"
                    onClick={() => onSelect(c.id)}
                    className={`rounded-md border px-5 py-2 text-[11px] transition-colors ${active === c.id
                            ? "border-[#7A5238] bg-[#7A5238] text-white"
                            : "border-[#DDD6CC] bg-transparent text-[#7A726B] hover:border-[#7A5238] hover:text-[#7A5238]"
                        }`}
                >
                    {c.label}
                </button>
            ))}
        </div>
    );
}