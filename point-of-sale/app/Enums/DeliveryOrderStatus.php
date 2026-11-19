<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DeliveryOrderStatus: string
{
    use HasValues;

    case Pending = 'pending';          
    case Confirmed = 'confirmed';      
    case OnDelivery = 'on_delivery';   
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Confirmed => 'Siap Dikirim',
            self::OnDelivery => 'Sedang Dikirim',
            self::Delivered => 'Sudah Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 border-amber-200',
            self::Confirmed => 'bg-sky-50 text-sky-800 border-sky-200',
            self::OnDelivery => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            self::Delivered => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::Cancelled => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    public function needsAttention(): bool
    {
        return $this === self::Confirmed;
    }

    public static function active(): array
    {
        return [self::Confirmed, self::OnDelivery];
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    public function canBecome(self $to): bool
    {
        return match ($this) {
            self::Pending => in_array($to, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($to, [self::OnDelivery, self::Cancelled], true),
            self::OnDelivery => $to === self::Delivered,
            default => false,
        };
    }
}
