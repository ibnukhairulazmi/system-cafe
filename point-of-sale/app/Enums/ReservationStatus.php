<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ReservationStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Seated = 'seated';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu DP',
            self::Confirmed => 'Terkonfirmasi',
            self::Seated => 'Sedang Dilayani',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 border-amber-200',
            self::Confirmed => 'bg-sky-50 text-sky-800 border-sky-200',
            self::Seated => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            self::Completed => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::Cancelled => 'bg-rose-50 text-rose-700 border-rose-200',
            self::Expired => 'bg-stone-100 text-stone-600 border-stone-200',
        };
    }

    
    public static function active(): array
    {
        return [self::Confirmed, self::Seated];
    }

    public function canBecome(self $to): bool
    {
        return match ($this) {
            self::Pending => in_array($to, [self::Confirmed, self::Cancelled, self::Expired], true),
            self::Confirmed => in_array($to, [self::Seated, self::Cancelled], true),
            self::Seated => $to === self::Completed,
            default => false,
        };
    }
}
