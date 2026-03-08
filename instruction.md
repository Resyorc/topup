# 🚀 Project Blueprint: SaaS Top-up Game (Automated System)

# 1. Ringkasan Proyek (Project Summary)

## 1.1 Visi Utama

Membangun sebuah platform **SaaS (Software as a Service)** untuk penjualan **top-up game dan produk digital** yang beroperasi secara **otonom penuh**.

Fokus utama proyek ini adalah menciptakan **aliran pendapatan pasif (passive income)** melalui efisiensi operasional maksimal, di mana sistem mampu menangani seluruh proses secara otomatis, mulai dari:

- Verifikasi pembayaran
- Pemrosesan transaksi
- Pengiriman produk digital

Tanpa memerlukan intervensi manusia dalam proses operasional harian.

---

# 1.2 Arsitektur Sistem  
## (The Hybrid & Service-Oriented Approach)

Berbeda dengan aplikasi Laravel tradisional yang menggunakan struktur **MVC (Model–View–Controller)** sederhana, proyek ini mengadopsi pendekatan **Hybrid-Service Architecture**.

---

## Frontend Architecture (Hybrid)

### Customer Side
Menggunakan **React + Inertia.js** untuk menciptakan pengalaman pengguna berbasis:

**Single Page Application (SPA)** yang:

- Sangat cepat
- Modern
- Responsif
- Minim reload halaman

### Admin Side
Menggunakan **Filament v5 (Blade + Livewire)** untuk menyediakan dashboard backend yang:

- Stabil
- Aman
- Cepat untuk pengolahan data
- Efisien dalam manajemen transaksi dan produk

---

## Logic Architecture (Service-Oriented)

Sistem mengimplementasikan **Service Layer Pattern**, di mana seluruh logika bisnis dan integrasi eksternal ditempatkan pada **Service Classes**.

Contoh layanan yang akan digunakan:

- `DigiflazzService`
- `TripayService`
- `TopupPriceService`
- `TransactionService`

Service layer ini menangani:

- Integrasi API provider (Digiflazz)
- Integrasi payment gateway (Tripay)
- Logika margin dan pricing engine
- Proses transaksi otomatis

### Keuntungan Pendekatan Ini

- Controller tetap **tipis (Thin Controllers)**
- Kode lebih **modular**
- Mudah diuji (**testable architecture**)
- Mudah dikembangkan dan diskalakan

---

# 1.3 Pilar Otomatisasi

## Real-time Processing

Sistem menggunakan **Laravel Reverb** untuk memungkinkan sinkronisasi status transaksi secara **real-time** antara server dan browser pengguna.

Contoh perubahan status transaksi:

```
Pending → Processing → Success
```

Tanpa perlu melakukan **refresh halaman**.

---

## Asynchronous Tasks

Komunikasi dengan API provider akan dijalankan menggunakan **Laravel Queue System**.

Queue digunakan untuk:

- Mengirim order ke provider
- Mengecek status transaksi
- Memproses callback
- Mengirim notifikasi

Keuntungan penggunaan queue:

- Website tetap responsif
- Tidak terjadi blocking request
- Mendukung retry otomatis jika terjadi kegagalan

---

## Autonomous Price Engine

Sistem memiliki **Price Engine otomatis** yang bertugas melakukan sinkronisasi harga dengan provider.

Fungsi utama:

- Mengambil **harga modal terbaru dari provider**
- Menghitung **harga jual berdasarkan margin**
- Memperbarui harga produk secara otomatis

### Formula Harga

```
Harga Jual = Harga Modal (Provider) + Margin + Biaya Gateway
```

Dengan mekanisme ini, sistem dapat menyesuaikan harga secara **dinamis** mengikuti perubahan harga dari provider.

---

# 2. Tech Stack & Environment

## 2.1 Framework & Core Engine

### Laravel 12 (PHP 8.3+)
Berperan sebagai **pusat kendali (orchestrator)** yang mengelola berbagai komponen utama sistem, termasuk:

- Service Classes
- Queue Jobs
- Broadcasting Events
- API Integration

Laravel juga bertindak sebagai **backend monolith** yang mengatur routing, keamanan, dan integrasi eksternal.

