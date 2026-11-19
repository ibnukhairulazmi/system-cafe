<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'code',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'landmark',
        'delivery_tier',
        'delivery_fee',
        'subtotal_amount',
        'total_amount',
        'delivery_date',
        'delivery_time',
        'is_asap',
        'payment_method',
        'payment_status',
        'paid_amount',
        'customer_note',
        'status',
        'handled_by',
        'handled_at',
        'staff_note',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'is_asap' => 'boolean',
            'status' => DeliveryOrderStatus::class,
            'delivery_fee' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'handled_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function deliveryTierLabel(): string
    {
        return match ($this->delivery_tier) {
            'fast' => 'Fast Track (Prioritas)',
            default => 'Hemat (Saving)',
        };
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'qris' => 'QRIS Instan',
            'transfer' => 'Transfer Bank (BCA)',
            'cash' => 'Tunai / COD',
            default => ucfirst($this->payment_method ?? 'cash'),
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Lunas',
            'unpaid' => 'Belum Lunas',
            default => ucfirst($this->payment_status ?? 'unpaid'),
        };
    }

    public function whatsappUrl(): string
    {
        $cafe = Setting::get('cafe_name', 'Ruang Teduh');
        $itemList = $this->items->map(fn($item) => "- {$item->quantity}x {$item->product_name}")->join("\n");
        $total = 'Rp ' . number_format($this->total_amount, 0, ',', '.');
        $orderSummary = "\n\nRincian Pesanan:\n{$itemList}\nOngkir: Rp " . number_format($this->delivery_fee, 0, ',', '.') . "\nTotal Tagihan: {$total}\nMetode Bayar: {$this->paymentMethodLabel()} ({$this->paymentStatusLabel()})\nAlamat Kirim: {$this->delivery_address}";

        $text = match ($this->status) {
            DeliveryOrderStatus::Confirmed => "Halo {$this->customer_name}, pesanan antar Anda ({$this->code}) telah dikonfirmasi dan sedang disiapkan oleh barista kami.{$orderSummary}\n\nTerima kasih!",
            DeliveryOrderStatus::OnDelivery => "Halo {$this->customer_name}, kurir kami sedang mengantarkan pesanan ({$this->code}) ke alamat Anda. Mohon pastikan nomor telepon aktif.",
            DeliveryOrderStatus::Delivered => "Halo {$this->customer_name}, pesanan ({$this->code}) telah berhasil diantarkan. Selamat menikmati hidangan dari {$cafe}!",
            DeliveryOrderStatus::Cancelled => "Halo {$this->customer_name}, mohon maaf pesanan antar ({$this->code}) telah dibatalkan. Hubungi kami bila ada pertanyaan.",
            default => "Halo {$this->customer_name}, kami dari {$cafe} terkait pesanan antar Anda ({$this->code}).{$orderSummary}",
        };

        return 'https://wa.me/' . $this->customer_phone . '?text=' . rawurlencode($text);
    }
}

