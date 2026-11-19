# 🏛 Arsitektur Sistem & Standar Clean Code (Cafe System)

Dokumen ini mendefinisikan standar arsitektur perangkat lunak, pemisahan tanggung jawab (*Separation of Concerns*), serta panduan refaktorisasi modular untuk seluruh role di sistem **Ruang Teduh (Cafe System)**: mulai dari **Kasir**, **Admin**, hingga **Superadmin**.

---

## 1. Prinsip Utama Arsitektur

Untuk menjaga sistem tetap mudah dirawat (*maintainable*), mudah diuji (*testable*), dan scalable, kode diatur ke dalam 5 lapisan (*5-Layer Architecture*):

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Presentation Layer (Blade Views & UI Components)         │
│    - resources/views/{role}/{feature}/...                   │
│    - Bebas dari logika query SQL & skrip inline masif       │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│ 2. Client-Side Script Layer (Modular JavaScript / Alpine.js)│
│    - resources/js/{role}/{feature}.js                       │
│    - Terkompilasi via Vite, declarative Alpine components   │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTP Request (Form / Fetch)
┌──────────────────────────────▼──────────────────────────────┐
│ 3. HTTP & Routing Layer (Controllers & Form Requests)       │
│    - app/Http/Controllers/{Role}/{Feature}Controller.php    │
│    - app/Http/Requests/{Role}/{Feature}Request.php          │
│    - Thin Controllers: validasi input & delegasi ke Service │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│ 4. Domain & Application Service Layer (Business Logic)      │
│    - app/Services/{Role}/{Feature}Service.php               │
│    - DB Transactions, kalkulasi matematis, stok, payment    │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│ 5. Data Access & Persistence Layer (Models & Scopes)        │
│    - app/Models/*.php                                       │
│    - Relasi, query scopes, attribute casts, mutators        │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Aturan Pemisahan Kode (*Separation of Concerns*)

### A. Lapisan View (`resources/views/`)
- **Tanggung Jawab**: Merender markup HTML dan komponen antarmuka pengguna (Tailwind CSS, Blade Icons, layout).
- **Larangan**:
  - ❌ Tidak boleh ada tag `<script>` inline ratusan baris. Skrip harus dipisahkan ke `resources/js/{role}/{feature}.js`.
  - ❌ Tidak boleh ada query database langsung (e.g. `Product::where(...)` atau `DB::table(...)` di dalam Blade).
  - ❌ Format direktori view harus konsisten menggunakan pola direktori fitur jamak:
    - Contoh: `resources/views/kasir/tables/index.blade.php` (bukan file tunggal `kasir/table.blade.php`).

### B. Lapisan Client-Side Script (`resources/js/`)
- **Tanggung Jawab**: Menangani reaktivitas UI di browser (keranjang kasir, pencarian instan, modal, kalkulasi kembalian).
- **Pola Desain**:
  - Alpine.js component didaftarkan secara modular:
    ```javascript
    export function posComponent(cfg) { ... }
    ```
  - Didaftarkan ke `resources/js/app.js` menggunakan `Alpine.data('pos', posComponent)`.
  - View Blade hanya menginisialisasi dengan data config:
    ```html
    <div x-data="pos({{ Js::from($config) }})"> ... </div>
    ```

### C. Lapisan Controller (`app/Http/Controllers/`)
- **Tanggung Jawab**:
  1. Menerima HTTP request dan memvalidasi via `FormRequest`.
  2. Memanggil method pada Domain Service terkait.
  3. Mengembalikan response: `View`, `RedirectResponse`, atau `JsonResponse`.
- **Target Ketebalan (*Thin Controller*)**: Maksimal 30–50 baris per method standar. Controller dilarang membungkus `DB::transaction` manual atau rumus bisnis kompleks secara langsung.

### D. Lapisan Service (`app/Services/`)
- **Tanggung Jawab**:
  - Seluruh logika bisnis (transaksi penjualan, potongan stok otomatis resep, check-in reservasi, sinkronisasi shift, audit trail).
  - Menjamin integritas data menggunakan `DB::transaction()`.
  - Melempar `DomainException` atau `ValidationException` jika ada pelanggaran aturan bisnis (misal: stok bahan tidak cukup, meja bentrok, shift belum dibuka).

---

## 3. Roadmap Refaktorisasi per Role

Proses refaktorisasi dilakukan secara bertahap dan diverifikasi role-by-role:

| Tahap | Role Target | Lingkup Fitur | Status |
|:---:|:---|:---|:---:|
| **Fase 1** | **Kasir (Cashier)** | POS System, Shift Kasir, Manajemen Meja, Reservasi, Delivery Order, Riwayat Transaksi | ✅ **Selesai** |
| **Fase 2** | **Admin (Operational)** | Dashboard Operasional, Produk & Resep, Kategori, Bahan & Stok, Purchase Orders, Suppliers, Verifikasi Transaksi | ✅ **Selesai** |
| **Fase 3** | **Superadmin (Owner)** | Laporan Finansial (Sales, PO, Stok), Manajemen Karyawan/User, Konfigurasi Sistem, Pemantauan & Audit | ✅ **Selesai** |

---

## 4. Standar Struktur Direktori Role Kasir

```
point-of-sale/
├── app/
│   ├── Http/
│   │   ├── Controllers/Kasir/
│   │   │   ├── PosController.php            # Ramping: mengambil data via PosCatalogueService
│   │   │   ├── CheckoutController.php       # Menangani API POST checkout
│   │   │   ├── ShiftController.php          # Delegasi ke ShiftService
│   │   │   ├── TableController.php          # Delegasi ke KasirTableService
│   │   │   ├── ReservationController.php    # Delegasi ke KasirReservationService
│   │   │   ├── DeliveryOrderController.php  # Delegasi ke KasirDeliveryService
│   │   │   └── TransactionController.php    # Delegasi ke KasirTransactionService
│   │   └── Requests/Kasir/
│   │       ├── CheckoutRequest.php
│   │       ├── OpenShiftRequest.php
│   │       └── CloseShiftRequest.php
│   └── Services/Kasir/
│       ├── PosCatalogueService.php          # Query katalog produk, stok porsi, & tables
│       ├── KasirTableService.php            # Logika occupy, release, & conflict check
│       ├── KasirReservationService.php      # Check-in tamu, pelunasan DP, status seated
│       ├── KasirDeliveryService.php         # Pengiriman, COD payment, completion
│       └── KasirTransactionService.php      # Query transaksi kasir & reprint receipt
├── resources/
│   ├── js/
│   │   └── kasir/
│   │       └── pos.js                       # Logic keranjang, diskon, bayar Alpine component
│   └── views/kasir/
│       ├── pos.blade.php                    # UI murni (bebas inline script 200 baris)
│       ├── shift.blade.php
│       ├── receipt.blade.php
│       ├── transactions.blade.php
│       ├── tables/
│       │   └── index.blade.php              # Standar RESTful directory
│       ├── reservations/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       └── delivery-orders/
│           ├── index.blade.php
│           └── show.blade.php
```

---

## 5. Standar Struktur Direktori Role Admin

```
point-of-sale/
├── app/
│   ├── Http/
│   │   ├── Controllers/Admin/
│   │   │   ├── DashboardController.php       # Ramping (15 baris): Delegasi ke AdminDashboardService
│   │   │   ├── ProductController.php         # Delegasi ke AdminProductService (CRUD + Resep)
│   │   │   ├── CategoryController.php        # Kategori master
│   │   │   ├── IngredientController.php      # Bahan baku master
│   │   │   ├── StockController.php           # Delegasi ke AdminStockService
│   │   │   ├── PurchaseOrderController.php   # Delegasi ke PurchaseOrderService
│   │   │   ├── ReservationController.php     # Delegasi ke AdminReservationService
│   │   │   ├── DeliveryOrderController.php   # Delegasi ke AdminDeliveryService
│   │   │   ├── SupplierController.php        # Supplier master
│   │   │   └── TransactionVerificationController.php # Delegasi ke AdminVerificationService
│   │   └── Requests/Admin/
│   │       ├── ProductRequest.php
│   │       ├── RecipeRequest.php
│   │       ├── PurchaseOrderRequest.php
│   │       ├── StockInRequest.php
│   │       ├── StockOutRequest.php
│   │       ├── StockOpnameRequest.php
│   │       └── VerifyTransactionRequest.php
│   └── Services/Admin/
│       ├── AdminDashboardService.php         # Agregasi metrik analitik dashboard admin
│       ├── AdminProductService.php           # Manajemen produk, upload gambar, cache & resep
│       ├── AdminStockService.php             # Mutasi stok, opname, stock in & out
│       ├── AdminReservationService.php       # Pengelolaan reservasi admin, catat DP/pelunasan
│       ├── AdminDeliveryService.php          # Monitor delivery & update status
│       └── AdminVerificationService.php      # Verifikasi transaksi, audit trail & flagging
└── resources/
    ├── js/
    │   └── admin/
    │       ├── recipe.js                     # Alpine component form resep dinamis
    │       └── purchase-order.js             # Alpine component form item PO & hitung otomatis
    └── views/admin/
        ├── dashboard.blade.php
        ├── categories/
        ├── delivery-orders/
        ├── ingredients/
        ├── products/
        │   ├── form.blade.php
        │   ├── index.blade.php
        │   └── recipe.blade.php              # Menggunakan declarative recipeForm component
        ├── purchase-orders/
        │   ├── form.blade.php                # Menggunakan declarative purchaseOrderForm component
        │   ├── index.blade.php
        │   └── show.blade.php
        ├── reservations/
        ├── shifts/
        ├── stock/
        ├── suppliers/
        └── verifications/
```

---

## 6. Standar Struktur Direktori Role Superadmin (Owner)

```
point-of-sale/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ReportController.php             # Laporan Keuangan Omnichannel (POS, Reservasi, Delivery)
│   │   ├── Controllers/Superadmin/
│   │   │   ├── DashboardController.php          # Delegasi ke ReportService
│   │   │   ├── EmployeeController.php           # Delegasi ke SuperadminEmployeeService
│   │   │   ├── SettingsController.php           # Delegasi ke SuperadminSettingsService
│   │   │   ├── ActivityLogController.php        # Delegasi ke SuperadminAuditService
│   │   │   ├── TransactionController.php        # Delegasi ke SuperadminAuditService
│   │   │   ├── ShiftController.php              # Delegasi ke SuperadminAuditService
│   │   │   ├── StockController.php              # Delegasi ke SuperadminAuditService
│   │   │   ├── ReservationController.php        # Delegasi ke SuperadminAuditService
│   │   │   ├── DeliveryOrderController.php      # Delegasi ke SuperadminAuditService
│   │   │   └── PurchaseOrderController.php      # Delegasi ke PurchaseOrderService
│   │   └── Requests/Superadmin/
│   │       ├── EmployeeFilterRequest.php
│   │       ├── StoreEmployeeRequest.php
│   │       ├── UpdateEmployeeRequest.php
│   │       ├── UpdateSettingsRequest.php
│   │       ├── ActivityLogFilterRequest.php
│   │       ├── TransactionFilterRequest.php
│   │       └── PurchaseOrderFilterRequest.php
│   └── Services/Superadmin/
│       ├── SuperadminEmployeeService.php        # CRUD pegawai, toggle aktif, & revoke sesi
│       ├── SuperadminSettingsService.php        # Konfigurasi PPN, diskon, & identitas cafe
│       └── SuperadminAuditService.php           # Pemantauan log, transaksi, audit shift, stok, & anomali
└── resources/
    └── views/superadmin/
        ├── dashboard.blade.php                  # Dashboard ringkasan finansial & performa periode
        ├── settings.blade.php                   # Form konfigurasi sistem
        ├── activity-logs/
        ├── employees/
        ├── purchase-orders/
        ├── reservations/
        ├── shifts/
        ├── stock/
        └── transactions/
```