### MySQL
Digunakan sebagai **database relasional utama** untuk menyimpan:

- Data produk
- Data transaksi
- Data pengguna
- Relasi kategori dan game

Penggunaan MySQL memastikan **integritas transaksi** serta konsistensi relasi antar entitas dalam sistem.

---

# 2.2 Frontend Architecture (Customer Side)

### React
Digunakan untuk membangun **antarmuka pengguna yang dinamis dan interaktif**, seperti:

- Form input ID game
- Validasi username game
- Pemilihan nominal produk
- Halaman pembayaran

React memungkinkan pengalaman pengguna yang lebih cepat dan responsif.

### Inertia.js
Digunakan untuk menghubungkan **Laravel dan React** dalam arsitektur **modern monolith**.

Keuntungan pendekatan ini:

- Routing tetap di Laravel
- Controller tetap di backend
- View sepenuhnya menggunakan React

Hal ini menghindari kompleksitas arsitektur **full API + SPA terpisah**.

### Tailwind CSS v4.2.1
Framework styling utama yang digunakan untuk membangun UI.

Menggunakan **CSS-first engine terbaru** yang menawarkan:

- Performa styling tinggi
- Utility-first workflow
- Kemudahan implementasi **Dark Mode**
- Konsistensi desain komponen

---

# 2.3 Admin Panel (Internal Management)

### Filament v5
Framework admin panel berbasis **TALL Stack**, yang terdiri dari:

- **Tailwind CSS**
- **Alpine.js**
- **Laravel**
- **Livewire**

Filament menyediakan dashboard admin dengan fitur seperti:

- CRUD Resource otomatis
- Table management
- Form builder
- Filtering dan searching data
- Monitoring transaksi

Hal ini mempercepat pengembangan sistem backend tanpa harus membuat panel admin dari nol.

### Native File Upload
Menggunakan fitur **File Upload bawaan Filament** untuk menangani aset media seperti:

- Ikon game
- Logo produk

Pendekatan ini menghindari penggunaan **library pihak ketiga tambahan**, sehingga menjaga sistem tetap ringan dan stabil.

---

# 2.4 Real-time & Automation Engine

### Laravel Reverb
Digunakan sebagai **engine broadcasting real-time** yang memungkinkan server mengirimkan update langsung ke browser pengguna.

Contoh penggunaan:

- Update status transaksi
- Notifikasi perubahan status order

Dengan Reverb, status transaksi dapat berubah secara real-time tanpa perlu **refresh halaman**.

### Laravel Queues
Digunakan untuk menjalankan **proses asinkron di latar belakang**, seperti:

- Mengirim pesanan ke API Digiflazz
- Mengecek status transaksi
- Mengirim notifikasi
- Memproses webhook

Keuntungan penggunaan queue:

- Menghindari blocking request
- Website tetap cepat
- Mendukung retry otomatis jika terjadi kegagalan

---

# 2.5 Library Pendukung & Integrasi

### Spatie Laravel Permission
Digunakan untuk mengelola **role dan permission sistem** secara granular.

Contoh role:

- Super Admin
- Admin Operasional

Library ini memastikan akses ke fitur sensitif pada dashboard dapat dikontrol dengan baik.

### Akaunting / Laravel Money
Digunakan untuk memastikan **presisi perhitungan mata uang Rupiah (IDR)**.

Keuntungan penggunaan library ini:

- Menghindari kesalahan pembulatan angka
- Konsistensi format uang
- Standar pengelolaan nilai moneter di seluruh aplikasi

### Tripay Gateway
Digunakan sebagai **payment gateway utama** untuk memproses pembayaran pengguna.

Metode pembayaran yang didukung antara lain:

- QRIS
- Virtual Account
- Transfer bank

Tripay bertindak sebagai jembatan antara sistem dan berbagai metode pembayaran lokal.

### Digiflazz API
Merupakan **sumber utama produk digital** seperti:

- Top-up game
- Pulsa
- Paket data
- Token listrik

Integrasi dilakukan melalui **Service Layer khusus**, sehingga komunikasi dengan API tetap modular dan mudah dipelihara. 

