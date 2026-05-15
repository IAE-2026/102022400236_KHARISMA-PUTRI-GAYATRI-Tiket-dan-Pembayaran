# Layanan Tiket & Pembayaran Public Transport

Repository ini berisi implementasi **Service Tiket & Pembayaran** sebagai bagian dari ekosistem Public Transport. Proyek ini dibangun untuk memenuhi kriteria Tugas 2 mata kuliah Integrasi Aplikasi Perusahaan (IAE).

---

## 👤 Identitas Mahasiswa
* **Nama:** Kharisma Putri Gayatri
* **NIM:** 102022400236
* **Kelas:** SI-48-09

---

## 🛠️ Ringkasan Implementasi
Service ini dikembangkan menggunakan framework **Laravel 11** dan dijalankan di dalam lingkungan **Docker**. Berikut adalah komponen utama yang telah diimplementasikan:

### 1. Arsitektur API (REST & GraphQL)
Membangun 4 endpoint strategis dengan prefix `/api/v1/` yang mencakup siklus hidup tiket:
- `GET /api/v1/tickets` : Mengambil daftar/riwayat pesanan tiket.
- `GET /api/v1/tickets/{id}` : Mengambil detail spesifik satu tiket berdasarkan ID.
- `POST /api/v1/tickets/{id}/payments` : Memproses konfirmasi pembayaran tiket.
- `POST /api/v1/tickets/{id}/send` : Simulasi penerbitan dan pengiriman E-Ticket.

Selain REST, service ini juga mendukung query data melalui **GraphQL (Lighthouse)** untuk fleksibilitas integrasi antar-layanan.

### 2. Sistem Keamanan (Middleware)
Implementasi keamanan berbasis **ApiKey** menggunakan header custom:
- **Header Key:** `X-IAE-KEY`
- **Header Value:** `(NIM)'
Setiap request yang tidak menyertakan header ini atau menggunakan nilai yang salah akan mendapatkan respon `401 Unauthorized`.

### 3. Dokumentasi Interaktif (Swagger/OpenAPI)
Dokumentasi teknis API dibangun menggunakan **L5-Swagger**. 
- Seluruh metadata API diisolasi pada file `SwaggerInfo.php` untuk menjaga kebersihan kode program.
- Dilengkapi fitur **Authorize** pada antarmuka web untuk memungkinkan pengujian API secara langsung dengan menginjeksikan header NIM.

### 4. Pola Pengembangan (Clean Code)
- **Service Pattern:** Memisahkan logika bisnis (Pembayaran & E-Ticket) dari Controller ke dalam folder `Services`.
- **API Response Standard:** Seluruh respon JSON diseragamkan mengikuti kontrak integrasi: `{ "status": "...", "message": "...", "data": [...] }`.

---

## 🚀 Cara Menjalankan Proyek

1. **Clone Repository:**
   ```bash
   git clone [URL_REPO]

2. **Masuk ke Direktori Proyek:**
   ```bash
   cd pemesanan-travel

3. **Setup Environment**
   ```bash
      cp .env.example .env

4. **Jalankan Docker Container:**
   ```bash
   docker-compose up -d --build

   # Masuk ke container untuk install library
   docker exec -it ticket-app composer install

   # Generate key aplikasi
   docker exec -it ticket-app php artisan key:generate

   # Jalankan migrasi database
   docker exec -it ticket-app php artisan migrate

   # Jalankan Seeder (opsional)
   docker exec -it ticket-app php artisan db:seed