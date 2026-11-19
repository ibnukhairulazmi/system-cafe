import { Coffee } from "lucide-react";
import { serif } from "../../lib/styles";

export default function Logo() {
    return (
        <a href="#beranda" className="flex items-center gap-2">
            <Coffee className="h-4 w-4 text-[#7A5238]" strokeWidth={1.5} />
            <span className={`${serif} text-xl text-[#2B2623]`}>Ruang Teduh</span>
        </a>
    );
}