<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesExport implements WithMultipleSheets
{
    public function __construct(private array $report) {}

    public function sheets(): array
    {
        $r = $this->report;
        $s = $r['summary'];
        $ch = $r['channels'] ?? [];

        return [
            new ArraySheet('Ringkasan', ['Keterangan', 'Nilai'], [
                ['Periode', $r['period']['from'] . ' s/d ' . $r['period']['to']],
                ['Saluran yang Dipilih', ucfirst($r['channel'] ?? 'all')],
                ['Jumlah transaksi (Semua Saluran)', $s['transactions']],
                ['Omzet bersih (Rp)', $s['net_sales']],
                ['Diskon (Rp)', $s['discount']],
                ['Pajak (Rp)', $s['tax']],
                ['Total diterima termasuk pajak (Rp)', $s['gross_total']],
                ['Rata-rata per transaksi (Rp)', $s['average']],
                ['Pengeluaran bahan (Rp)', $s['purchases']],
                ['Laba kotor (Rp)', $s['gross_profit']],
            ]),
            new ArraySheet('Per Saluran', ['Saluran Penjualan', 'Transaksi', 'Omzet Bersih (Rp)', 'Total Kotor (Rp)', 'Kontribusi (%)'], [
                ['Kasir POS (Langsung)', $ch['pos']['transactions'] ?? 0, $ch['pos']['net_sales'] ?? 0, $ch['pos']['gross_total'] ?? 0, ($ch['pos']['percentage'] ?? ($ch['pos']['revenue_percent'] ?? 0)) . '%'],
                ['Reservasi Meja Online', $ch['reservation']['transactions'] ?? 0, $ch['reservation']['net_sales'] ?? 0, $ch['reservation']['gross_total'] ?? 0, ($ch['reservation']['percentage'] ?? ($ch['reservation']['revenue_percent'] ?? 0)) . '%'],
                ['Pesan Antar (Delivery)', $ch['delivery']['transactions'] ?? 0, $ch['delivery']['net_sales'] ?? 0, $ch['delivery']['gross_total'] ?? 0, ($ch['delivery']['percentage'] ?? ($ch['delivery']['revenue_percent'] ?? 0)) . '%'],
            ]),
            new ArraySheet(
                'Harian',
                ['Tanggal', 'POS (Rp)', 'Reservasi (Rp)', 'Delivery (Rp)', 'Total Transaksi', 'Omzet bersih (Rp)', 'Diskon (Rp)', 'Pajak (Rp)', 'Total Kotor (Rp)'],
                array_map(fn($d) => [
                    $d['date'],
                    $d['pos_sales'] ?? 0,
                    $d['res_sales'] ?? 0,
                    $d['deliv_sales'] ?? 0,
                    $d['transactions'],
                    $d['net_sales'],
                    $d['discount'],
                    $d['tax'],
                    $d['gross_total']
                ], $r['daily'])
            ),
            new ArraySheet(
                'Per Metode Bayar',
                ['Metode', 'Transaksi', 'POS (Rp)', 'Reservasi (Rp)', 'Delivery (Rp)', 'Total (Rp)'],
                array_map(fn($m) => [
                    $m['label'],
                    $m['transactions'],
                    $m['pos_total'] ?? ($m['pos'] ?? 0),
                    $m['res_total'] ?? ($m['reservation'] ?? 0),
                    $m['deliv_total'] ?? ($m['delivery'] ?? 0),
                    $m['total']
                ], $r['by_method'])
            ),
            new ArraySheet(
                'Per Kasir & Petugas',
                ['Kasir / Petugas', 'Transaksi', 'Omzet bersih (Rp)', 'Total (Rp)'],
                array_map(fn($c) => [$c['name'], $c['transactions'], $c['net_sales'], $c['gross_total']], $r['by_cashier'])
            ),
            new ArraySheet(
                'Produk Terlaris',
                ['Menu', 'Terjual POS', 'Terjual Reservasi', 'Terjual Delivery', 'Total Terjual', 'Pendapatan (Rp)'],
                array_map(fn($p) => [
                    $p['name'],
                    $p['pos_qty'] ?? 0,
                    $p['res_qty'] ?? 0,
                    $p['deliv_qty'] ?? 0,
                    $p['qty'],
                    $p['revenue']
                ], $r['top_products'])
            ),
        ];
    }
}
