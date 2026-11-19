<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    @include('pdf._style')
</head>

<body>
    @php
        $s = $report['summary'];
        $ch = $report['channels'] ?? [];
    @endphp
    @include('pdf._header', [
        'title' => 'Laporan Penjualan & Pendapatan',
        'subtitle' =>
            'Periode ' .
            \Carbon\Carbon::parse($report['period']['from'])->translatedFormat('d M Y') .
            ' s/d ' .
            \Carbon\Carbon::parse($report['period']['to'])->translatedFormat('d M Y') .
            ($report['channel'] !== 'all' ? ' | Saluran: ' . ucfirst($report['channel']) : ' | Semua Saluran (POS, Reservasi, Delivery)'),
    ])

    <h2>Ringkasan Akuntansi</h2>
    <table>
        <tr>
            <td>Total transaksi (semua saluran)</td>
            <td class="r">{{ $s['transactions'] }}</td>
        </tr>
        <tr>
            <td>Omzet bersih (sebelum pajak)</td>
            <td class="r">@rupiah($s['net_sales'])</td>
        </tr>
        <tr>
            <td>Diskon diberikan</td>
            <td class="r">@rupiah($s['discount'])</td>
        </tr>
        <tr>
            <td>Pajak dipungut</td>
            <td class="r">@rupiah($s['tax'])</td>
        </tr>
        <tr>
            <td>Total kotor diterima (termasuk pajak)</td>
            <td class="r">@rupiah($s['gross_total'])</td>
        </tr>
        <tr>
            <td>Rata-rata per transaksi</td>
            <td class="r">@rupiah($s['average'])</td>
        </tr>
        <tr>
            <td>Pengeluaran bahan (PO diterima)</td>
            <td class="r">@rupiah($s['purchases'])</td>
        </tr>
        <tr class="total">
            <td>Laba kotor</td>
            <td class="r {{ $s['gross_profit'] < 0 ? 'red' : 'green' }}">@rupiah($s['gross_profit'])</td>
        </tr>
    </table>

    <h2>Performa Saluran Penjualan</h2>
    <table>
        <tr>
            <th>Saluran</th>
            <th class="r">Transaksi</th>
            <th class="r">Kontribusi</th>
            <th class="r">Omzet Bersih</th>
            <th class="r">Total Diterima</th>
        </tr>
        @if (!empty($ch))
            <tr>
                <td>Kasir POS (Langsung)</td>
                <td class="r">{{ $ch['pos']['transactions'] ?? 0 }}</td>
                <td class="r">{{ $ch['pos']['percentage'] ?? ($ch['pos']['revenue_percent'] ?? 0) }}%</td>
                <td class="r">@rupiah($ch['pos']['net_sales'] ?? 0)</td>
                <td class="r">@rupiah($ch['pos']['gross_total'] ?? 0)</td>
            </tr>
            <tr>
                <td>Reservasi Meja Online</td>
                <td class="r">{{ $ch['reservation']['transactions'] ?? 0 }}</td>
                <td class="r">{{ $ch['reservation']['percentage'] ?? ($ch['reservation']['revenue_percent'] ?? 0) }}%</td>
                <td class="r">@rupiah($ch['reservation']['net_sales'] ?? 0)</td>
                <td class="r">@rupiah($ch['reservation']['gross_total'] ?? 0)</td>
            </tr>
            <tr>
                <td>Pesan Antar (Delivery)</td>
                <td class="r">{{ $ch['delivery']['transactions'] ?? 0 }}</td>
                <td class="r">{{ $ch['delivery']['percentage'] ?? ($ch['delivery']['revenue_percent'] ?? 0) }}%</td>
                <td class="r">@rupiah($ch['delivery']['net_sales'] ?? 0)</td>
                <td class="r">@rupiah($ch['delivery']['gross_total'] ?? 0)</td>
            </tr>
        @endif
    </table>

    <h2>Per Metode Pembayaran</h2>
    <table>
        <tr>
            <th>Metode</th>
            <th class="r">Transaksi</th>
            <th class="r">POS</th>
            <th class="r">Reservasi</th>
            <th class="r">Delivery</th>
            <th class="r">Total</th>
        </tr>
        @foreach ($report['by_method'] as $m)
            <tr>
                <td>{{ $m['label'] }}</td>
                <td class="r">{{ $m['transactions'] }}</td>
                <td class="r">@rupiah($m['pos_total'] ?? ($m['pos'] ?? 0))</td>
                <td class="r">@rupiah($m['res_total'] ?? ($m['reservation'] ?? 0))</td>
                <td class="r">@rupiah($m['deliv_total'] ?? ($m['delivery'] ?? 0))</td>
                <td class="r">@rupiah($m['total'])</td>
            </tr>
        @endforeach
    </table>

    <h2>Menu Terlaris (Semua Saluran)</h2>
    <table>
        <tr>
            <th>Menu</th>
            <th class="r">POS</th>
            <th class="r">Reservasi</th>
            <th class="r">Delivery</th>
            <th class="r">Total Terjual</th>
            <th class="r">Pendapatan</th>
        </tr>
        @forelse ($report['top_products'] as $p)
            <tr>
                <td>{{ $p['name'] }}</td>
                <td class="r">{{ $p['pos_qty'] ?? 0 }}</td>
                <td class="r">{{ $p['res_qty'] ?? 0 }}</td>
                <td class="r">{{ $p['deliv_qty'] ?? 0 }}</td>
                <td class="r">{{ $p['qty'] }}</td>
                <td class="r">@rupiah($p['revenue'])</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted">Tidak ada data penjualan menu.</td>
            </tr>
        @endforelse
    </table>

    <h2>Rincian Harian</h2>
    <table>
        <tr>
            <th>Tanggal</th>
            <th class="r">POS</th>
            <th class="r">Reservasi</th>
            <th class="r">Delivery</th>
            <th class="r">Total Trx</th>
            <th class="r">Diskon</th>
            <th class="r">Pajak</th>
            <th class="r">Total</th>
        </tr>
        @forelse ($report['daily'] as $d)
            <tr>
                <td>{{ \Carbon\Carbon::parse($d['date'])->translatedFormat('d M Y') }}</td>
                <td class="r">@rupiah($d['pos_sales'] ?? 0)</td>
                <td class="r">@rupiah($d['res_sales'] ?? 0)</td>
                <td class="r">@rupiah($d['deliv_sales'] ?? 0)</td>
                <td class="r">{{ $d['transactions'] }}</td>
                <td class="r">@rupiah($d['discount'])</td>
                <td class="r">@rupiah($d['tax'])</td>
                <td class="r">@rupiah($d['gross_total'])</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="muted">Tidak ada penjualan pada periode ini.</td>
            </tr>
        @endforelse
    </table>

    @include('pdf._footer')
</body>

</html>
