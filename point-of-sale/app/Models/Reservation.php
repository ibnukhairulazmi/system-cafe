<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'phone',
        'reserved_date',
        'reserved_time',
        'party_size',
        'notes',
        'total_amount',
        'payment_method',
        'payment_status',
        'paid_amount',
        'status',
        'handled_by',
        'handled_at',
        'staff_note',
        'cafe_table_id',
        'reserved_end_time',
        'hold_expires_at',
        'gateway_order_id',
        'snap_token',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'reserved_date'   => 'date',
            'handled_at'      => 'datetime',
            'status'          => ReservationStatus::class,
            'total_amount'    => 'decimal:2',
            'paid_amount'     => 'decimal:2',
            'hold_expires_at' => 'datetime',
            'checked_in_at'   => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReservationPayments::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public static function dpPercent(): float
    {
        return (float) config('cafe.reservation.dp_percent', 50);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function getDpAmountAttribute(): float
    {
        return round((float) $this->total_amount * self::dpPercent() / 100);
    }

    public function getMinimumDpAttribute(): float
    {
        return $this->dp_amount;
    }

    public static function expireOverdue(): int
    {
        return static::query()
            ->where('status', ReservationStatus::Pending->value)
            ->where('hold_expires_at', '<', now()->subMinute())
            ->update([
                'status'          => ReservationStatus::Expired->value,
                'hold_expires_at' => null,
                'staff_note'      => 'Dibatalkan otomatis: DP tidak dibayar dalam batas waktu.',
            ]);
    }
    
    public function partyLabel(): string
    {
        return $this->party_size . ' orang';
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'qris'     => 'QRIS Instan',
            'transfer' => 'Transfer Bank (BCA)',
            'cash'     => 'Bayar di Kasir',
            default    => $this->payment_method ?: 'Di Kasir',
        };
    }

    public function paymentStatusLabel(): string
    {
        $percent = rtrim(rtrim(number_format(self::dpPercent(), 2, '.', ''), '0'), '.');

        return match ($this->payment_status) {
            'paid'           => 'Lunas',
            'dp', 'partial'  => "DP {$percent}%",
            'unpaid', null   => 'Belum Bayar',
            default          => ucfirst($this->payment_status),
        };
    }

    private function whenLabel(): string
    {
        return $this->reserved_date->translatedFormat('d F Y') . ' pukul ' . substr((string) $this->reserved_time, 0, 5);
    }

    private function waLink(string $text): string
    {
        return 'https://wa.me/' . $this->phone . '?text=' . rawurlencode($text);
    }

    public function whatsappDpUrl(): string
    {
        $cafe = Setting::get('cafe_name', 'cafe kami');
        $dp   = 'Rp ' . number_format($this->dp_amount, 0, ',', '.');

        $deadline = $this->hold_expires_at
            ? ' Batas pembayaran: ' . $this->hold_expires_at->translatedFormat('d F Y H:i') . ' WIB.'
            : '';

        $text = "Halo {$this->name}, reservasi Anda ({$this->code}) pada {$this->whenLabel()} "
              . "untuk {$this->partyLabel()} di {$cafe} menunggu pembayaran DP sebesar {$dp}. "
              . "Reservasi otomatis dikonfirmasi setelah DP masuk dan hangus bila DP tidak dibayar tepat waktu.{$deadline}";

        return $this->waLink($text);
    }

    public function whatsappUrl(): string
    {
        $cafe = Setting::get('cafe_name', 'cafe kami');
        $when = $this->whenLabel();

        $orderSummary = '';
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            $itemList     = $this->items->map(fn($item) => "- {$item->quantity}x {$item->product_name}")->join("\n");
            $total        = 'Rp ' . number_format($this->total_amount, 0, ',', '.');
            $orderSummary = "\n\nPesanan Menu:\n{$itemList}\nTotal Estimasi: {$total}\nMetode Bayar: {$this->paymentMethodLabel()}";
        }

        if ($this->status === ReservationStatus::Pending) {
            return $this->whatsappDpUrl();
        }

        $text = match ($this->status) {
            ReservationStatus::Confirmed => "Halo {$this->name}, reservasi Anda ({$this->code}) pada {$when} untuk {$this->partyLabel()} sudah kami konfirmasi.{$orderSummary}\n\nSampai jumpa di {$cafe}!",
            ReservationStatus::Expired   => "Halo {$this->name}, reservasi Anda ({$this->code}) pada {$when} hangus karena DP tidak dibayar sampai batas waktu. Silakan buat reservasi baru, kami siap membantu.",
            default                      => "Halo {$this->name}, kami dari {$cafe} terkait reservasi Anda ({$this->code}) pada {$when} untuk {$this->partyLabel()}.{$orderSummary}",
        };

        return $this->waLink($text);
    }
}