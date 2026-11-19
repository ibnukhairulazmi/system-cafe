# Ruang Teduh Cafe System

Sistem manajemen kafe terintegrasi yang mencakup halaman publik untuk pelanggan, aplikasi kasir (point of sale), manajemen operasional, dan dashboard pemilik.

## Daftar Isi

- [Gambaran Proyek](#gambaran-proyek)
- [Fitur Utama](#fitur-utama)
- [Struktur Proyek](#struktur-proyek)
- [Teknologi](#teknologi)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Data Awal](#data-awal)
- [API Publik](#api-publik)
- [Arsitektur Kode](#arsitektur-kode)
- [Lisensi](#lisensi)

## Gambaran Proyek

Proyek ini terdiri dari dua aplikasi yang berjalan terpisah:

| Aplikasi | Deskripsi |
|---|---|
| `landing-page/` | Halaman publik untuk pelanggan: katalog menu, reservasi meja, dan pesanan antar |
| `point-of-sale/` | Backend dan antarmuka untuk kasir, admin, dan superadmin |

Kedua aplikasi berkomunikasi melalui REST API. Backend mengelola data dan logika bisnis, sedangkan landing page menampilkan informasi dan menerima pesanan dari pelanggan.

## Fitur Utama

### Pelanggan

- Melihat katalog menu lengkap dengan foto, harga, dan kategori
- Reservasi meja secara online
- Pemesanan antar ke alamat pelanggan
- Pembayaran online dan pengecekan status pesanan dengan kode unik

### Kasir

- Transaksi penjualan: pilih menu, tambah ke keranjang, proses pembayaran
- Pembayaran tunai atau non-tunai
- Pembukaan dan penutupan shift beserta rekap transaksi
- Check-in reservasi pelanggan
- Pengelolaan pesanan antar yang masuk

### Admin

- Pengelolaan produk, kategori, dan resep bahan baku
- Pengelolaan stok: stok masuk, stok keluar, dan stok opname
- Pembuatan purchase order ke supplier
- Pemantauan reservasi dan pesanan antar
- Verifikasi transaksi
- Dashboard ringkasan operasional

### Superadmin

- Pengelolaan akun karyawan
- Laporan penjualan, stok, dan kasir dengan filter periode
- Ekspor laporan ke PDF dan Excel
- Audit log aktivitas karyawan
- Pengaturan umum kafe

## Struktur Proyek

```
cafe-system/
    landing-page/        Aplikasi frontend untuk pelanggan
        src/
            components/  Komponen antarmuka
            data/        Data statis situs
            hooks/       Custom hooks
            lib/         Utilitas dan klien API
            types/       Definisi tipe
    point-of-sale/       Aplikasi backend dan antarmuka internal
        app/
            Http/        Controller, middleware, dan request
            Models/      Model data
            Services/    Logika bisnis
        config/          File konfigurasi
        database/        Migrasi dan seeder
        resources/       Template tampilan, CSS, dan JavaScript
        routes/          Definisi rute
    ARCHITECTURE.md      Dokumentasi arsitektur
    README.md
```

## Teknologi

**Backend (`point-of-sale/`)**

- PHP dan Laravel
- Database relasional
- Blade, Alpine.js, dan Tailwind CSS untuk antarmuka
- Vite sebagai asset bundler

**Frontend (`landing-page/`)**

- React dan TypeScript
- Tailwind CSS
- Vite sebagai build tool

Versi spesifik tiap dependensi dapat dilihat di `composer.json` dan `package.json`.

## Instalasi

### Prasyarat

- PHP dan Composer
- Node.js dan npm
- Database yang didukung Laravel

### 1. Clone Repository

```bash
git clone https://github.com/your-username/cafe-system.git
cd cafe-system
```

### 2. Backend

```bash
cd point-of-sale
composer install
cp .env.example .env
php artisan key:generate
```

Sesuaikan file `.env` dengan lingkungan kamu (lihat bagian [Konfigurasi](#konfigurasi)), lalu jalankan:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

Backend tersedia di `http://localhost:8000`.

### 3. Frontend

```bash
cd landing-page
npm install
cp .env.example .env.local
npm run dev
```

Frontend tersedia di `http://localhost:5173`.

## Konfigurasi

### Backend

Atur variabel berikut pada file `.env` di folder `point-of-sale/`:

```env
APP_NAME="Ruang Teduh"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
```

Jika fitur pembayaran online digunakan, tambahkan kredensial penyedia pembayaran sesuai kebutuhan. Jangan pernah menyimpan nilai asli di dalam repository.

### Frontend

Atur alamat backend pada file `.env.local` di folder `landing-page/`:

```env
VITE_API_URL=http://localhost:8000
```

### Pengaturan Aplikasi

Beberapa pengaturan dapat diubah dari menu superadmin, seperti nama kafe, jam operasional, biaya pengantaran, dan pembulatan pembayaran.

### CORS

Backend hanya menerima permintaan dari alamat yang ditentukan pada `FRONTEND_URL`. Pastikan nilainya sesuai dengan alamat frontend yang digunakan.

## Data Awal

Seeder menyediakan data awal untuk membantu pengembangan, seperti akun pengguna, pengaturan, meja, supplier, bahan baku, kategori, dan produk.

```bash
cd point-of-sale

# Jalankan seeder
php artisan db:seed

# Atau reset database sekaligus seeder
php artisan migrate:fresh --seed
```

Pastikan password akun awal diganti sebelum aplikasi digunakan di lingkungan sebenarnya. Akun superadmin juga dapat dibuat melalui perintah:

```bash
php artisan app:create-superadmin
```

Sesuaikan nama perintah dengan yang terdaftar di `app/Console/Commands/CreateSuperadmin.php`.

## API Publik

Endpoint berikut digunakan oleh landing page dan tidak memerlukan autentikasi. Semua endpoint berada di bawah awalan `/api/public`.

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/menu` | Katalog menu |
| `GET` | `/site` | Informasi kafe |
| `GET` | `/reservations/availability` | Cek ketersediaan meja |
| `POST` | `/reservations` | Membuat reservasi |
| `GET` | `/reservations/{code}` | Detail dan status reservasi |
| `POST` | `/delivery-orders` | Membuat pesanan antar |
| `POST` | `/payments/qris` | Memulai pembayaran |
| `GET` | `/payments/qris/{code}/status` | Mengecek status pembayaran |

### Contoh Request

Cek ketersediaan meja:

```http
GET /api/public/reservations/availability?date=2025-01-15&time=19:00&guests=4
```

Buat reservasi:

```http
POST /api/public/reservations
Content-Type: application/json

{
  "name": "Nama Pelanggan",
  "phone": "08123456789",
  "email": "pelanggan@example.com",
  "date": "2025-01-15",
  "time": "19:00",
  "guests": 4,
  "note": "Catatan tambahan"
}
```

## Arsitektur Kode

Backend memakai pola service layer untuk memisahkan logika bisnis dari controller:

```
Request -> Controller -> Service -> Model -> Database
```

| Layer | Lokasi | Tanggung Jawab |
|---|---|---|
| Controller | `app/Http/Controllers/` | Menerima request, memanggil service, mengembalikan response |
| Service | `app/Services/` | Logika bisnis dan koordinasi antar model |
| Model | `app/Models/` | Representasi data dan relasi |
| Migration | `database/migrations/` | Skema database |
| Seeder | `database/seeders/` | Data awal |

Penjelasan lebih rinci tersedia di `ARCHITECTURE.md`.

## Lisensi

Proyek ini dibuat untuk keperluan internal Ruang Teduh Cafe. Semua hak cipta dilindungi.