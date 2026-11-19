import { CircleAlert, CircleCheck } from "lucide-react";

export type Status = { type: "success" | "error"; message: string } | null;

export default function StatusAlert({ status }: { status: NonNullable<Status> }) {
    const isSuccess = status.type === "success";

    return (
        <div
            role="status"
            className={`my-3 flex gap-3 rounded-md border p-4 ${isSuccess ? "border-[#D5DDCB] bg-[#F7FAF3]" : "border-[#E8CFC8] bg-[#FDF6F4]"
                }`}
        >
            {isSuccess ? (
                <CircleCheck className="mt-0.5 h-4 w-4 shrink-0 text-[#5E7A45]" strokeWidth={1.5} />
            ) : (
                <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-[#B5523B]" strokeWidth={1.5} />
            )}
            <div>
                <p className={`text-xs font-semibold ${isSuccess ? "text-[#2B2623]" : "text-[#B5523B]"}`}>
                    {isSuccess ? "Reservasi terkirim" : "Ada yang perlu diperbaiki"}
                </p>
                <p className="mt-1 text-[11px] text-[#7A726B]">{status.message}</p>
            </div>
        </div>
    );
}