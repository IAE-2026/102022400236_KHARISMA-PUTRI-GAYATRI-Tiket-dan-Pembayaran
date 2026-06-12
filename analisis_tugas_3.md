# Analisis Tugas 3 - The Enterprise Digital City


- Nama: Kharisma Putri Gayatri
- NIM: 102022400236
- Kelas: SI-48-09
- Service: Tiket dan Pembayaran Public Transport


## Ringkasan Service

Service Tiket dan Pembayaran digunakan untuk mengelola proses bisnis pemesanan tiket travel, dari melihat data tiket, memproses pembayaran, hingga menerbitkan e-ticket. Pada Tugas 3, service ini diintegrasikan dengan service pusat dari dosen melalui:

1. SSO untuk validasi JWT warga.
2. SOAP untuk mengirim data transaksi pembayaran tiket yang sukses dan menerbitkan ReceiptNumber sebelum tiket dibayar.
3. RabbitMQ untuk mengirimkan message pembayaran ke service lain setelah tiket dibayar dan status lunas.

Endpoint utama yang diuji menggunakan Postman:

| No | Method | Endpoint | Tujuan | Auth |
|---:|---|---|---|---|
| 1 | `GET` | `/api/v1/tickets` | Melihat daftar tiket | Bearer JWT |
| 2 | `GET` | `/api/v1/tickets/{id}` | Melihat detail tiket | Bearer JWT |
| 3 | `POST` | `/api/v1/tickets/{id}/payments` | Memproses pembayaran tiket | Bearer JWT |
| 4 | `POST` | `/api/v1/tickets/{id}/send` | Menerbitkan e-ticket | Bearer JWT |


Dari semua proses yang ada, endpoint untuk pembayaran tiket yang dipilih adalah:

```
POST /api/v1/tickets/{id}/payments
```
Alasannya:

1. Pembayaran mengubah dan mencatat data penting pada tabel payments, yaitu status, payment_method, payment_date, dan soap_receipt_number.
2. Pembayaran berhubungan dengan keuangan sehingga kesalahan data dapat berdampak pada transaksi pelanggan.
3. Pembayaran menjadi syarat penerbitan e-ticket. Endpoint /send hanya dapat berhasil jika status pembayaran sudah completed atau lunas.
4. Pembayaran perlu dicatat ke sistem audit pusat melalui SOAP agar transaksi memiliki bukti audit eksternal berupa ReceiptNumber.
5. Pembayaran perlu diteruskan ke service lain melalui RabbitMQ agar yang lain dapat mengetahui bahwa transaksi tiket sudah selesai.

- Kenapa wajib mencatat ke SOAP Audit? Supaya ketika ada yang mengubah db lokal (misalnya status tiket yang belum dibayar menjadi LUNAS), akan terdeteksi pada sistem audit pusat karena tidak ada data masuk untuk transaksi tersebut. SOAP akan menerbitkan ReceiptNumber sebagai bukti transaksi yang valid.

- Untuk apa RabbitMQ? SOAP untuk melaporkan ke pusat, nah RabbitMQ ini untuk memberi tau ke service lain secara tidak langsung (asynchronous).

### Login SSO
Endpoint: POST https://iae-sso.virtualfri.id/api/v1/auth/token
Mengirim email warga dan password ke server pusat untuk mendapatkan token JWT. Token ini dipakai untuk request-request berikutnya

### Daftar Tiket
Endpoint: GET http://localhost:8000/api/v1/tickets
Menguji middleware lokal bisa baca token JWT atau tidak. Jika valid, akan menampilkan daftar tiket dari.

### Detail Tiket
Endpoint: GET http://localhost:8000/api/v1/tickets/1
Mengambil data tiket untuk ID 1, melihat status pembayarannya masih pending sebelum melakukan proses pelunansan.

### Pembayaran Tiket
Endpoint: POST http://localhost:8000/api/v1/tickets/1/payments
Mengubah status pembayaran di database, menembak SOAP untuk meminta ReceiptNumber dan mengirim message sukses ke RabbitMQ.

### Mengirim E-Ticket
Endpoint: POST http://localhost:8000/api/v1/tickets/1/send
Endpoint yang menerbitkan e-ticket, jika status sudah lunas.

## Sequence Diagram
![Sequence Diagram Pembayaran Tiket](TUGAS%203%20EAI.png)