---

# 3. Arsitektur Sistem  
## (Service-Oriented Approach)

Arsitektur sistem pada proyek ini dibagi menjadi tiga domain utama:

1. **Admin Domain** — Pengelolaan sistem internal  
2. **Customer Domain** — Interaksi pengguna akhir  
3. **Automation Engine** — Mesin backend yang menjalankan proses otomatis

Pendekatan ini memastikan sistem tetap **modular, scalable, dan mudah dipelihara**.

---

# 3.1 Dunia Admin — Manajemen Internal (`/admin`)

Sisi admin menggunakan **Filament v5 (Blade + Livewire)** sebagai pusat kendali operasional.

Dashboard ini berfungsi sebagai **control panel utama** untuk mengelola seluruh sistem.

## Manajemen Katalog Digital

Admin dapat melakukan operasi **CRUD (Create, Read, Update, Delete)** untuk:

- Kategori produk
- Produk digital
- Game top-up

Seluruh data terhubung langsung dengan **skema database relasional Laravel (Eloquent ORM)**.

---

## Monitoring Transaksi Agregat

Dashboard admin menyediakan informasi penting seperti:

- Status pesanan secara real-time
- Total transaksi harian
- Profit harian
- Monitoring kesehatan koneksi API provider

Hal ini membantu admin dalam **mengawasi performa sistem secara menyeluruh**.

---

## Konfigurasi Sistem

Admin juga memiliki akses ke pengaturan sistem seperti:

- Margin global
- Limit transaksi
- Pengaturan sistem lainnya

Manajemen akses pengguna dilakukan menggunakan:

```
Spatie Laravel Permission
```

Role yang umum digunakan:

- Super Admin
- Admin Operasional

---

# 3.2 Dunia Customer — Antarmuka Pengguna (`/`)

Sisi pengguna dibangun menggunakan **React + Inertia.js** untuk memberikan pengalaman pengguna yang modern dan cepat.

---

## Single Page Application (SPA)

Navigasi antar halaman berjalan menggunakan konsep **SPA (Single Page Application)**.

Keuntungan:

- Navigasi lebih cepat
- Tidak ada full page reload
- UX terasa seperti aplikasi native

Contoh navigasi:

```
Game List → Product Selection → Checkout → Payment
```

---

## Reactive UI

Komponen **React** menangani berbagai state interaktif seperti:

- Input ID game
- Validasi username
- Pemilihan nominal top-up
- Pemilihan metode pembayaran

Semua perubahan UI terjadi secara **dinamis tanpa reload halaman**.

---

## Inertia Bridge

**Inertia.js** berfungsi sebagai penghubung antara Laravel dan React.

Dengan pendekatan ini:

- Routing tetap berada di Laravel
- Controller tetap di backend
- Rendering UI sepenuhnya menggunakan React

Keuntungan utama:

- Tidak perlu membuat **REST API publik yang kompleks**
- Pengembangan lebih cepat
- Struktur aplikasi tetap sederhana

---

# 3.3 Otomatisasi & Service Layer  
## (The Engine)

Lapisan ini merupakan **mesin utama sistem otomatisasi**.

Alih-alih menempatkan logika pada controller, sistem menggunakan pendekatan **Service-Oriented Architecture**.

---

## Service Layer Integration

Semua logika integrasi API ditempatkan pada folder:

```
app/Services
```

Contoh service:

```
DigiflazzService
TripayService
TopupPriceService
TransactionService
```

Service ini bertanggung jawab untuk:

- Sinkronisasi harga provider
- Pengiriman order ke Digiflazz
- Pembuatan invoice Tripay
- Pemrosesan callback pembayaran

Pendekatan ini membuat **controller tetap tipis (Thin Controllers)** dan kode lebih modular.

---

## Laravel Queues (Background Workers)

Semua komunikasi dengan API provider dilakukan melalui **Laravel Queue System**.

Contoh proses yang dijalankan melalui queue:

- Mengirim order ke Digiflazz
- Mengecek status transaksi
- Mengirim notifikasi
- Retry transaksi yang gagal

Keuntungan:

