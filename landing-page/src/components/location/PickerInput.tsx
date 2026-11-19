import type { ChangeEvent } from "react";
import type { LucideIcon } from "lucide-react";
import { inputClass } from "../../lib/styles";
import TimePicker from "./TimePicker";

type PickerInputProps = {
    id: string;
    type: "date" | "time";
    value: string;
    onChange: (e: ChangeEvent<HTMLInputElement>) => void;
    icon: LucideIcon;
    min?: string;
    max?: string;
};

const hideNativeIndicator =
    "[&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0";

export default function PickerInput({ id, type, value, onChange, icon: Icon }: PickerInputProps) {
    if (type === "time") {
        return (
            <TimePicker
                id={id}
                value={value}
                icon={Icon}
                onChange={(v) =>
                    onChange({ target: { id, value: v } } as unknown as ChangeEvent<HTMLInputElement>)
                }
            />
        );
    }

    return (
        <div className="relative">
            <input
                id={id}
                type={type}
                value={value}
                onChange={onChange}
                className={`${inputClass} ${hideNativeIndicator}`}
            />
            <Icon
                className="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#7A726B]"
                strokeWidth={1.5}
            />
        </div>
    );
}