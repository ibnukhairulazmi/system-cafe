# 🌿 Ruang Teduh - Landing Page Pelanggan

Frontend website interaktif untuk cafe **Ruang Teduh Coffee & Eatery**, dibangun dengan **React 19**, **TypeScript**, **Tailwind CSS v4**, dan **Vite**.

Website ini berfungsi sebagai etalase digital pelanggan yang terintegrasi langsung dengan API backend sistem Point of Sale (POS).

---

## 🌟 Fitur Utama

- **Hero & Cerita Cafe**: Desain visual hangat dan modern yang memperkenalkan konsep kenyamanan dan suasana Ruang Teduh.
- **Katalog Menu Interaktif**:
  - Filter menu berdasarkan kategori (Coffee, Non-Coffee, Food, Pastry).
  - Tampilan foto menu, deskripsi, dan harga.
  - Sinkronisasi data dinamis dari REST API POS backend (`/api/public/menu`).
- **Form Reservasi Meja Online**:
  - Input nama, kontak (WhatsApp/telepon), tanggal, jam kedatangan, dan jumlah orang (*party size*).
  - Fitur **Pre-Order Menu**: Pelanggan dapat langsung memilih menu makanan/minuman saat memesan meja.
  - Validasi formulir terintegrasi dan proteksi request throttle.
- **Peta & Lokasi**: Informasi alamat lengkap, jam operasional hari kerja dan akhir pekan, serta integrasi peta lokasi.
- **Responsif & Cepat**: Dioptimalkan untuk perangkat mobile, tablet, maupun desktop dengan performa loading instan berkat Vite.

---

## 🛠 Teknologi

- **Framework**: [React 19](https://react.dev)
- **Language**: [TypeScript](https://www.typescriptlang.org)
- **Build Tool**: [Vite](https://vitejs.dev)
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com)
- **Icons**: [Lucide React](https://lucide.dev)

---

## 🚀 Menjalankan Secara Lokal

### Prasyarat
- Node.js >= 20.x
- npm atau pnpm / yarn

### Instalasi & Menjalankan Dev Server

```bash
# 1. Masuk ke folder landing_page
cd landing_page

# 2. Pasang dependensi
npm install

# 3. Jalankan server development
npm run dev
```

Buka URL lokal yang muncul di terminal (biasanya `http://localhost:5173`).

### Perintah Script yang Tersedia

| Perintah | Deskripsi |
|---|---|
| `npm run dev` | Menjalankan local development server dengan Hot Module Replacement (HMR) |
| `npm run build` | Melakukan kompilasi TypeScript (`tsc -b`) dan build produksi ke folder `/dist` |
| `npm run preview` | Menjalankan preview lokal dari hasil build produksi |
| `npm run lint` | Menjalankan ESLint untuk memeriksa kualitas kode |

---

## 🔗 Integrasi Backend POS

Landing page ini terhubung ke backend Laravel POS pada endpoint publik berikut:

- `GET http://127.0.0.1:8000/api/public/menu` — Mengambil data kategori dan item menu aktif.
- `GET http://127.0.0.1:8000/api/public/site` — Mengambil profil cafe dan informasi kontak.
- `POST http://127.0.0.1:8000/api/public/reservations` — Mengirimkan reservasi meja baru.