- Website tetap cepat
- Tidak ada blocking request
- Sistem lebih stabil saat trafik tinggi

---

## Event-Driven Communication

Setiap perubahan status transaksi akan memicu **Event Laravel**.

Contoh event:

```
TransactionCreated
TransactionProcessing
TransactionSuccess
TransactionFailed
```

Event tersebut kemudian disiarkan menggunakan:

```
Laravel Reverb
```

Ke frontend React melalui **WebSocket broadcasting**.

Hasilnya:

- Status transaksi berubah **secara real-time**
- Pengguna melihat update tanpa refresh halaman
- Pengalaman pengguna lebih modern dan responsif

---

# 4. Skema Database (Schema Design)

Struktur database dirancang untuk mendukung sistem **top-up game dan produk digital otomatis**, dengan fokus pada integritas transaksi, fleksibilitas harga, dan keamanan data.

---

# 4.1 Kategori & Game

### Tabel: `categories`

| Kolom | Tipe | Keterangan |
|------|------|------------|
| id | BigInt | Primary key kategori |
| name | String | Nama kategori (Game, Pulsa, Data, dll) |
| slug | String | Slug untuk URL |

Digunakan untuk **mengelompokkan jenis produk digital**.

---

### Tabel: `games`

| Kolom | Tipe | Keterangan |
|------|------|------------|
| id | BigInt | Primary key game |
| category_id | BigInt (FK) | Relasi ke tabel `categories` |
| name | String | Nama game / layanan |
| slug | String | Slug URL |
| image | String | Path gambar / ikon game |
| is_active | Boolean | Status tampil di frontend |

Tabel ini merepresentasikan **entitas game atau layanan digital** yang tersedia di platform.

---

# 4.2 Produk & Otomatisasi Harga

### Tabel: `products`

| Kolom | Tipe | Keterangan |
|------|------|------------|
| id | BigInt | Primary key produk |
| game_id | BigInt (FK) | Relasi ke tabel `games` |
| provider_sku | String (Unique) | SKU produk dari Digiflazz |
| name | String | Nama produk |
| price_cost | Decimal | Harga modal dari API provider |
| margin_flat | Decimal | Margin tetap (misal: Rp2.000) |
| margin_percent | Decimal | Margin persentase (misal: 5%) |
| price_sell | Decimal | Harga jual yang tampil di website |
| is_available | Boolean | Status ketersediaan produk dari provider |

Tabel ini digunakan untuk menyimpan **produk digital yang dijual**, termasuk sistem **penentuan margin otomatis**.

---

# 4.3 Transaksi & Audit (UUID)

### Tabel: `transactions`

| Kolom | Tipe | Keterangan |
|------|------|------------|
| id | UUID (Primary) | ID transaksi unik untuk keamanan |
| invoice_id | String (Unique) | Kode invoice yang digunakan Tripay |
| user_id | BigInt (Nullable FK) | Relasi ke user (null jika guest checkout) |
| product_id | BigInt (FK) | Produk yang dibeli |
| customer_game_id | String | ID game pembeli |
| customer_zone_id | String (Nullable) | Zone ID (contoh: Mobile Legends) |
| amount | Decimal | Total pembayaran |
| profit | Decimal | Laba bersih dari transaksi |
| status | Enum | pending, paid, processing, success, failed |
| sn (Serial Number) | String (Nullable) | Serial dari Digiflazz jika transaksi sukses |
| api_logs | LongText / JSON | Log mentah response API untuk debugging |

Tabel ini merupakan **inti dari sistem transaksi** dan juga berfungsi sebagai **audit log** untuk troubleshooting.

---

# 4.4 Pengguna & Saldo (Member System)

### Tabel: `users`

| Kolom | Tipe | Keterangan |
|------|------|------------|
| id | BigInt | Primary key user |
| name | String | Nama pengguna |
| email | String | Email akun |
| balance | Decimal | Saldo deposit pengguna |

Fungsi tabel ini:

- Menyimpan data pengguna terdaftar
- Mendukung **fitur deposit saldo**
- Memungkinkan **pembayaran instan tanpa gateway**

