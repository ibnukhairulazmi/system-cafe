import type { ReactNode } from "react";

type InfoRowProps = {
    icon: ReactNode;
    label: string;
    children: ReactNode;
};

export default function InfoRow({ icon, label, children }: InfoRowProps) {
    return (
        <div className="flex gap-3">
            <span className="mt-0.5 text-[#7A726B]">{icon}</span>
            <div>
                <p className="text-xs font-semibold text-[#2B2623]">{label}</p>
                <div className="mt-1 text-xs leading-relaxed text-[#7A726B]">{children}</div>
            </div>
        </div>
    );
}