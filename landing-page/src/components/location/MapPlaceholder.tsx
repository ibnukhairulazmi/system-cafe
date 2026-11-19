import { MapPin } from "lucide-react";

export default function MapPlaceholder() {
    return (
        <div className="relative h-56 overflow-hidden rounded-lg border border-[#DDD6CC] bg-[#EAE4DC]">

            <div className="absolute left-4 top-4 h-12 w-16 rounded bg-[#DDD6CC]" />
            <div className="absolute right-10 top-6 h-10 w-24 rounded bg-[#DDD6CC]" />
            <div className="absolute bottom-10 right-6 h-14 w-20 rounded bg-[#DDD6CC]" />

            <div className="absolute -left-4 top-24 h-4 w-[120%] -rotate-[8deg] bg-[#FAF8F5]" />
            <div className="absolute left-[28%] top-[-10%] h-[130%] w-4 rotate-[14deg] bg-[#FAF8F5]" />
            <span className="absolute left-5 top-[46%] -rotate-[8deg] text-[8px] text-[#A39B93]">
                Jl. Teduh Raya
            </span>

            <div className="absolute left-[56%] top-[28%] flex flex-col items-center">
                <span className="rounded bg-[#FAF8F5] px-2 py-1 text-[9px] font-semibold text-[#7A5238] shadow-sm">
                    Ruang Teduh
                </span>
                <MapPin className="mt-1 h-5 w-5 text-[#7A5238]" strokeWidth={1.5} />
            </div>

            <span className="absolute bottom-2 left-2 rounded bg-[#FAF8F5]/90 px-2 py-1 text-[8px] text-[#7A726B]">
                Ilustrasi lokasi contoh
            </span>
        </div>
    );
}