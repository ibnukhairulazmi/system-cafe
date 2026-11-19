import { useState } from "react";
import { useMenu } from "../../hooks/useMenu";
import { container, eyebrow, serif } from "../../lib/styles";
import MenuCategory from "../menu/MenuCategory";
import MenuTabs from "../menu/MenuTabs";

export default function Menu() {
    const { categories, status } = useMenu();
    const [active, setActive] = useState<string | null>(null);
    const current = active ?? categories[0]?.id ?? "";

    const handleSelect = (id: string) => {
        setActive(id);
        document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
    };

    return (
        <section id="menu" className="scroll-mt-20">
            <div className={`${container} py-20`}>
                <p className={eyebrow}>Dari dapur kami</p>
                <h2 className={`${serif} mt-3 text-4xl text-[#2B2623]`}>Menu Kami</h2>
                <p className="mt-3 text-xs text-[#7A726B]">
                    Rasa yang akrab, dibuat dengan sepenuh hati. Temukan teman untuk waktu teduhmu.
                </p>

                {status === "loading" && (
                    <p className="mt-12 text-center text-sm text-[#7A726B]">Memuat menu…</p>
                )}
                {status === "error" && (
                    <p className="mt-12 text-center text-sm text-[#7A726B]">
                        Menu belum dapat dimuat. Coba muat ulang halaman beberapa saat lagi.
                    </p>
                )}
                {status === "empty" && (
                    <p className="mt-12 text-center text-sm text-[#7A726B]">Menu sedang kami perbarui.</p>
                )}

                {status === "ready" && (
                    <>
                        <MenuTabs categories={categories} active={current} onSelect={handleSelect} />
                        {categories.map((category) => (
                            <MenuCategory key={category.id} category={category} />
                        ))}
                    </>
                )}
            </div>
        </section>
    );
}