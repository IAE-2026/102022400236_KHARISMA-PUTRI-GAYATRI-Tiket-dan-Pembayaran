# Log Prompting - Tugas 3 Service Tiket dan Pembayaran

## Identitas

- Nama: Kharisma Putri Gayatri
- NIM: 102022400236
- Kelas: SI-48-09
- Service: Tiket dan Pembayaran

## Tujuan Prompting

Log ini mencatat rangkaian interaksi dengan AI untuk: memahami kebutuhan tugas dari dokumen, memperbaiki environment Docker, mengimplementasikan SSO, SOAP Audit, RabbitMQ, serta menguji endpoint utama menggunakan Postman.

Fokus pengujian diarahkan ke empat endpoint berikut:

- `GET /api/v1/tickets`
- `GET /api/v1/tickets/{id}`
- `POST /api/v1/tickets/{id}/payments`
- `POST /api/v1/tickets/{id}/send`

## Kronologi Prompting

### Prompt 1: Membedah Kebutuhan Tugas dari PDF

**Prompt:**
> Baca PDF tugas di folder ini dong, tolong bantu breakdown detail dari Tugas 3 dan analisis error Docker aku yang dari tadi ga bisa di up.

**Hasil:**
Arsitektur yang harus diimplementasikan: Federated SSO, SOAP XML Client untuk audit, RabbitMQ Publisher untuk event-driven. AI juga mengingatkan bahwa `docker compose` harus dijalankan dari dalam folder `pemesanan-travel`.

### Prompt 2: Mengatasi Konflik Port dan File Compose pada Docker

**Prompt:**
> Docker compose error nih, katanya config file nggak ketemu dan port 8000 udah kepake. Gimana cara benerinnya?

**Hasil:**
AI mendeteksi bahwa file compose ada di dalam sub-folder `pemesanan-travel` dan port `8000` masih dipakai oleh container lama. Docker setup diperbaiki menjadi tiga service: `ticket_webserver_container` (Nginx:8000), `ticket_app_container` (PHP-FPM), dan `ticket_db_container` (PostgreSQL:5432).

### Prompt 3: Mengatasi Error 504 Gateway Timeout

**Prompt:**
> Kok ada error 504 Gateway Time-out ya pas nyoba akses endpoint?

**Hasil:**
Analisis log Nginx menunjukkan PHP-FPM terlalu lama menunggu respons dari SSO server pusat. AI membantu menambahkan konfigurasi timeout di Nginx (`docker-config/nginx/app.conf`) serta penanganan fallback HTTP pada pemanggilan SOAP (`app/Services/SOAPAuditService.php`) dan RabbitMQ (`app/Services/RabbitMQService.php`) agar request tidak menggantung jika jaringan sedang tidak stabil.

### Prompt 4: Implementasi Middleware Federated SSO (JWT RS256)

**Prompt:**
> Endpoint Tugas 3 ini wajib pakai SSO Dosen. Tolong buatin dan bimbing cara menggunakan middleware JWT-nya di Laravel.

**Hasil:**
Dibuat middleware `VerifyFederatedJWT` yang:
- Mengekstrak Bearer token dari header Authorization.
- Mengunduh JWKS publik dari server SSO.
- Mendaftarkan user dan role lokal berdasarkan `profile.email`.

### Prompt 5: Error Token Expired & Malformed UTF-8

**Prompt:**
> Tokennya dibilang expired terus, malah muncul error Malformed UTF-8. Padahal ngerasa udah bener copynya.

**Hasil:**
AI menemukan adanya clock skew antara jam container Docker dengan server SSO pusat, serta copy-paste token yang tidak bersih di Postman. Solusi diterapkan dengan menambahkan `SSO_JWT_LEEWAY=28800` di `.env` dan memperbaiki format error agar lebih jelas ketika JWT salah.

### Prompt 6: Sinkronisasi Environment Parameter Akun dan Team ID

**Prompt:**
> Akun warga pengujianku itu warga37@ktp.iae.id dan API key-nya KEY-MHS-314. Tolong sesuaikan konfigurasinya tanpa merusak Team ID.

**Hasil:**
File `.env` diperbarui dengan:
```env
TEAM_ID=TEAM-12
SSO_API_KEY=KEY-MHS-314
```
Setelah reload konfigurasi, pengujian login SSO dengan akun `warga37@ktp.iae.id` berhasil membuka endpoint tiket.

### Prompt 7: Integrasi Komunikasi SOAP Audit Server

**Prompt:**
> Pembayaran tiket kudu dikirim ke SOAP Audit Server sebagai bukti transaksi. Bantu bikin strukturnya.

**Hasil:**
Dibuat SOAPAuditService untuk membungkus payload transaksi ke dalam SOAP. Service ini juga berhasil menangkap ReceiptNumber dari respons server audit dan menyimpannya di database lokal.

### Prompt 8: Publish Transaksi Sukses ke Broker RabbitMQ

**Prompt:**
> Gimana caranya biar pas pembayaran sukses, service kita langsung publish event ke RabbitMQ?

**Hasil:**
Dibangun RabbitMQService untuk mengirim payload JSON ke exchange iae.central.exchange dengan routing key ticket.payment.completed. Fitur ini dilengkapi fallback HTTP jika port AMQP 5672 tiba-tiba tidak dapat diakses.

### Prompt 9: Penyusunan Skenario Uji Coba Final di Postman

**Prompt:**
> Semua fitur udah selesai dicoding, tolong buatin urutan testing yang bener di Postman buat mastiin gak ada yang skip.

**Hasil:**
AI menyusun urutan pengujian yang logis:
1. Login SSO → ambil token
2. `GET /api/v1/tickets`
3. `GET /api/v1/tickets/{id}`
4. `POST /api/v1/tickets/{id}/payments`
5. `POST /api/v1/tickets/{id}/send`

AI juga menekankan bahwa data tiket bisa diambil dari seeder lokal sehingga tidak perlu input manual.