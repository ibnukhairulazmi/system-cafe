import type { ReactNode } from "react";
import { labelClass } from "../../lib/styles";

type FormFieldProps = {
    id: string;
    label: string;
    children: ReactNode;
};

export default function FormField({ id, label, children }: FormFieldProps) {
    return (
        <div>
            <label htmlFor={id} className={labelClass}>
                {label}
            </label>
            {children}
        </div>
    );
}