<?php

namespace App\Services\Admin;

use App\Enums\VerificationStatus;
use App\Models\DeliveryOrder;
use App\Models\Ingredient;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    /**
     * Mengumpulkan seluruh data analitik operasional dan metrik untuk dashboard admin:
     * - Reservasi terkonfirmasi hari ini & yang akan datang
     * - Delivery order aktif
     * - Bahan baku di bawah batas minimum / habis
     * - Transaksi menunggu verifikasi / berstatus flagged
     * - Action items dan kartu ringkasan
     */
    public function getDashboardData(): array
    {
        $todayReservations  = $this->todayReservations();
        $latestReservations = $this->latestReservations();
        $activeDeliveries   = $this->activeDeliveries();
        $lowStock           = Ingredient::lowStock()->orderBy('name')->get();

        $pendingVerification = Transaction::where('verification_status', VerificationStatus::Pending->value)->count();
        $flagged             = Transaction::where('verification_status', VerificationStatus::Flagged->value)->count();

        $stats = [
            'pendingVerification' => $pendingVerification,
            'flagged'             => $flagged,
            'deliveryCount'       => $activeDeliveries->count(),
            'todayCount'          => $todayReservations->count(),
            'todayNoTable'        => $todayReservations->filter(fn($r) => ! $r->table?->name)->count(),
            'latestCount'         => $latestReservations->count(),
            'lowStockCount'       => $lowStock->count(),
            'outOfStockCount'     => $lowStock->filter(fn($i) => (float) $i->stock <= 0)->count(),
        ];
        $stats['almostOutCount'] = $stats['lowStockCount'] - $stats['outOfStockCount'];

        return [
            'cards'              => $this->buildCards($stats, $todayReservations->first()),
            'actions'            => $this->buildActions($stats),
            'todayReservations'  => $todayReservations,
            'latestReservations' => $latestReservations,
            'activeDeliveries'   => $activeDeliveries,
            'lowStock'           => $lowStock,
            'todayCount'         => $stats['todayCount'],
            'latestCount'        => $stats['latestCount'],
            'deliveryCount'      => $stats['deliveryCount'],
            'lowStockCount'      => $stats['lowStockCount'],
        ];
    }

    public function todayReservations(): Collection
    {
        $nowTime = now()->format('H:i');

        return Reservation::query()
            ->where('status', 'Confirmed')
            ->with(['table', 'items'])
            ->whereDate('reserved_date', today())
            ->orderBy('reserved_time')
            ->get()
            ->filter(fn($r) => substr($r->reserved_time, 0, 5) >= $nowTime)
            ->values()
            ->each(function ($r) {
                $r->menu_text = $this->formatMenuText($r->items);
            });
    }

    public function latestReservations(): Collection
    {
        return Reservation::query()
            ->where('status', 'Confirmed')
            ->where(function ($q) {
                $q->whereDate('reserved_date', '>', today())
                    ->orWhere(function ($sub) {
                        $sub->whereDate('reserved_date', today())
                            ->whereTime('reserved_time', '>=', now()->format('H:i:s'));
                    });
            })
            ->latest('created_at')
            ->get();
    }

    public function activeDeliveries(): Collection
    {
        return DeliveryOrder::query()
            ->whereNotIn('status', ['Delivered', 'Cancelled'])
            ->when(
                method_exists(DeliveryOrder::class, 'items'),
                fn($q) => $q->with('items')
            )
            ->oldest('created_at')
            ->get()
            ->each(function ($d) {
                $status = $d->status;
                $d->display_code    = $d->code ?? '#' . $d->getKey();
                $d->display_name    = $d->name ?? ($d->customer_name ?? '-');
                $d->display_address = $d->address ?? ($d->delivery_address ?? '-');
                $d->status_text     = is_object($status)
                    ? (method_exists($status, 'label') ? $status->label() : ($status->value ?? ''))
                    : (string) $status;
                $d->menu_text       = $this->formatMenuText(collect($d->items ?? []));
            });
    }

    public function formatMenuText(iterable $items): string
    {
        return collect($items)
            ->map(function ($item) {
                $name = $item->product_name ?? $item->menu?->product_name ?? $item->menuItem?->product_name;
                $qty  = $item->qty ?? $item->quantity ?? null;

                if (! $name) {
                    return null;
                }

                return $qty ? "{$name} x{$qty}" : $name;
            })
            ->filter()
            ->implode(', ');
    }

    private function buildCards(array $stats, ?Reservation $nextReservation): array
    {
        [
            'flagged'         => $flagged,
            'deliveryCount'   => $deliveryCount,
            'todayCount'      => $todayCount,
            'todayNoTable'    => $todayNoTable,
            'lowStockCount'   => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
        ] = $stats;

        return [
            [
                'label'       => 'Transaksi ditandai',
                'url'         => route('admin.verifications.index', ['verification_status' => 'flagged']),
                'value'       => $flagged,
                'value_class' => $flagged > 0 ? 'text-rose-600' : 'text-stone-900',
                'ok'          => $flagged === 0,
                'badge'       => $flagged > 0 ? 'Perlu diperiksa' : 'Aman',
                'badge_class' => $flagged > 0
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-stone-200 text-stone-600',
                'title'       => $flagged > 0 ? 'Segera periksa transaksi' : 'Tidak ada yang ditandai',
                'desc'        => 'Transaksi dengan status ditandai',
            ],
            [
                'label'       => 'Reservasi hari ini',
                'url'         => route('admin.reservations.index'),
                'value'       => $todayCount,
                'value_class' => 'text-stone-900',
                'ok'          => $todayNoTable === 0,
                'badge'       => $todayCount === 0
                    ? 'Kosong'
                    : ($todayNoTable > 0 ? "{$todayNoTable} tanpa meja" : 'Meja lengkap'),
                'badge_class' => $todayNoTable > 0
                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                    : 'border-stone-200 text-stone-600',
                'title'       => $nextReservation
                    ? 'Berikutnya ' . substr($nextReservation->reserved_time, 0, 5) . ' WIB'
                    : 'Tidak ada reservasi tersisa',
                'desc'        => $nextReservation
                    ? $nextReservation->name . ' · ' . $nextReservation->partyLabel()
                    : 'Tidak ada reservasi yang akan datang hari ini',
            ],
            [
                'label'       => 'Delivery Order',
                'url'         => route('admin.delivery-orders.index'),
                'value'       => $deliveryCount,
                'value_class' => 'text-stone-900',
                'ok'          => $deliveryCount === 0,
                'badge'       => $deliveryCount > 0 ? 'Perlu dikirim' : 'Aman',
                'badge_class' => $deliveryCount > 0
                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                    : 'border-stone-200 text-stone-600',
                'title'       => $deliveryCount > 0 ? 'Perlu segera ditinjau' : 'Antrean delivery kosong',
                'desc'        => 'Order delivery menunggu pengiriman',
            ],
            [
                'label'       => 'Stok bahan menipis',
                'url'         => route('admin.purchase-orders.create'),
                'value'       => $lowStockCount,
                'value_class' => $lowStockCount > 0 ? 'text-amber-700' : 'text-stone-900',
                'ok'          => $lowStockCount === 0,
                'badge'       => $outOfStockCount > 0
                    ? "{$outOfStockCount} habis"
                    : ($lowStockCount > 0 ? 'Menipis' : 'Aman'),
                'badge_class' => $outOfStockCount > 0
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : ($lowStockCount > 0
                        ? 'border-amber-200 bg-amber-50 text-amber-800'
                        : 'border-stone-200 text-stone-600'),
                'title'       => $lowStockCount > 0 ? 'Perlu segera dipesan' : 'Semua stok aman',
                'desc'        => $lowStockCount > 0
                    ? 'Bahan di bawah batas minimum'
                    : 'Tidak ada bahan di bawah batas minimum',
            ],
        ];
    }

    private function buildActions(array $stats): Collection
    {
        return collect([
            [
                'label' => 'Transaksi ditandai perlu diperiksa',
                'count' => $stats['flagged'],
                'url'   => route('admin.verifications.index', ['verification_status' => 'flagged']),
                'cta'   => 'Periksa',
                'dot'   => 'bg-rose-500',
                'text'  => 'text-rose-600',
            ],
            [
                'label' => 'Bahan sudah habis',
                'count' => $stats['outOfStockCount'],
                'url'   => route('admin.purchase-orders.create'),
                'cta'   => 'Pesan bahan',
                'dot'   => 'bg-rose-500',
                'text'  => 'text-rose-600',
            ],
            [
                'label' => 'Transaksi menunggu verifikasi',
                'count' => $stats['pendingVerification'],
                'url'   => route('admin.verifications.index'),
                'cta'   => 'Verifikasi',
                'dot'   => 'bg-amber-500',
                'text'  => 'text-amber-700',
            ],
            [
                'label' => 'Reservasi hari ini belum memilih meja',
                'count' => $stats['todayNoTable'],
                'url'   => route('admin.reservations.index'),
                'cta'   => 'Atur meja',
                'dot'   => 'bg-amber-500',
                'text'  => 'text-amber-700',
            ],
            [
                'label' => 'Bahan menipis (belum habis)',
                'count' => $stats['almostOutCount'],
                'url'   => route('admin.purchase-orders.create'),
                'cta'   => 'Pesan bahan',
                'dot'   => 'bg-amber-500',
                'text'  => 'text-amber-700',
            ],
        ])->filter(fn($a) => $a['count'] > 0)->values();
    }
}
