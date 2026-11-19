type PhotoProps = {
    src: string;
    alt: string;
    className?: string;
};

export default function Photo({ src, alt, className = "" }: PhotoProps) {
    return (
        <div className={`overflow-hidden bg-[#E7E0D6] ${className}`}>
            <img src={src} alt={alt} loading="lazy" className="h-full w-full object-cover" />
        </div>
    );
}