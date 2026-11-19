import { useState } from "react";
import { navLinks } from "../../data/site";
import { container } from "../../lib/styles";
import Logo from "../ui/Logo";

export default function Navbar() {
    const [active, setActive] = useState("Beranda");
    const [open, setOpen] = useState(false);

    const handleClick = (label: string) => {
        setActive(label);
        setOpen(false);
    };

    return (
        <header className="sticky top-0 z-50 border-b border-[#EAE4DC] bg-[#FAF8F5]/95 backdrop-blur">
            <div className={`${container} flex h-[70px] items-center justify-between`}>
                <Logo />
                <nav className="flex items-center gap-2 md:gap-8">
                    <ul className="hidden items-center gap-8 md:flex">
                        {navLinks.map((link) => (
                            <li key={link.label}>
                                <a
                                    href={link.href}
                                    onClick={() => setActive(link.label)}
                                    className={`text-xs transition-colors hover:text-[#7A5238] ${active === link.label ? "font-semibold text-[#7A5238]" : "text-[#7A726B]"
                                        }`}
                                >
                                    {link.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                    <a
                        href="#reservasi"
                        className="rounded-md bg-[#7A5238] px-5 py-2.5 text-xs font-medium text-white transition-colors hover:bg-[#654229]"
                    >
                        Reservasi
                    </a>

                    <button
                        type="button"
                        onClick={() => setOpen((o) => !o)}
                        aria-label={open ? "Tutup menu" : "Buka menu"}
                        aria-expanded={open}
                        aria-controls="mobile-menu"
                        className="flex h-10 w-10 items-center justify-center rounded-md text-[#2B2623] transition-colors hover:bg-[#EAE4DC]/60 md:hidden"
                    >
                        <svg
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            {open ? (
                                <path d="M18 6L6 18M6 6l12 12" />
                            ) : (
                                <path d="M4 6h16M4 12h16M4 18h16" />
                            )}
                        </svg>
                    </button>
                </nav>
            </div>

            <div
                id="mobile-menu"
                className={`grid transition-all duration-300 ease-out md:hidden ${open ? "grid-rows-[1fr] border-t border-[#EAE4DC]" : "grid-rows-[0fr]"
                    }`}
            >
                <ul className="overflow-hidden">
                    {navLinks.map((link) => (
                        <li key={link.label}>
                            <a
                                href={link.href}
                                onClick={() => handleClick(link.label)}
                                className={`block px-6 py-3.5 text-sm transition-colors hover:bg-[#EAE4DC]/50 hover:text-[#7A5238] ${active === link.label ? "font-semibold text-[#7A5238]" : "text-[#7A726B]"
                                    }`}
                            >
                                {link.label}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        </header>
    );
}