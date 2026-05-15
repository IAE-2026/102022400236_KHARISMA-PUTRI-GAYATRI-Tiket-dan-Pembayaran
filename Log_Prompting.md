# 📝 Log Prompting & Diskusi Pengembangan Service

Dokumen ini mencatat rangkaian instruksi (prompt) dan proses pemecahan masalah sistematis dalam pengembangan **Service Tiket & Pembayaran** menggunakan bantuan AI Agent (Cursor & Gemini).

---

## 🚀 Fase 1: Inisialisasi & Konteks Tugas

**Prompt 1: Penetapan Konteks**
> "Saya baru saja memulai Tugas 2 Integrasi Aplikasi Perusahaan (IAE). Saya berperan sebagai pengembang service Tiket & Pembayaran dalam ekosistem Public Transport. Tugas saya adalah membangun service menggunakan Laravel yang wajib memiliki: Minimal 3 endpoint REST API fungsional, Keamanan menggunakan header X-IAE-KEY (NIM: 102022400236), Dokumentasi Swagger/OpenAPI (L5-Swagger), dan Implementasi GraphQL (Lighthouse). Bisakah kita berdiskusi mengenai langkah awal?"


---

## 🛠️ Fase 2: Troubleshooting Environment & Docker

**Prompt 2: Migrasi Drive & File Permission**
> "Saya mengalami error 'Permission Denied' saat menjalankan Docker di Drive C. Bagaimana cara memigrasikan proyek Laravel Docker saya ke Drive D:\ agar volume mapping WSL2 berjalan stabil, dan apa perintah Docker Compose untuk memastikan container 'ticket-app' menyala di environment baru tersebut?"

**Prompt 3: Penanganan Error Docker Daemon**
> "Docker saya error 'failed to connect to the docker API at npipe'. Bagaimana cara memastikan Docker Desktop dan engine WSL2 saya kembali sinkron agar perintah 'docker compose up' bisa berjalan kembali?"

---

## 🔐 Fase 3: Pengembangan Backend & Keamanan

**Prompt 4: Implementasi 4 Endpoint Utama & Security**
> "Bangun backend service menggunakan Laravel 11 + Docker dengan 4 endpoint inti: (1) GET /api/v1/tickets untuk riwayat, (2) GET /api/v1/tickets/{id} untuk detail, (3) POST /api/v1/tickets/{id}/payments untuk proses bayar, dan (4) POST /api/v1/tickets/{id}/send untuk e-ticket. Implementasikan middleware keamanan yang memvalidasi header 'X-IAE-KEY' bernilai '102022400236' dan pastikan semua response menggunakan format JSON seragam."

**Prompt 5: Refactoring ke Service Pattern**
> "Buatkan controller backend (TicketController), menggunakan Service Pattern agar logika pembayaran dan pengiriman e-ticket terpisah dari controller utama."

**Prompt 6: Penyesuaian Endpoint Sesuai Kontrak**
> "Saya ingin menyesuaikan endpoint API v1 saya menjadi: GET /api/v1/tickets, GET /api/v1/tickets/{id}, POST /api/v1/tickets/{id}/payments, dan POST /api/v1/tickets/{id}/send. Tolong sesuaikan routing dan controller agar sinkron."

---

## 📖 Fase 4: Dokumentasi API (Swagger)

**Prompt 7: Solusi Error @OA\Info**
> "Saya mendapatkan error 'Required @OA\Info() not found' pada Swagger. Tolong buatkan kode isolasi metadata pada file 'app/Http/Controllers/SwaggerInfo.php' yang mencakup @OA\Info dan @OA\SecurityScheme tipe apiKey (header: X-IAE-KEY), agar dokumentasi terbaca sistem."

**Prompt 8: Integrasi Security di UI Swagger**
> "Bagaimana cara memastikan fitur 'Authorize' di antarmuka web Swagger bisa digunakan untuk mengetes endpoint secara langsung? Saya ingin mencoba memasukkan NIM saya sebagai kunci akses di web tersebut."

---

## 🧪 Fase 5: Pengujian & Validasi

**Prompt 9: Verifikasi Header Keamanan**
> "Mengapa akses langsung via URL browser menghasilkan 'Unauthorized'? Tolong jelaskan cara cek header NIM tersebut melalui Swagger UI dan Postman agar saya yakin middleware sudah berjalan."

**Prompt 10: Pengujian End-to-End Tanpa Browser**
> "Bantu saya melakukan pengecekan semua endpoint (GET, POST, PATCH) menggunakan Postman. Apa saja body JSON yang harus dikirim dan header apa yang wajib disertakan?"

---

## 🧹 Fase 6: Finalisasi & Data Management

**Prompt 11: Manajemen Data Dummy & Seeder**
> "Bagaimana cara agar database saya bersih (empty state) saat masuk ke repo organisasi dosen, tapi file Seeder tetap tersedia jika sewaktu-waktu ingin melakukan demo pengisian data?"

**Prompt 12: Pembersihan Database (Clean State)**
> "Saya ingin menghapus data yang sudah ter-seed agar tidak masuk ke repository namun tetap bisa melakukan seeding kembali di masa depan."


---

### **🛠️ Analisis Troubleshooting & Keputusan Teknis**
* **Separation of Concerns:** Pemisahan metadata Swagger ke file khusus (`SwaggerInfo.php`) dilakukan untuk menghindari error parsing pada abstract class Laravel 11 dan menjaga kebersihan kode.
* **Custom Middleware:** Penggunaan header `X-IAE-KEY` berbasis NIM memastikan service mematuhi kontrak integrasi tim dan aman dari akses ilegal.
* **Environment Optimization:** Migrasi proyek ke Drive D dilakukan untuk mengatasi kendala *file permission* pada sistem Windows dan mengoptimalkan performa Docker.
* **Data Sanitization:** Penggunaan `migrate:fresh` menjamin database dalam kondisi bersih saat dilakukan tahap penilaian oleh penguji.

### **💡 Kesimpulan Log**
Melalui rangkaian prompt di atas, pengembangan berhasil diselesaikan dengan standar profesional:
1. **Backend:** Laravel 11 dengan *Service Pattern*.
2. **Keamanan:** Validasi Header NIM.
3. **DevOps:** Dockerized environment yang stabil.
4. **Dokumentasi:** Swagger UI interaktif & Skema GraphQL.