Guest checkout tetap dimungkinkan karena `user_id` pada transaksi bersifat **nullable**.

---

## C. Transaksi (UUID / ULID)

### `transactions`

| Kolom | Tipe |
|------|------|
| id | uuid (primary) |
| invoice_id | string (unique) |
| user_id | foreign key (nullable) |
| product_id | foreign key |
| customer_game_id | string |
| customer_zone_id | string |
| customer_whatsapp | string |
| amount | decimal |
| profit | decimal |
| status | enum |
| payment_url | text |
| reference_id_provider | string |

---

# 5. Logika Bisnis Utama (Core Logic)

Bagian ini menjelaskan mekanisme inti sistem yang mengatur **penentuan harga produk** serta **alur transaksi otomatis** dari awal hingga selesai.

---

# 5.1 Price Engine (Otomatisasi Laba)

Mesin harga dikelola melalui **`TopupPriceService`**, yang bertanggung jawab untuk menghitung dan memperbarui harga jual secara otomatis.

Tujuan utama sistem ini adalah memastikan **fleksibilitas margin keuntungan** sekaligus menjaga harga tetap kompetitif.

---

## Komponen Formula Harga

### Harga Modal
Nilai `price_cost` yang diambil langsung dari **API Digiflazz**.

Nilai ini merepresentasikan **harga dasar dari provider**.

---

### Margin (Profit)

Margin keuntungan dapat diatur melalui dashboard admin dengan dua metode:

- **Fixed Margin**  
  Contoh:
  ```
  Rp2.000
  ```

- **Persentase Margin**  
  Contoh:
  ```
  5%
  ```

Margin dapat dikonfigurasi secara fleksibel **per produk atau per kategori**.

---

### Biaya Admin Gateway

Biaya tambahan yang berasal dari **MDR (Merchant Discount Rate)** dari **Tripay Payment Gateway**.

Biaya ini dapat disesuaikan agar:

- Margin tetap stabil
- Sistem tetap profitable

---

## Formula Harga

```
Harga Jual = Harga Modal + Margin + Biaya Gateway
```

Nilai hasil perhitungan disimpan pada kolom:

```
price_sell
```

---

## Skrip Sinkronisasi Berkala

Sistem melakukan sinkronisasi harga dan stok secara otomatis menggunakan **Laravel Task Scheduling**.

Command yang digunakan:

```bash
php artisan sync:prices
```

Command ini dijalankan secara berkala oleh **Laravel Scheduler**.

---

### Fungsi Sinkronisasi

Proses sinkronisasi melakukan beberapa tugas utama:

1. Mengambil **data harga terbaru dari Digiflazz**
2. Memperbarui nilai `price_cost`
3. Menghitung ulang `price_sell`
4. Memperbarui status ketersediaan produk

---

### Update Status Stok

Field berikut diperbarui secara otomatis:

```
is_available
```

Status ini menyesuaikan dengan ketersediaan produk pada provider.

---

# 5.2 Alur Transaksi Otomatis  
## (The Automated Workflow)

Sistem transaksi dirancang agar dapat berjalan **sepenuhnya otomatis**, dengan pengawasan sistem yang ketat.

---

# 1. Selection (Frontend)

Pengguna melakukan beberapa langkah awal di frontend React:

1. Memasukkan **ID Game**
2. Memasukkan **Zone ID** (jika diperlukan)
3. Memilih **produk / nominal top-up**

Sistem kemudian melakukan:

- Validasi ID game
- Mengambil username pemain dari API

Hal ini memastikan **data akun game valid sebelum pembayaran dilakukan**.

---

# 2. Payment Initiation (Backend)

Setelah data valid, backend Laravel menjalankan:

```
PaymentService
```

Service ini bertugas untuk:

- Membuat invoice pembayaran di **Tripay**
- Menyimpan data transaksi awal ke database

User kemudian:

- Dialihkan ke halaman pembayaran Tripay  
atau
- Mendapatkan kode pembayaran melalui halaman **Inertia React**

---

# 3. Verification (Webhook)

Setelah pembayaran dilakukan, **Tripay** mengirimkan callback ke sistem.

