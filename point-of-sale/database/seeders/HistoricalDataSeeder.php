<?php

namespace Database\Seeders;

use App\Enums\DeliveryOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Enums\ShiftStatus;
use App\Enums\StockMovementType;
use App\Enums\VerificationStatus;
use App\Models\ActivityLog;
use App\Models\CafeTable;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationPayments;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HistoricalDataSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('role', Role::Superadmin)->first();
        $admin = User::where('role', Role::Admin)->first() ?? $superadmin;
        $kasir1 = User::where('email', 'kasir@cafe.test')->first() ?? $admin;
        $kasir2 = User::where('email', 'kasir2@cafe.test')->first() ?? $kasir1;

        $tables = CafeTable::all();
        $products = Product::where('is_active', true)->get();
        $ingredients = Ingredient::all();
        $suppliers = Supplier::all();

        if ($products->isEmpty() || $tables->isEmpty()) {
            return;
        }

        $this->seedPurchaseOrders($admin, $suppliers, $ingredients);

        $this->seedShiftsAndTransactions($kasir1, $kasir2, $admin, $tables, $products);

        $this->seedReservations($kasir1, $admin, $tables, $products);

        $this->seedDeliveryOrders($kasir1, $admin, $products);

        $this->seedActivityLogs($superadmin, $admin, $kasir1);
    }

    private function seedPurchaseOrders(User $admin, $suppliers, $ingredients): void
    {
        PurchaseOrderItem::query()->delete();
        StockMovement::where('reference_type', PurchaseOrder::class)->delete();
        PurchaseOrder::withTrashed()->forceDelete();

        $poDates = [85, 70, 56, 42, 28, 14, 3];
        $poCounter = 1;

        foreach ($poDates as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            $supplier = $suppliers->random();
            $poNo = sprintf('PO-%s-%04d', str_replace('-', '', $date), $poCounter++);

            $po = PurchaseOrder::create([
                'po_no'       => $poNo,
                'supplier_id' => $supplier->id,
                'created_by'  => $admin->id,
                'received_by' => $admin->id,
                'status'      => PurchaseOrderStatus::Received,
                'total'       => 0,
                'ordered_at'  => $date,
                'received_at' => $date,
                'note'        => 'Restock berkala bahan baku cafe',
                'created_at'  => Carbon::parse($date)->setHour(9),
                'updated_at'  => Carbon::parse($date)->setHour(14),
            ]);

            $total = 0;
            $itemsPool = $ingredients->random(min(6, $ingredients->count()));

            foreach ($itemsPool as $ing) {
                $qty = rand(5, 20) * ($ing->unit === 'gram' || $ing->unit === 'ml' ? 1000 : 10);
                $unitPrice = (float) $ing->last_cost;
                $subtotal = $qty * $unitPrice;
                $total += $subtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'ingredient_id'     => $ing->id,
                    'quantity'          => $qty,
                    'unit_price'        => $unitPrice,
                    'subtotal'          => $subtotal,
                    'created_at'        => $date,
                    'updated_at'        => $date,
                ]);

                $curr = (float) $ing->stock;
                $newStock = $curr + $qty;
                $ing->forceFill(['stock' => $newStock])->save();

                StockMovement::create([
                    'ingredient_id'  => $ing->id,
                    'type'           => StockMovementType::In,
                    'quantity'       => $qty,
                    'stock_after'    => $newStock,
                    'reference_type' => PurchaseOrder::class,
                    'reference_id'   => $po->id,
                    'user_id'        => $admin->id,
                    'note'           => "Penerimaan PO #{$poNo}",
                    'created_at'     => Carbon::parse($date)->setHour(14),
                ]);
            }

            $po->update(['total' => $total]);
        }
    }

    private function seedShiftsAndTransactions(User $kasir1, User $kasir2, User $admin, $tables, $products): void
    {
        TransactionItem::query()->delete();
        Transaction::withTrashed()->forceDelete();
        Shift::query()->delete();

        $roundingConfig = 500; // Pembulatan wajib kelipatan 500 / 1000
        $txGlobalCount = 1;

        for ($daysAgo = 89; $daysAgo >= 0; $daysAgo--) {
            $currentDate = now()->subDays($daysAgo);
            $isWeekend = $currentDate->isWeekend();
            $dateStr = $currentDate->toDateString();

            $shift1 = Shift::create([
                'user_id'       => $kasir1->id,
                'opened_at'     => $currentDate->copy()->setTime(8, 0, 0),
                'closed_at'     => $daysAgo === 0 ? null : $currentDate->copy()->setTime(15, 30, 0),
                'opening_cash'  => 200000,
                'expected_cash' => 200000,
                'closing_cash'  => $daysAgo === 0 ? null : 200000,
                'difference'    => $daysAgo === 0 ? null : 0,
                'status'        => $daysAgo === 0 ? ShiftStatus::Open : ShiftStatus::Closed,
                'note'          => $daysAgo === 0 ? 'Shift pagi sedang berjalan' : 'Shift pagi selesai rapi',
                'created_at'    => $currentDate->copy()->setTime(8, 0, 0),
                'updated_at'    => $currentDate->copy()->setTime(15, 30, 0),
            ]);

            $shift2 = null;
            if ($daysAgo > 0) {
                $shift2 = Shift::create([
                    'user_id'       => $kasir2->id,
                    'opened_at'     => $currentDate->copy()->setTime(15, 30, 0),
                    'closed_at'     => $currentDate->copy()->setTime(22, 30, 0),
                    'opening_cash'  => 200000,
                    'expected_cash' => 200000,
                    'closing_cash'  => 200000,
                    'difference'    => 0,
                    'status'        => ShiftStatus::Closed,
                    'note'          => 'Shift malam closing lancar',
                    'created_at'    => $currentDate->copy()->setTime(15, 30, 0),
                    'updated_at'    => $currentDate->copy()->setTime(22, 30, 0),
                ]);
            }

            $txCountToday = $daysAgo === 0 ? rand(6, 12) : ($isWeekend ? rand(18, 28) : rand(10, 16));
            $shift1CashTotal = 0;
            $shift2CashTotal = 0;

            for ($i = 0; $i < $txCountToday; $i++) {
                if ($daysAgo === 0) {
                    $hour = rand(8, 13);
                    $minute = rand(0, 59);
                    $assignedShift = $shift1;
                    $cashier = $kasir1;
                } else {
                    $hour = rand(8, 22);
                    $minute = rand(0, 59);
                    $assignedShift = ($hour < 16 || ! $shift2) ? $shift1 : $shift2;
                    $cashier = ($assignedShift->id === $shift1->id) ? $kasir1 : $kasir2;
                }

                $txTime = $currentDate->copy()->setTime($hour, $minute, rand(0, 59));
                $invoiceNo = sprintf('INV-%s-%04d', str_replace('-', '', $dateStr), $txGlobalCount++);

                $itemCount = rand(1, 4);
                $selectedProducts = $products->random($itemCount);

                $subtotal = 0;
                $txItemsData = [];

                foreach ($selectedProducts as $prod) {
                    $qty = rand(1, 3);
                    $price = (float) $prod->price;
                    $itemSubtotal = $qty * $price;
                    $subtotal += $itemSubtotal;

                    $txItemsData[] = [
                        'product_id'   => $prod->id,
                        'product_name' => $prod->name,
                        'price'        => $price,
                        'quantity'     => $qty,
                        'subtotal'     => $itemSubtotal,
                        'created_at'   => $txTime,
                        'updated_at'   => $txTime,
                    ];
                }

                $discount = 0;
                if (rand(1, 10) === 1) {
                    $discountPercent = rand(1, 2) === 1 ? 5 : 10;
                    $discount = round(($subtotal * $discountPercent) / 100);
                }

                $taxBase = $subtotal - $discount;
                $tax = round($taxBase * 0.11); // PPN 11%
                $rawTotal = $taxBase + $tax;

                $total = round($rawTotal / $roundingConfig) * $roundingConfig;
                $rounding = round($total - $rawTotal, 2);

                $isQris = rand(1, 100) <= 55; // 55% QRIS, 45% Tunai
                $paymentMethod = $isQris ? PaymentMethod::Qris : PaymentMethod::Cash;

                if ($isQris) {
                    $paidAmount = $total;
                    $changeAmount = 0.0;
                } else {
                    $paidAmount = $this->calculateRealisticCashPayment($total);
                    $changeAmount = round($paidAmount - $total, 2);

                    if ($assignedShift->id === $shift1->id) {
                        $shift1CashTotal += $total;
                    } else {
                        $shift2CashTotal += $total;
                    }
                }

                $isDineIn = rand(1, 100) <= 70; // 70% Dine In, 30% Take Away
                $tableId = $isDineIn ? $tables->random()->id : null;

                $transaction = Transaction::create([
                    'invoice_no'          => $invoiceNo,
                    'cashier_id'          => $cashier->id,
                    'shift_id'            => $assignedShift->id,
                    'order_type'          => $isDineIn ? 'dine_in' : 'takeaway',
                    'cafe_table_id'       => $tableId,
                    'subtotal'            => $subtotal,
                    'discount'            => $discount,
                    'tax_rate'            => 11.00,
                    'tax'                 => $tax,
                    'rounding'            => $rounding,
                    'total'               => $total,
                    'payment_method'      => $paymentMethod,
                    'paid_amount'         => $paidAmount,
                    'change_amount'       => $changeAmount,
                    'verification_status' => $daysAgo === 0 ? VerificationStatus::Verified : VerificationStatus::Verified,
                    'verified_by'         => $admin->id,
                    'verified_at'         => $txTime->copy()->addMinutes(rand(5, 45)),
                    'note'                => null,
                    'created_at'          => $txTime,
                    'updated_at'          => $txTime,
                ]);

                $transaction->items()->createMany($txItemsData);
            }

            if ($daysAgo > 0) {
                $shift1->update([
                    'expected_cash' => 200000 + $shift1CashTotal,
                    'closing_cash'  => 200000 + $shift1CashTotal,
                    'difference'    => 0,
                ]);

                if ($shift2) {
                    $shift2->update([
                        'expected_cash' => 200000 + $shift2CashTotal,
                        'closing_cash'  => 200000 + $shift2CashTotal,
                        'difference'    => 0,
                    ]);
                }
            } else {
                $shift1->update([
                    'expected_cash' => 200000 + $shift1CashTotal,
                ]);
            }
        }
    }

    private function seedReservations(User $kasir, User $admin, $tables, $products): void
    {
        ReservationPayments::query()->delete();
        ReservationItem::query()->delete();
        Reservation::query()->delete();

        $names = [
            'Budi Pratama', 'Siti Rahmawati', 'Dimas Anggara', 'Anisa Maharani',
            'Rizky Fauzi', 'Nadia Safitri', 'Fajar Ramadhan', 'Dian Permatasari',
            'Aditya Nugraha', 'Maya Kusuma', 'Kevin Sanjaya', 'Tiara Andini',
            'Gilang Dirga', 'Clarissa Putri', 'Bambang Soediro', 'Zahra Amalia'
        ];

        for ($daysAgo = 88; $daysAgo >= 1; $daysAgo -= rand(2, 4)) {
            $resDate = now()->subDays($daysAgo)->toDateString();
            $table = $tables->random();
            $name = $names[array_rand($names)];
            $code = 'RSV-' . strtoupper(Str::random(6));
            $partySize = min($table->capacity, rand(2, 6));

            $selected = $products->random(rand(2, 4));
            $totalAmount = 0;
            $itemsData = [];

            foreach ($selected as $prod) {
                $qty = rand(1, 2);
                $sub = $qty * (float) $prod->price;
                $totalAmount += $sub;
                $itemsData[] = [
                    'product_id'   => $prod->id,
                    'product_name' => $prod->name,
                    'price'        => $prod->price,
                    'quantity'     => $qty,
                    'subtotal'     => $sub,
                ];
            }

            $totalAmount = round($totalAmount / 500) * 500;
            $dpAmount = round(($totalAmount * 0.5) / 500) * 500;
            $settleAmount = $totalAmount - $dpAmount;

            $reservation = Reservation::create([
                'code'           => $code,
                'name'           => $name,
                'phone'          => '62812' . rand(10000000, 99999999),
                'reserved_date'  => $resDate,
                'reserved_time'  => sprintf('%02d:00:00', rand(11, 19)),
                'party_size'     => $partySize,
                'cafe_table_id'  => $table->id,
                'notes'          => 'Reservasi makan malam santai',
                'total_amount'   => $totalAmount,
                'payment_method' => 'qris',
                'payment_status' => 'paid',
                'paid_amount'    => $totalAmount,
                'status'         => ReservationStatus::Completed,
                'handled_by'     => $kasir->id,
                'handled_at'     => Carbon::parse($resDate)->setHour(20),
                'checked_in_at'  => Carbon::parse($resDate)->setHour(18),
                'created_at'     => Carbon::parse($resDate)->subDays(1),
                'updated_at'     => Carbon::parse($resDate)->setHour(20),
            ]);

            $reservation->items()->createMany($itemsData);

            ReservationPayments::create([
                'reservation_id' => $reservation->id,
                'type'           => 'dp',
                'amount'         => $dpAmount,
                'method'         => 'qris',
                'reference'      => 'MID-' . strtoupper(Str::random(10)),
                'paid_at'        => Carbon::parse($resDate)->subDays(1)->setHour(10),
                'received_by'    => $admin->id,
                'created_at'     => Carbon::parse($resDate)->subDays(1)->setHour(10),
                'updated_at'     => Carbon::parse($resDate)->subDays(1)->setHour(10),
            ]);

            ReservationPayments::create([
                'reservation_id' => $reservation->id,
                'type'           => 'settlement',
                'amount'         => $settleAmount,
                'method'         => 'cash',
                'reference'      => 'SETTLE-' . $reservation->id,
                'paid_at'        => Carbon::parse($resDate)->setHour(18),
                'received_by'    => $kasir->id,
                'created_at'     => Carbon::parse($resDate)->setHour(18),
                'updated_at'     => Carbon::parse($resDate)->setHour(18),
            ]);
        }

        $t1 = $tables[0] ?? $tables->random();
        $t1->update(['is_occupied' => true, 'occupied_at' => now(), 'occupied_by' => $kasir->id]);

        $resTodaySeated = Reservation::create([
            'code'           => 'RSV-TODAY1',
            'name'           => 'Rian Hidayat',
            'phone'          => '6281288887777',
            'reserved_date'  => today()->toDateString(),
            'reserved_time'  => '12:30:00',
            'party_size'     => 2,
            'cafe_table_id'  => $t1->id,
            'notes'          => 'Meeting makan siang',
            'total_amount'   => 120000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'paid_amount'    => 120000,
            'status'         => ReservationStatus::Seated,
            'handled_by'     => $kasir->id,
            'checked_in_at'  => now()->subMinutes(30),
            'handled_at'     => now()->subMinutes(30),
            'created_at'     => now()->subDay(),
        ]);
        $resTodaySeated->items()->create([
            'product_id'   => $products[0]->id,
            'product_name' => $products[0]->name,
            'price'        => $products[0]->price,
            'quantity'     => 2,
            'subtotal'     => $products[0]->price * 2,
        ]);
        ReservationPayments::create([
            'reservation_id' => $resTodaySeated->id,
            'type'           => 'dp',
            'amount'         => 60000,
            'method'         => 'qris',
            'paid_at'        => now()->subDay(),
            'received_by'    => $admin->id,
        ]);
        ReservationPayments::create([
            'reservation_id' => $resTodaySeated->id,
            'type'           => 'settlement',
            'amount'         => 60000,
            'method'         => 'qris',
            'paid_at'        => now()->subMinutes(30),
            'received_by'    => $kasir->id,
        ]);

        $t2 = $tables[1] ?? $tables->random();
        $resTonight = Reservation::create([
            'code'           => 'RSV-TODAY2',
            'name'           => 'Amanda Manopo',
            'phone'          => '6281299990000',
            'reserved_date'  => today()->toDateString(),
            'reserved_time'  => '19:00:00',
            'party_size'     => 4,
            'cafe_table_id'  => $t2->id,
            'notes'          => 'Dinner ulang tahun',
            'total_amount'   => 250000,
            'payment_method' => 'qris',
            'payment_status' => 'partial',
            'paid_amount'    => 125000,
            'status'         => ReservationStatus::Confirmed,
            'created_at'     => now()->subDays(2),
        ]);
        ReservationPayments::create([
            'reservation_id' => $resTonight->id,
            'type'           => 'dp',
            'amount'         => 125000,
            'method'         => 'qris',
            'paid_at'        => now()->subDays(2),
            'received_by'    => $admin->id,
        ]);

        Reservation::create([
            'code'           => 'RSV-FUTURE1',
            'name'           => 'Dr. Hendra Gunawan',
            'phone'          => '6281311112222',
            'reserved_date'  => today()->addDays(2)->toDateString(),
            'reserved_time'  => '18:30:00',
            'party_size'     => 6,
            'cafe_table_id'  => $tables->where('capacity', '>=', 6)->first()?->id ?? $t2->id,
            'notes'          => 'Reuni keluarga',
            'total_amount'   => 380000,
            'payment_method' => 'transfer',
            'payment_status' => 'paid',
            'paid_amount'    => 380000,
            'status'         => ReservationStatus::Confirmed,
            'created_at'     => now(),
        ]);
    }

    private function seedDeliveryOrders(User $kasir, User $admin, $products): void
    {
        DeliveryOrderItem::query()->delete();
        DeliveryOrder::query()->delete();

        $addresses = [
            ['Jl. Kemang Timur No. 45, RT 02/03', 'Dekat Lawson Kemang', 'fast', 18000],
            ['Apartemen Hampton Park Tower B Unit 1201, Cilandak', 'Lobby Tower B', 'saving', 8000],
            ['Jl. Bangka Raya No. 18, Mampang Prapatan', 'Pagar Hitam Depan Indomaret', 'saving', 8000],
            ['Jl. Senopati No. 88, Kebayoran Baru', 'Gedung Graha Lantai 3', 'fast', 18000],
            ['Jl. Antasari No. 10B, Cipete Selatan', 'Sebelah Apotek K-24', 'saving', 8000],
            ['Jl. Fatmawati Raya No. 102, Gandaria Selatan', 'Rumah Tingkat Putih', 'fast', 18000],
        ];

        $names = ['Sarah Sechan', 'Reza Rahadian', 'Maudy Ayunda', 'Nicholas Saputra', 'Chelsea Islan', 'Chicco Jerikho'];

        for ($daysAgo = 89; $daysAgo >= 1; $daysAgo -= rand(1, 2)) {
            $delivDate = now()->subDays($daysAgo)->toDateString();
            $addr = $addresses[array_rand($addresses)];
            $name = $names[array_rand($names)];
            $code = 'DEL-' . strtoupper(Str::random(6));

            $selected = $products->random(rand(2, 4));
            $subtotal = 0;
            $itemsData = [];

            foreach ($selected as $prod) {
                $qty = rand(1, 3);
                $sub = $qty * (float) $prod->price;
                $subtotal += $sub;
                $itemsData[] = [
                    'product_id'   => $prod->id,
                    'product_name' => $prod->name,
                    'price'        => $prod->price,
                    'quantity'     => $qty,
                    'subtotal'     => $sub,
                ];
            }

            $fee = $addr[3];
            $total = round(($subtotal + $fee) / 500) * 500;

            $order = DeliveryOrder::create([
                'code'             => $code,
                'customer_name'    => $name,
                'customer_phone'   => '62817' . rand(10000000, 99999999),
                'delivery_address' => $addr[0],
                'landmark'         => $addr[1],
                'delivery_tier'    => $addr[2],
                'delivery_fee'     => $fee,
                'subtotal_amount'  => $subtotal,
                'total_amount'     => $total,
                'delivery_date'    => $delivDate,
                'delivery_time'    => sprintf('%02d:%02d', rand(11, 20), rand(0, 59)),
                'is_asap'          => true,
                'payment_method'   => rand(1, 2) === 1 ? 'qris' : 'cash',
                'payment_status'   => 'paid',
                'paid_amount'      => $total,
                'customer_note'    => 'Mohon saus dan sambal dipisah ya kak.',
                'status'           => DeliveryOrderStatus::Delivered,
                'handled_by'       => $kasir->id,
                'handled_at'       => Carbon::parse($delivDate)->setHour(20),
                'created_at'       => Carbon::parse($delivDate)->setHour(12),
                'updated_at'       => Carbon::parse($delivDate)->setHour(14),
            ]);

            $order->items()->createMany($itemsData);
        }

        $delivToday = DeliveryOrder::create([
            'code'             => 'DEL-TODAY01',
            'customer_name'    => 'Iqbaal Ramadhan',
            'customer_phone'   => '6281899998888',
            'delivery_address' => 'Jl. Kemang Timur No. 12, Jakarta Selatan',
            'landmark'         => 'Lantai 2 Cafe Kopi',
            'delivery_tier'    => 'fast',
            'delivery_fee'     => 18000,
            'subtotal_amount'  => 85000,
            'total_amount'     => 103000,
            'delivery_date'    => today()->toDateString(),
            'delivery_time'    => 'ASAP',
            'is_asap'          => true,
            'payment_method'   => 'qris',
            'payment_status'   => 'paid',
            'paid_amount'      => 103000,
            'customer_note'    => 'Tolong segera diantar ya.',
            'status'           => DeliveryOrderStatus::OnDelivery,
            'handled_by'       => $kasir->id,
            'handled_at'       => now()->subMinutes(15),
            'created_at'       => now()->subMinutes(45),
        ]);
        $delivToday->items()->create([
            'product_id'   => $products[0]->id,
            'product_name' => $products[0]->name,
            'price'        => $products[0]->price,
            'quantity'     => 2,
            'subtotal'     => $products[0]->price * 2,
        ]);
    }

    private function seedActivityLogs(User $superadmin, User $admin, User $kasir): void
    {
        ActivityLog::query()->delete();

        $logs = [
            ['user_id' => $superadmin->id, 'action' => 'auth.login', 'properties' => ['ip' => '127.0.0.1'], 'created_at' => now()->subDays(90)],
            ['user_id' => $superadmin->id, 'action' => 'settings.updated', 'properties' => ['changes' => ['tax_rate' => '11', 'rounding' => '500']], 'created_at' => now()->subDays(90)],
            ['user_id' => $admin->id, 'action' => 'product.created', 'properties' => ['sku' => 'COF-009', 'name' => 'Kopi Susu Teduh'], 'created_at' => now()->subDays(85)],
            ['user_id' => $admin->id, 'action' => 'po.created', 'properties' => ['supplier' => 'CV Java Agro Perkasa'], 'created_at' => now()->subDays(85)],
            ['user_id' => $kasir->id, 'action' => 'shift.opened', 'properties' => ['opening_cash' => 200000], 'created_at' => now()->setHour(8)],
        ];

        foreach ($logs as $log) {
            ActivityLog::create($log);
        }
    }

    /**
     * Hitung pembayaran tunai realistis (kelipatan 500, 1.000, 10.000, 20.000, 50.000, 100.000).
     */
    private function calculateRealisticCashPayment(float $total): float
    {
        if (rand(1, 100) <= 35) {
            return $total;
        }

        $denominations = [5000, 10000, 20000, 50000, 100000];

        foreach ($denominations as $denom) {
            $candidate = ceil($total / $denom) * $denom;
            if ($candidate > $total) {
                return $candidate;
            }
        }

        return ceil($total / 50000) * 50000;
    }
}
