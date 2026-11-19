import { container, eyebrow, serif } from "../../lib/styles";
import SlidingOrderPanel from "../location/SlidingOrderPanel";

export default function Reservation() {
    return (
        <section id="reservasi" className="scroll-mt-14 bg-[#F1ECE6] py-8 sm:py-10 lg:py-12 min-h-[calc(100vh-4rem)] flex flex-col justify-center">
            <div className={`${container}`}>
                <div className="mx-auto max-w-2xl text-center">
                    <p className={eyebrow}>Pesan &amp; Reservasi</p>
                    <h2 className={`${serif} mt-2 text-2xl text-[#2B2623] sm:text-3xl`}>
                        Sisihkan Waktu atau Nikmati di Rumah
                    </h2>
                    <p className="mt-2 text-xs leading-relaxed text-[#7A726B]">
                        Amankan meja terbaikmu untuk berkumpul dan berbincang hangat, atau pesan menu pilihanmu untuk diantar segar sampai ke depan pintu.
                    </p>
                </div>

                <div className="mt-6 sm:mt-8">
                    <SlidingOrderPanel />
                </div>
            </div>
        </section>
    );
}
