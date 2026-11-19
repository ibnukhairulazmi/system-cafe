import { serif } from "../../lib/styles";
import type { Category } from "../../types/menu";
import MenuCard from "./MenuCard";

export default function MenuCategory({ category }: { category: Category }) {
    return (
        <div id={category.id} className="mt-12 scroll-mt-24 border-t border-[#EAE4DC] pt-6">
            <div className="flex items-baseline justify-between">
                <h3 className={`${serif} text-2xl text-[#2B2623]`}>{category.title}</h3>
            </div>
            <p className="mt-2 text-[11px] text-[#7A726B]">{category.subtitle}</p>

            <MenuCard items={category.items} />
        </div>
    );
}