Proses verifikasi yang dilakukan:

1. Validasi **signature keamanan**
2. Verifikasi status pembayaran
3. Memastikan invoice cocok dengan transaksi

Jika valid:

```
status → paid
```

Sistem juga memancarkan event real-time menggunakan:

```
Laravel Reverb
```

Frontend akan langsung menerima update tanpa refresh halaman.

---

# 4. Execution (Laravel Queues)

Setelah pembayaran berhasil, sistem memicu **Queue Job**:

```
ProcessTopupJob
```

Job ini dijalankan di background worker.

---

## Proses di dalam Queue

Queue akan menjalankan:

```
TopupProviderService
```

Service ini bertugas untuk:

- Mengirim order ke **Digiflazz API**
- Menyimpan **reference ID provider**
- Menyimpan **Serial Number (SN)** jika transaksi berhasil

Data ini kemudian dicatat dalam tabel:

```
transactions
```

---

# 5. Notification (Post-Transaction)

Setelah transaksi berhasil (`success`), sistem secara otomatis mengirimkan notifikasi kepada pengguna.

---

## WhatsApp Notification

Pesan otomatis dikirimkan kepada pembeli:

```
Pesanan Berhasil
```

Pesan ini berisi:

- Nama produk
- ID game
- Serial number (jika ada)

---

## Email Notification

Sistem juga mengirimkan:

**Invoice PDF resmi**

Tujuan:

- Arsip transaksi
- Bukti pembayaran formal bagi pengguna

---

# 6. Rencana Notifikasi  
## (Notification & Broadcasting)

Sistem notifikasi dirancang untuk memberikan **transparansi status transaksi**, meningkatkan **kepercayaan pengguna**, serta mempermudah **monitoring operasional oleh admin**.

Notifikasi dibagi menjadi tiga kanal utama:

- Real-time Web Update
- Email Invoice
- WhatsApp Notification

---

# 6.1 Real-time Web Update (Laravel Reverb)

Fitur ini memastikan pengguna dapat melihat **perubahan status transaksi secara instan** tanpa perlu melakukan refresh halaman.

---

## Pemicu (Trigger)

Setiap perubahan pada kolom berikut akan memicu event:

```
transactions.status
```

Contoh perubahan status:

```
pending → paid → processing → success
```

---

## Mekanisme Broadcasting

Backend Laravel akan memancarkan event melalui **Private Channel WebSocket** menggunakan:

```
Laravel Reverb
```

Channel akan bersifat spesifik berdasarkan:

```
invoice_id
```

Contoh channel:

```
transactions.{invoice_id}
```

Dengan pendekatan ini, hanya pengguna yang memiliki invoice tersebut yang dapat menerima update status.

---

## User Experience

Frontend **React + Inertia** akan mendengarkan event broadcast dari server.

Tampilan status akan berubah secara otomatis, misalnya:

```
Menunggu Pembayaran
      ↓
Pembayaran Diterima
      ↓
Proses Pengiriman
      ↓
Transaksi Berhasil
```

Semua perubahan terjadi **secara real-time tanpa reload halaman**.

---

# 6.2 Arsip & Bukti Transaksi (Email)

Sistem juga menyediakan **bukti transaksi formal** yang dikirim melalui email.

Email ini berfungsi sebagai **arsip permanen transaksi** bagi pengguna.

---

## Konten Email

Email invoice berisi informasi berikut:

- Nama produk
- Nomor tujuan (Game ID / Nomor pelanggan)
- Waktu transaksi
- Total pembayaran
- Status transaksi
- Tautan menuju invoice digital

---

## Implementasi Teknis

Pengiriman email menggunakan **Laravel Mailable Class**.

Karakteristik implementasi:

- Template HTML modern
- Styling berbasis CSS
- Responsif untuk berbagai perangkat

Email akan dikirim menggunakan penyedia layanan email seperti:

- **Resend**
- **Mailgun**
- **SMTP Provider lainnya**

---

## Automasi Pengiriman

Email otomatis dikirim ketika status transaksi berubah menjadi:

```
paid
```

Hal ini memastikan pengguna langsung mendapatkan bukti pembayaran.

