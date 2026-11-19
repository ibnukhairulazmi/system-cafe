<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <x-flash />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($periods as $p)
                    @php
                        $growth = $p['growth'] ?? 0;
                        $isPositive = $growth >= 0;
                    @endphp
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-stone-500">
                                    {{ $p['label'] }}
                                </h3>

                                
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $isPositive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-600 border-rose-200' }}">
                                    @if ($isPositive)
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                        +{{ number_format(abs($growth), 1) }}%
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                                        </svg>
                                        -{{ number_format(abs($growth), 1) }}%
                                    @endif
                                </span>
                            </div>

                            <p class="mt-3 text-3xl font-bold text-stone-900 tracking-tight">
                                @rupiah($p['net_sales'])
                            </p>
                            <p class="text-xs text-stone-400 mt-1">
                                Omzet bersih (sebelum pajak) &middot; {{ $p['transactions'] }} transaksi
                            </p>
                        </div>

                        <dl class="mt-4 pt-3.5 border-t border-stone-100 space-y-1.5 text-sm">
                            <div class="flex justify-between text-stone-600">
                                <dt>Pengeluaran bahan</dt>
                                <dd class="font-medium text-stone-800">@rupiah($p['purchases'])</dd>
                            </div>
                            <div class="flex justify-between font-semibold">
                                <dt class="text-stone-700">Laba kotor</dt>
                                <dd class="{{ $p['gross_profit'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                    @rupiah($p['gross_profit'])
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-stone-900">Tren Penghasilan</h3>
                        <div class="flex flex-wrap items-center gap-3 mt-1">
                            <p id="chartSubtitle" class="text-xs text-stone-400">
                                Statistik omzet bersih dan laba kotor 3 bulan terakhir
                            </p>
                            <span class="text-stone-300">&bull;</span>
                            <div class="flex items-center gap-3 text-xs text-stone-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #b45309;"></span>
                                    Omzet Bersih
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #059669;"></span>
                                    Laba Kotor
                                </span>
                            </div>
                        </div>
                    </div>

                    
                    <div class="inline-flex items-center rounded-xl bg-stone-100 p-1 border border-stone-200/80">
                        <button type="button" onclick="updateRevenueChart('3m')" id="btn-3m"
                            class="period-btn px-3.5 py-1.5 text-xs font-semibold rounded-lg transition bg-white text-stone-900 shadow-xs">
                            3 Bulan Terakhir
                        </button>
                        <button type="button" onclick="updateRevenueChart('30d')" id="btn-30d"
                            class="period-btn px-3.5 py-1.5 text-xs font-medium rounded-lg transition text-stone-500 hover:text-stone-900">
                            30 Hari Terakhir
                        </button>
                        <button type="button" onclick="updateRevenueChart('7d')" id="btn-7d"
                            class="period-btn px-3.5 py-1.5 text-xs font-medium rounded-lg transition text-stone-500 hover:text-stone-900">
                            7 Hari Terakhir
                        </button>
                    </div>
                </div>

                <div class="relative w-full h-[380px]">
                    <canvas id="revenueAreaChart"></canvas>
                </div>
            </div>


            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-stone-900">Penjualan produk bulan ini</h3>
                    </div>
                        <div class="flex items-center gap-3 text-xs text-stone-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #78716c;"></span>
                                Paling sedikit
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #b45309;"></span>
                                Paling banyak
                            </span>
                        </div>
                </div>

                @if ($product_sales->isEmpty())
                    <div class="py-12 text-center text-sm text-stone-400">
                        Belum ada data produk.
                    </div>
                @else
                    <div class="relative w-full h-[380px]">
                        <canvas id="productSalesChart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const chartDatasets = @json($chart_data);
        const ctx = document.getElementById('revenueAreaChart').getContext('2d');

        const netGradient = ctx.createLinearGradient(0, 0, 0, 380);
        netGradient.addColorStop(0, 'rgba(180, 83, 9, 0.25)');   // amber-700
        netGradient.addColorStop(1, 'rgba(180, 83, 9, 0.0)');

        const profitGradient = ctx.createLinearGradient(0, 0, 0, 380);
        profitGradient.addColorStop(0, 'rgba(5, 150, 105, 0.22)'); // emerald-600
        profitGradient.addColorStop(1, 'rgba(5, 150, 105, 0.0)');

        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartDatasets['3m'].labels,
                datasets: [
                    {
                        label: 'Omzet Bersih',
                        data: chartDatasets['3m'].net_sales,
                        borderColor: '#b45309',
                        borderWidth: 2.2,
                        backgroundColor: netGradient,
                        fill: true,
                        tension: 0.42,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#b45309',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2
                    },
                    {
                        label: 'Laba Kotor',
                        data: chartDatasets['3m'].gross_profit,
                        borderColor: '#059669',
                        borderWidth: 2.2,
                        backgroundColor: profitGradient,
                        fill: true,
                        tension: 0.42,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#059669',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1c1917',
                        titleColor: '#a8a29e',
                        bodyColor: '#fafaf9',
                        borderColor: '#292524',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y || 0;
                                return ' ' + label + ': Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: '#78716c',
                            font: { size: 11 },
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        grid: { color: '#f5f5f4' },
                        border: { display: false },
                        ticks: { display: false }
                    }
                }
            }
        });

        function updateRevenueChart(period) {
            const subtitles = {
                '3m': 'Statistik omzet bersih dan laba kotor 3 bulan terakhir',
                '30d': 'Statistik omzet bersih dan laba kotor 30 hari terakhir',
                '7d': 'Statistik omzet bersih dan laba kotor 7 hari terakhir'
            };

            document.getElementById('chartSubtitle').innerText = subtitles[period];

            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'text-stone-900', 'font-semibold', 'shadow-xs');
                btn.classList.add('text-stone-500', 'font-medium');
            });

            const activeBtn = document.getElementById('btn-' + period);
            activeBtn.classList.remove('text-stone-500', 'font-medium');
            activeBtn.classList.add('bg-white', 'text-stone-900', 'font-semibold', 'shadow-xs');

            revenueChart.data.labels = chartDatasets[period].labels;
            revenueChart.data.datasets[0].data = chartDatasets[period].net_sales;
            revenueChart.data.datasets[1].data = chartDatasets[period].gross_profit;
            revenueChart.update();
        }

        const productSales = @json($product_sales);
        const productTotal = @json($product_total);
        const barCanvas = document.getElementById('productSalesChart');

        if (barCanvas && productSales.length > 0) {
            const totalQty = Number(@json($product_sales->sum('qty')));
            const barColors = productSales.map(item => item.group === 'low' ? '#78716c' : '#b45309');
            const barHoverColors = productSales.map(item => item.group === 'low' ? '#57534e' : '#92400e');

            const valueLabels = {
                id: 'valueLabels',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    const meta = chart.getDatasetMeta(0);

                    ctx.save();
                    ctx.font = '600 12px ui-sans-serif, system-ui, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';

                    meta.data.forEach((bar, i) => {
                        const qty = Number(productSales[i].qty);
                        ctx.fillStyle = qty === 0 ? '#e11d48' : '#57534e';
                        ctx.fillText(qty, bar.x, bar.y - 5);
                    });

                    ctx.restore();
                }
            };

            new Chart(barCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: productSales.map(item => item.name),
                    datasets: [{
                        data: productSales.map(item => Number(item.qty)),
                        backgroundColor: barColors,
                        hoverBackgroundColor: barHoverColors,
                        borderRadius: 5,
                        borderSkipped: false,
                        maxBarThickness: 40,
                        categoryPercentage: 0.9,
                        barPercentage: 0.75
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 22 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1c1917',
                            titleColor: '#a8a29e',
                            bodyColor: '#fafaf9',
                            borderColor: '#292524',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                title: function (context) {
                                    return context[0].label;
                                },
                                label: function (context) {
                                    const item = productSales[context.dataIndex];
                                    const qty = Number(item.qty);
                                    const revenue = new Intl.NumberFormat('id-ID').format(item.revenue);
                                    return [
                                        ` Terjual: ${qty} porsi`,
                                        ` Pendapatan: Rp ${revenue}`,
                                        ` Kasir ${item.pos_qty} · Reservasi ${item.res_qty} · Delivery ${item.deliv_qty}`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: '#78716c',
                                font: { size: 11 },
                                autoSkip: false,
                                maxRotation: 50,
                                callback: function (value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 14 ? label.slice(0, 13) + '…' : label;
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f5f5f4' },
                            border: { display: false },
                            ticks: { display: false }
                        }
                    }
                },
                plugins: [valueLabels]
            });
        }
    </script>
</x-app-layout>