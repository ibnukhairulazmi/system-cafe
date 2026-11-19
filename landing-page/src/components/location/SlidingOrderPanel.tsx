import { useState, useRef, useEffect } from "react";
import { Armchair, Bike } from "lucide-react";
import ImagePanel from "./ImagePanel";
import ReservationForm from "./ReservationForm";
import DeliveryForm from "./DeliveryForm";
import MenuPickerModal, { type OrderItem } from "./MenuPickerModal";
import PaymentModal, { type PaymentDetails } from "./PaymentModal";
import { useMenu } from "../../hooks/useMenu";
import type { MenuItem } from "../../types/menu";
import heroImg from "../../images/hero.jpg";
import aboutImg from "../../images/about.jpg";

export default function SlidingOrderPanel() {
    const [mode, setMode] = useState<"reservation" | "delivery">("reservation");
    const [selectedItems, setSelectedItems] = useState<OrderItem[]>([]);
    const [isPickerOpen, setIsPickerOpen] = useState(false);
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);
    const [paymentDetails, setPaymentDetails] = useState<PaymentDetails | null>(null);
    const reservationFirstInputRef = useRef<HTMLInputElement>(null);
    const deliveryFirstInputRef = useRef<HTMLInputElement>(null);

    const { categories } = useMenu();

    const handleToggleMode = () => {
        setMode((prev) => (prev === "reservation" ? "delivery" : "reservation"));
    };

    useEffect(() => {
        const timer = setTimeout(() => {
            if (mode === "reservation") {
                reservationFirstInputRef.current;
            } else {
                deliveryFirstInputRef.current;
            }
        }, 620);

        return () => clearTimeout(timer);
    }, [mode]);

    const handleUpdateQuantity = (item: MenuItem | OrderItem, delta: number) => {
        setSelectedItems((prev) => {
            const existingIndex = prev.findIndex((i) => i.name === item.name);
            if (existingIndex > -1) {
                const updated = [...prev];
                const newQty = updated[existingIndex].quantity + delta;
                if (newQty <= 0) {
                    updated.splice(existingIndex, 1);
                } else {
                    updated[existingIndex] = { ...updated[existingIndex], quantity: newQty };
                }
                return updated;
            } else if (delta > 0) {
                const productId = item.product_id ?? item.id;
                return [
                    ...prev,
                    {
                        id: productId,
                        product_id: productId,
                        image: item.image,
                        name: item.name,
                        price: item.price,
                        quantity: 1,
                    },
                ];
            }
            return prev;
        });
    };

    const handleRemoveItem = (name: string) => {
        setSelectedItems((prev) => prev.filter((i) => i.name !== name));
    };

    const handlePaymentSuccess = (details: PaymentDetails) => {
        setPaymentDetails(details);
        setPaymentModalOpen(true);
    };

    return (
        <div className="mx-auto w-full">
            <div className="mb-4 grid grid-cols-2 gap-2 rounded-xl border border-[#EAE4DC] bg-[#FAF8F5] p-1.5 md:hidden">
                <button
                    type="button"
                    onClick={() => setMode("reservation")}
                    className={`flex items-center justify-center gap-2 rounded-lg py-2.5 text-xs font-semibold transition-all ${mode === "reservation"
                            ? "bg-[#7A5238] text-white shadow-xs"
                            : "text-[#7A726B] hover:text-[#2B2623]"
                        }`}
                >
                    <Armchair className="h-4 w-4" />
                    <span>Reservasi</span>
                </button>

                <button
                    type="button"
                    onClick={() => setMode("delivery")}
                    className={`flex items-center justify-center gap-2 rounded-lg py-2.5 text-xs font-semibold transition-all ${mode === "delivery"
                            ? "bg-[#7A5238] text-white shadow-xs"
                            : "text-[#7A726B] hover:text-[#2B2623]"
                        }`}
                >
                    <Bike className="h-4 w-4" />
                    <span>Delivery</span>
                </button>
            </div>

            <div className="relative overflow-hidden rounded-2xl border border-[#EAE4DC] bg-white shadow-[0_12px_36px_rgba(43,38,35,0.07)]">
                {}
                <div className="block h-44 w-full overflow-hidden md:hidden">
                    <img
                        src={mode === "reservation" ? heroImg : aboutImg}
                        alt={
                            mode === "reservation"
                                ? "Suasana Cafe Ruang Teduh"
                                : "Menu dan Kopi Ruang Teduh"
                        }
                        className="block h-full w-full object-cover object-top"
                    />
                </div>

                <div className="relative flex flex-col md:grid md:grid-cols-2 md:h-[580px] lg:h-[600px]">
                    {/* Mobile: hanya form aktif yang ikut layout, supaya tidak ada space kosong.
                        Desktop: tetap tampil seperti semula (md:flex md:h-full). */}
                    <div
                        className={`relative w-full flex-col justify-start overflow-y-auto md:flex md:h-full ${
                            mode === "delivery" ? "flex" : "hidden"
                        }`}
                    >
                        <DeliveryForm
                            isActive={mode === "delivery"}
                            firstInputRef={deliveryFirstInputRef}
                            selectedItems={selectedItems}
                            onOpenPicker={() => setIsPickerOpen(true)}
                            onUpdateQuantity={handleUpdateQuantity}
                            onRemoveItem={handleRemoveItem}
                            onPaymentSuccess={handlePaymentSuccess}
                        />
                    </div>

                    <div
                        className={`relative w-full flex-col justify-start overflow-hidden md:flex md:h-full ${
                            mode === "reservation" ? "flex" : "hidden"
                        }`}
                    >
                        <ReservationForm
                            isActive={mode === "reservation"}
                            firstInputRef={reservationFirstInputRef}
                            selectedItems={selectedItems}
                            onOpenPicker={() => setIsPickerOpen(true)}
                            onUpdateQuantity={handleUpdateQuantity}
                            onRemoveItem={handleRemoveItem}
                            onPaymentSuccess={handlePaymentSuccess}
                        />
                    </div>

                    {}
                    <div className="hidden md:contents">
                        <ImagePanel mode={mode} onToggle={handleToggleMode} />
                    </div>
                </div>
            </div>

            <MenuPickerModal
                isOpen={isPickerOpen}
                onClose={() => setIsPickerOpen(false)}
                categories={categories}
                selectedItems={selectedItems}
                onUpdateQuantity={handleUpdateQuantity}
            />

            <PaymentModal
                isOpen={paymentModalOpen}
                onClose={() => setPaymentModalOpen(false)}
                details={paymentDetails}
            />
        </div>
    );
}