---

# 6.3 Operasional & Interaksi Cepat (WhatsApp API)

Integrasi **WhatsApp API** memungkinkan sistem berkomunikasi langsung dengan pengguna dan admin.

Tujuannya adalah memberikan **notifikasi instan yang lebih personal dan cepat dibanding email**.

---

# Notifikasi untuk Pembeli

## Status Diproses

Dikirim setelah pembayaran berhasil diverifikasi.

Pesan berisi konfirmasi bahwa:

- Pembayaran telah diterima
- Sistem sedang memproses pengiriman produk melalui provider Digiflazz

Contoh pesan:

```
Pembayaran Anda telah diterima.
Pesanan sedang diproses oleh sistem.
Mohon tunggu beberapa saat.
```

---

## Status Sukses

Dikirim setelah transaksi berhasil diproses oleh provider.

Pesan akan berisi:

- Nama produk
- ID game
- Serial Number (SN)
- Status transaksi

Contoh pesan:

```
Pesanan Anda berhasil.

Produk: Diamond Mobile Legends
ID Game: 12345678
SN: XJ2938DKL

Terima kasih telah menggunakan layanan kami.
```

---

# Notifikasi untuk Admin

## Laporan Penjualan

Admin akan menerima notifikasi setiap ada transaksi sukses.

Isi pesan biasanya berupa ringkasan seperti:

```
Transaksi Baru Berhasil

Produk: MLBB 86 Diamond
Harga: Rp25.000
Profit: Rp2.500
```

Hal ini membantu admin memantau **perputaran saldo dan penjualan secara pasif**.

---

## Peringatan Kegagalan

Jika terjadi kegagalan pada API provider atau proses transaksi, sistem akan mengirimkan **notifikasi kritis ke admin**.

Contoh kondisi kegagalan:

- Error dari Digiflazz API
- Timeout provider
- Produk tidak tersedia

Contoh pesan:

```
⚠️ Transaksi Gagal

Invoice: INV-20250312-001
Produk: MLBB 86 Diamond
Error: Provider Timeout
```

Dengan notifikasi ini, admin dapat segera melakukan **penanganan manual** jika diperlukan.

---

# 7. Strategi Implementasi (Next Steps)

Bagian ini menjelaskan tahapan implementasi sistem secara bertahap agar proses development berjalan terstruktur dan stabil.

---

# Tahap 1 — Fondasi Data & Model (Backend)

Fokus tahap ini adalah membangun **struktur data inti** yang aman, konsisten, dan mudah dikembangkan.

## Database Migration

Implementasi skema database menggunakan **Laravel 12 Anonymous Migrations** untuk tabel berikut:

- `categories`
- `games`
- `products`
- `transactions`

Migrasi harus mengikuti standar Laravel terbaru untuk menjaga konsistensi struktur database.

---

## Integrasi UUID / ULID

Tabel `transactions` wajib menggunakan **UUID** sebagai primary key.

Tujuan penggunaan UUID:

- Meningkatkan keamanan URL transaksi
- Menghindari kemungkinan enumerasi ID
- Melindungi privasi data pelanggan

---

## Eloquent Relationships

Relasi antar model harus didefinisikan dengan jelas menggunakan **Eloquent ORM**.

Contoh relasi utama:

```
Category → hasMany → Games
Game → hasMany → Products
Product → belongsTo → Game
Transaction → belongsTo → Product
Transaction → belongsTo → User (nullable)
```

Seluruh model harus menggunakan **type-hinting PHP 8.3+** yang ketat.

---

# Tahap 2 — Admin Control Center (Filament v5)

Tahap ini berfokus pada pembangunan **dashboard internal** untuk mengelola data operasional.

---

## Filament Resources

Implementasi resource CRUD menggunakan **Filament v5** untuk entitas berikut:

- Game
- Produk
- Transaksi

Fungsi utama:

- Input data awal produk
- Monitoring transaksi
- Pengelolaan katalog digital

---

## Native Media Handling

Konfigurasi **FileUpload bawaan Filament** untuk mengelola media seperti:

- Ikon game
- Gambar produk

