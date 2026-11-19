import { navLinks, siteInfo } from "../../data/site";
import { container } from "../../lib/styles";
import Logo from "../ui/Logo";
import { FacebookIcon, InstagramIcon, TikTokIcon } from "../ui/SocialIcons";

const socials = [
    { label: "Instagram", href: "https://instagram.com", Icon: InstagramIcon },
    { label: "Facebook", href: "https://facebook.com", Icon: FacebookIcon },
    { label: "TikTok", href: "https://tiktok.com", Icon: TikTokIcon },
];

function SocialLinks({ className = "" }: { className?: string }) {
    return (
        <div className={`flex gap-3 text-[#7A726B] ${className}`}>
            {socials.map(({ label, href, Icon }) => (
                <a
                    key={label}
                    href={href}
                    aria-label={label}
                    className="transition-colors hover:text-[#7A5238]"
                >
                    <Icon />
                </a>
            ))}
        </div>
    );
}

export default function Footer() {
    return (
        <footer className="bg-[#FAF8F5]">
            <div className={`${container} py-10`}>
                <div className="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                    <div>
                        <Logo />
                        <p className="mt-3 text-[11px] text-[#7A726B]">{siteInfo.tagline}</p>
                    </div>

                    {}
                    <div className="flex items-center justify-between gap-4 md:block">
                        <ul className="flex gap-4 md:gap-6">
                            {navLinks.map((link) => (
                                <li key={link.label}>
                                    <a
                                        href={link.href}
                                        className="text-[11px] text-[#7A726B] transition-colors hover:text-[#7A5238]"
                                    >
                                        {link.label}
                                    </a>
                                </li>
                            ))}
                        </ul>

                        <SocialLinks className="md:hidden" />
                    </div>
                </div>

                {}
                <SocialLinks className="hidden justify-end md:flex" />

                <div className="mt-8 flex items-center justify-between border-t border-[#EAE4DC] pt-5">
                    <p className="text-[10px] text-[#A39B93]">
                        © {new Date().getFullYear()} Ruang Teduh. Semua hak dilindungi.
                    </p>
                </div>
            </div>
        </footer>
    );
}