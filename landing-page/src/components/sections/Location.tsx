import { Clock, Compass, MapPin, MessageCircle, Sparkles, Wifi, Zap } from "lucide-react";
import { siteInfo } from "../../data/site";
import { container, eyebrow, serif } from "../../lib/styles";
import InfoRow from "../location/InfoRow";

const amenities = [
    { icon: Wifi, title: "Wi-Fi Cepat", desc: "Cocok untuk WFC & tugas" },
    { icon: Zap, title: "Stopkontak Lengkap", desc: "Tersedia di setiap sudut meja" },
    { icon: Sparkles, title: "Area Indoor & Outdoor", desc: "Bebas pilih suasana tenang" },
    { icon: Compass, title: "Parkir & Mushola", desc: "Fasilitas lengkap untuk kenyamanan" },
];

const mapsQuery = encodeURIComponent(siteInfo.mapsQuery);
const embedSrc = `https://www.google.com/maps?q=${mapsQuery}&output=embed`;
const mapsLink =
    siteInfo.mapsUrl || `https://www.google.com/maps/search/?api=1&query=${mapsQuery}`;

export default function Location() {
    return (
        <section id="lokasi" className="scroll-mt-20 bg-[#FAF8F5]">
            <div className={`${container} py-20`}>
                <div className="max-w-xl">
                    <p className={eyebrow}>Temukan Ruang Kami</p>
                    <h2 className={`${serif} mt-3 text-3xl text-[#2B2623] sm:text-4xl`}>
                        Lokasi &amp; Jam Buka
                    </h2>
                    <p className="mt-3 text-xs leading-relaxed text-[#7A726B]">
                        Mampir dan temukan ruang nyamanmu. Ada meja untuk obrolan hangat, atau sekadar menikmati waktu sendiri ditemani seduhan kopi segar.
                    </p>
                </div>

                <div className="mt-12 grid gap-10 lg:grid-cols-2 lg:gap-14 items-start">
                    {}
                    <div className="space-y-8">
                        <div className="rounded-xl border border-[#EAE4DC] bg-white p-6 shadow-[0_1px_3px_rgba(43,38,35,0.05)]">
                            <h3 className={`${serif} text-xl text-[#2B2623]`}>Mampir, kami menunggu.</h3>

                            <div className="mt-6 space-y-5">
                                <InfoRow icon={<MapPin className="h-4 w-4" strokeWidth={1.5} />} label="Alamat">
                                    {siteInfo.address.map((line) => (
                                        <p key={line}>{line}</p>
                                    ))}
                                </InfoRow>

                                <InfoRow icon={<Clock className="h-4 w-4" strokeWidth={1.5} />} label="Jam operasional">
                                    {siteInfo.hours.map((line) => (
                                        <p key={line}>{line}</p>
                                    ))}
                                </InfoRow>

                                <InfoRow icon={<MessageCircle className="h-4 w-4" strokeWidth={1.5} />} label="Telepon / WhatsApp">
                                    <p>{siteInfo.phone}</p>
                                </InfoRow>
                            </div>

                            <div className="mt-6 pt-5 border-t border-[#EAE4DC] flex items-center justify-between">
                                <span className="text-[11px] text-[#7A726B]">Butuh petunjuk arah?</span>
                                <a
                                    href={mapsLink}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 text-xs font-medium text-[#7A5238] hover:underline"
                                >
                                    <span>Buka di Google Maps &rarr;</span>
                                </a>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-5  ">
                            {amenities.map(({ icon: Icon, title, desc }) => (
                                <div
                                    key={title}
                                    className="rounded-lg border border-[#EAE4DC] bg-[#F1ECE6]/60 p-3.5 mt-3 transition-colors hover:bg-[#F1ECE6]"
                                >
                                    <Icon className="h-4 w-4 text-[#7A5238]" strokeWidth={1.75} />
                                    <div className="mt-2 text-xs font-semibold text-[#2B2623]">{title}</div>
                                    <div className="mt-0.5 text-[11px] text-[#7A726B]">{desc}</div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="overflow-hidden rounded-xl border border-[#EAE4DC] bg-white p-2 shadow-[0_1px_3px_rgba(43,38,35,0.05)]">
                            <iframe
                                src={embedSrc}
                                className="aspect-[4/3] max-h-150 w-full rounded-lg border-0"
                                loading="lazy"
                                allowFullScreen
                                referrerPolicy="no-referrer-when-downgrade"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}