Media akan disimpan secara efisien tanpa memerlukan library tambahan.

---

## Role Management

Menggunakan package:

```
Spatie Laravel Permission
```

Untuk mengatur hak akses dashboard.

Contoh role:

- Super Admin
- Admin Operasional

Hal ini memastikan fitur sensitif hanya dapat diakses oleh pengguna yang berwenang.

---

# Tahap 3 — Service Layer & Otomatisasi (The Engine)

Tahap ini membangun **logika bisnis inti** menggunakan pendekatan **Service-Oriented Architecture**.

---

## Core Services

Beberapa service utama yang perlu dibuat:

```
TopupProviderService
PaymentService
TopupPriceService
```

Fungsi masing-masing service:

### TopupProviderService
Mengelola komunikasi dengan **Digiflazz API** seperti:

- Mengirim order produk
- Mengecek status transaksi
- Sinkronisasi produk

---

### PaymentService
Mengelola integrasi dengan **Tripay API**:

- Membuat invoice pembayaran
- Memverifikasi callback pembayaran
- Mengelola metode pembayaran

---

### TopupPriceService
Mengelola **Price Engine** sistem:

- Menghitung margin
- Mengupdate harga jual
- Sinkronisasi harga provider

---

## Background Jobs

Implementasi **Laravel Queue Jobs** untuk menjalankan proses yang memerlukan waktu lama.

Contoh job utama:

```
ProcessTopupJob
```

Job ini bertugas:

- Mengirim order ke Digiflazz
- Menyimpan SN produk
- Mengupdate status transaksi

---

## Real-time Broadcaster

Mengonfigurasi **Laravel Reverb** untuk mengirimkan pembaruan status transaksi secara real-time.

Frontend React akan menerima update status seperti:

```
pending → paid → processing → success
```

Tanpa perlu melakukan refresh halaman.

---

# Tahap 4 — Reactive Frontend (React & Inertia)

Tahap ini berfokus pada pembangunan **antarmuka pengguna modern dan responsif**.

---

## Inertia Bridge

Menggunakan **Inertia.js** untuk menghubungkan backend Laravel dengan frontend React.

Keuntungan pendekatan ini:

- Tidak perlu membangun REST API terpisah
- Routing tetap berada di Laravel
- React hanya menangani tampilan dan state UI

---

## Tailwind v4.2.1 Styling

Menggunakan **Tailwind CSS v4.2.1** dengan pendekatan **CSS-first**.

Keunggulan:

- Performa styling tinggi
- Mudah mengimplementasikan **Dark Mode**
- Konsistensi desain komponen

---

## Interactive Components

Beberapa komponen interaktif yang akan dibangun di frontend:

- Form input ID Game
- Validasi username pemain
- Pemilihan nominal produk
- Halaman pembayaran
- Status transaksi real-time

Komponen React akan memberikan **respon instan kepada pengguna** tanpa reload halaman.

---

# 🛠️ Master Prompt untuk Antigravity (Update Tahap 1)

Gunakan prompt berikut untuk memulai implementasi **Tahap 1** bersama Antigravity.

```plaintext
Saya telah melakukan setup Laravel 12, React (Inertia), Filament v5, dan Tailwind v4.2.1.
Environment sudah siap. Gunakan Blueprint ini untuk eksekusi Tahap 1.

Tugas Utama:

1. Buat Migrations dan Models berdasarkan skema tabel:
   - Categories
   - Games
   - Products
   - Transactions

2. Tabel Transactions wajib menggunakan UUID sebagai primary key.

3. Implementasikan Filament Resources untuk:
   - Game
   - Product
   - Transaction

4. Gunakan FileUpload native Filament pada GameResource untuk kolom `image_path`.

Ketentuan Teknis:

- Terapkan PHP 8.3+ type-hinting yang ketat pada setiap Model.
- Gunakan gaya Laravel 12 Anonymous Migrations.
- Siapkan Service Class `TopupPriceService` sebagai kerangka awal logika penghitungan margin otomatis di folder `app/Services`.
- Pastikan semua komponen UI yang dihasilkan mendukung Tailwind v4.2.1 dengan pendekatan CSS-first.
```