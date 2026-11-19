import { container, eyebrow, serif } from "../../lib/styles";
import Photo from "../ui/Photo";
import heroImg from "../../images/hero.jpg";

export default function Hero() {
    return (
        <section id="beranda" className="scroll-mt-20">
            <div className={`${container} grid items-center gap-12 py-14 lg:grid-cols-2 lg:gap-16`}>
                <div>
                    <p className={eyebrow}>Kopi baik. Waktu yang pelan.</p>
                    <h1 className={`${serif} mt-6 text-5xl leading-[1.15] text-[#2B2623] lg:text-[56px]`}>
                        Secangkir Tenang di Setiap Hari
                    </h1>
                    <p className="mt-6 max-w-md text-sm leading-relaxed text-[#7A726B]">
                        Temukan jeda di antara riuh hari, ditemani kopi pilihan, pastry hangat, dan ruang yang terasa seperti rumah.
                    </p>

                    <div className="mt-8 flex flex-wrap gap-3">
                        <a
                            href="#reservasi"
                            className="rounded-md bg-[#7A5238] px-6 py-3 text-xs font-medium text-white transition-colors hover:bg-[#654229]"
                        >
                            Reservasi
                        </a>
                        <a
                            href="#menu"
                            className="rounded-md border border-[#CFC6BC] px-6 py-3 text-xs font-medium text-[#2B2623] transition-colors hover:bg-[#F1ECE6]"
                        >
                            Lihat Menu
                        </a>
                    </div>

                    <div className="mt-10 flex items-center gap-3">
                        <span className="h-px w-6 bg-[#7A5238]" />
                        <span className="text-[10px] text-[#7A726B]">Ruang kecil untuk cerita yang berarti.</span>
                    </div>
                </div>

                <Photo
                    src={heroImg}
                    alt="Cappuccino dan croissant di meja marmer"
                    className="aspect-[1.05/1] rounded-sm"
                />
            </div>
        </section>
    );
}