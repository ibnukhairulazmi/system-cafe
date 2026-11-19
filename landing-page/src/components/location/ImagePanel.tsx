import { ArrowRight, ArrowLeft } from "lucide-react";
import heroImg from "../../images/hero.jpg";
import aboutImg from "../../images/about.jpg";
import { serif } from "../../lib/styles";

type ImagePanelProps = {
    mode: "reservation" | "delivery";
    onToggle: () => void;
};

export default function ImagePanel({ mode, onToggle }: ImagePanelProps) {
    const isReservation = mode === "reservation";

    return (
        <div
            className={`
                relative h-52 w-full overflow-hidden
                md:absolute md:inset-y-0 md:left-0 md:z-20 md:h-full md:w-1/2
                md:will-change-transform md:transition-transform md:duration-[700ms]
                md:ease-[cubic-bezier(0.65,0,0.35,1)]
                ${isReservation ? "md:translate-x-0" : "md:translate-x-full"}
            `}
        >
            <div className="absolute inset-0 overflow-hidden">
                <img
                    src={heroImg}
                    alt="Suasana Cafe Ruang Teduh"
                    className={`absolute inset-0 h-full w-full object-cover object-top
                        transition-opacity duration-500 ease-out
                        ${isReservation ? "opacity-100 z-10" : "opacity-0 z-0 pointer-events-none"}`}
                />
                <img
                    src={aboutImg}
                    alt="Menu dan Kopi Ruang Teduh"
                    className={`absolute inset-0 h-full w-full object-cover object-top
                        transition-opacity duration-500 ease-out
                        ${!isReservation ? "opacity-100 z-10" : "opacity-0 z-0 pointer-events-none"}`}
                />
            </div>

            <div className="absolute inset-0 z-10 bg-gradient-to-t from-[#2B2623]/85 via-[#2B2623]/65 to-[#2B2623]/30 md:via-[#2B2623]/50 md:to-transparent" />

            <div className="relative z-20 flex h-full flex-col justify-end p-6 text-white sm:p-8 md:p-10">
                <div key={mode} className="mb-10 max-w-sm space-y-4">
                    <div className="space-y-2">
                        <h4
                            className={`${serif} animate-fade-up text-2xl font-semibold leading-snug text-[#FAF8F5] md:text-3xl [text-shadow:0_1px_4px_rgba(43,38,35,0.45)]`}
                        >
                            {isReservation ? "Ingin Nikmati di Rumah?" : "Ingin Reservasi Meja?"}
                        </h4>
                        <p className="animate-fade-up text-[13px] leading-relaxed text-[#F1ECE6] [animation-delay:380ms] md:text-sm [text-shadow:0_1px_3px_rgba(43,38,35,0.4)]">
                            {isReservation
                                ? "Kopi pilihan dan hidangan segar Ruang Teduh siap diantar hangat langsung ke depan pintumu."
                                : "Sisihkan waktumu dan amankan meja untuk menikmati obrolan hangat dan suasana teduh kami."}
                        </p>
                    </div>

                    <div className="animate-fade-up [animation-delay:500ms]">
                        <button
                            type="button"
                            onClick={onToggle}
                            className="group inline-flex items-center gap-2 rounded-lg bg-white/95 px-5 py-2.5 text-[13px] font-semibold text-[#2B2623] shadow-md transition-all duration-200 hover:scale-[1.02] hover:bg-white active:scale-[0.98]"
                        >
                            {isReservation ? (
                                <>
                                    <span>Pesan Delivery</span>
                                    <ArrowRight className="h-3.5 w-3.5 text-[#7A5238] transition-transform group-hover:translate-x-1" />
                                </>
                            ) : (
                                <>
                                    <ArrowLeft className="h-3.5 w-3.5 text-[#7A5238] transition-transform group-hover:-translate-x-1" />
                                    <span>Reservasi Meja</span>
                                </>
                            )}
                        </button>
                    </div>
                </div>

                <div className="hidden md:block">
                    <span className="text-xs text-[#EAE4DC]/90 [text-shadow:0_1px_3px_rgba(43,38,35,0.4)]">
                        Ruang Teduh Coffee &amp; Eatery · Purwokerto
                    </span>
                </div>
            </div>
        </div>
    );
}