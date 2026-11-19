import { features } from "../../data/site";
import { container, eyebrow, serif } from "../../lib/styles";
import Photo from "../ui/Photo";
import aboutImg from "../../images/about.jpg";

export default function About() {
    return (
        <section id="tentang" className="scroll-mt-20 bg-[#F1ECE6]">
            <div className={`${container} grid items-center gap-12 py-16 lg:grid-cols-[1.05fr_1fr] lg:gap-16`}>
                <div className="relative">
                    <Photo src={aboutImg} alt="Interior Ruang Teduh" className="aspect-[512/460] rounded-sm" />
                    <span
                        className={`${serif} absolute bottom-4 left-4 rounded-md bg-[#FAF8F5] px-4 py-2.5 text-sm text-[#2B2623] shadow-sm`}
                    >
                        Sejak 2020
                    </span>
                </div>

                <div>
                    <p className={eyebrow}>Tentang kami</p>
                    <h2 className={`${serif} mt-4 text-3xl leading-snug text-[#2B2623] lg:text-4xl`}>
                        Bukan sekadar kopi. Sebuah ruang untuk pulang.
                    </h2>
                    <p className="mt-5 text-s leading-relaxed text-[#7A726B]">
                        Ruang Teduh berawal dari keinginan sederhana: menghadirkan tempat untuk melambat. Sejak 2020, kami menyambut percakapan kecil, ide baru, dan waktu untuk diri sendiri.
                    </p>
                    <p className="mt-4 text-s leading-relaxed text-[#7A726B]">
                        Kami meracik biji kopi pilihan dari petani lokal dan menyiapkan hidangan segar setiap hari. Karena bagi kami, rasa yang baik dimulai dari bahan yang jujur dan perhatian pada setiap detail.
                    </p>

                    <ul className="mt-8 grid grid-cols-3 gap-4 border-t border-[#DDD6CC] pt-6">
                        {features.map(({ icon: Icon, label }) => (
                            <li key={label} className="flex flex-col gap-2 items-center text-center text-[#7A726B]">
                                <Icon className="h-8 w-8 text-[#7A5238]" strokeWidth={1.5} />
                                <span className="text-[12px] font-medium text-[#2B2623]">{label}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}