# 📝 Nuvelo.id Feature Audit & Roadmap
**Dokumen Pengecekan Kesesuaian Fitur Website Nuvelo vs Standar TopUp Premium**

**Cara Penggunaan:**
- Beri tanda `[x]` pada fitur yang sudah selesai dan berjalan normal.
- Beri tanda `[-]` pada fitur yang sedang dikerjakan / *Work in Progress* (WIP).
- Biarkan `[ ]` pada fitur yang belum dikerjakan.

---

## 🌐 1. Fitur Sistem User / Frontend (React Inertia)

### Autentikasi & Keamanan
- [x] Registrasi & Login User Standard
- [ ] Login WhatsApp OTP
- [x] Social Login (Google)
- [x] Proteksi Cloudflare Turnstile

### Pengalaman Berbelanja (Shopping Experience)
- [x] Dashboard User (Modern & Responsive)
- [x] Order Top Up Multi Game
- [x] Validasi Nickname untuk game tertentu (Cek ID)
- [x] Input ID / Server dinamis per game
- [x] Halaman Price List
- [x] Ulasan Produk (Product Reviews)

### Promosi & Pemasaran
- [x] Promo Code System (Kupon diskon)
- [x] Flash Sale / Promo Produk dengan Countdown Timer
- [x] Popup Promo Banner

### Pembayaran & Transaksi
- [x] Multi Payment Method + Kalkulasi Auto Fee (Kode Unik / Admin Fee)
- [x] Invoice Modern dengan Realtime Status Update (Websocket/Polling)
- [x] Cek Invoice berdasarkan Order ID (Tanpa Login)
- [x] Cek Transaksi via Nomor WhatsApp

### Fitur Tambahan & Utilitas
- [x] Halaman Artikel / Berita (Blog)
- [x] Custom Pages (Syarat Ketentuan, Kebijakan Privasi, dll)
- [x] WhatsApp Bubble Chat
- [ ] Kalkulator Winrate (MLBB dll)
- [ ] Kalkulator Magic Wheel
- [ ] Kalkulator Zodiac
- [x] Support Dark Mode / Light Mode
- [x] UI Customization (Warna dan Branding Dinamis)
- [x] Dokumentasi API Frontend (Swagger-like) untuk user
- [x] Halaman API Credential (Bagi user yang ingin menjadi reseller via API)
- [x] Sitemap Otomatis

---

## ⚙️ 2. Fitur Admin Panel (Filament 5 Backend)

### Dashboard & Analitik
- [x] Dashboard Admin (Overview metrics)
- [x] Statistik Profit & Transaksi Harian
- [x] Grafik Status Order (Pie chart / Bar chart)

### Manajemen Game & Kategori
- [x] Manajemen Kategori Game
- [x] Manajemen Game (Master Data)
- [x] Manajemen Konfigurasi Game
- [x] Custom Input Field per Game (ID, Server, Zone, dll)
- [x] Panduan Order + Upload Gambar Panduan per Game
- [x] Pengaturan Validasi Nickname (Integrasi API Cek ID)

### Manajemen Produk & Harga (Integrasi Digiflazz)
- [ ] Manajemen Produk Lengkap
- [ ] Sistem Multi-Level Pricing (Harga Basic / Gold / Platinum)
- [ ] Logo Produk & Banner Game
- [ ] Produk Promosi / Flash Sale Configuration
- [ ] Sinkronisasi Produk Provider Otomatis (Cron Job)
- [ ] Integrasi Multi Provider (Bisa pilih provider selain Digiflazz nanti)
- [ ] Preview Import Produk Provider
- [ ] Cleanup Katalog Provider (Hapus SKU yang sudah tidak aktif)

### Manajemen Order & Keuangan
- [ ] Order Management Lengkap (Data Table dengan Filter/Search)
- [ ] Failed Order Management (Handle transaksi gagal/refund)
- [ ] Manual Batch Order dari Admin (Beli massal)
- [ ] Export Order ke format Excel / CSV
- [ ] Data Transaksi Detail
- [ ] Profit Management (Rekap laba/rugi)

### Payment Gateway Setup
- [ ] Manajemen Metode Pembayaran (Aktif/Nonaktif)
- [ ] Fetch Payment Channel Otomatis
- [ ] Integrasi Tripay (Current)
- [ ] *[Opsi Kedepan]* Integrasi Duitku / SMP Payment / QRISPY / DompetX

### Pengelolaan Konten (CMS)
- [x] Artikel / Blog Management
- [ ] Kategori Artikel
- [ ] Slider / Carousel Management
- [ ] Popup Promo Management
- [ ] Custom Page Management
- [ ] Product Review Management (Approve/Reject Ulasan)

### Konfigurasi Sistem & Pengaturan Web
- [x] Manajemen Kode Promo
- [x] SEO Meta & OpenGraph Settings
- [x] Logo, Favicon & Tema Warna Website
- [x] Konfigurasi Dark / Light Mode Default
- [x] Footer Links Settings
- [x] Sitemap Custom URL
- [x] OTP Setting & Cloudflare Turnstile Configuration
- [x] WhatsApp Bubble Setting (Nomor & Pesan Default)
- [x] Kalkulator Feature Toggle (Dilewati)

### Notifikasi & API (WhatsApp Fonnte/MPWA)
- [ ] Notification Setting Lengkap
- [ ] Template Notifikasi Order (Sukses/Pending/Gagal)
- [ ] Template OTP & Reset Password
- [ ] Test Kirim Notifikasi Button
- [ ] Pengaturan Gateway WhatsApp (Fonnte / MPWA)
- [ ] Manajemen User & Role (Admin vs Customer vs Reseller)
- [ ] API Credentials Management untuk Reseller
- [ ] API Request Logs (Pantau siapa saja yang hit API Anda)

---
*Dokumen ini digunakan sebagai referensi untuk AI Agent dalam melakukan pengembangan (Vibe Coding) menggunakan Laravel 12 dan Filament 5.*