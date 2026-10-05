# Dokumen Requirements — Fase 4: Sistem Enrollment, Pembayaran Midtrans & Dashboard Siswa

> **Status Fase**: Sebagian besar selesai (✅). Dua item masih pending (⏳):
> - Webhook Integration (`e-learning-external`)
> - Admin Transaction Report
>
> Dokumen ini berfungsi sebagai **referensi definitif** seluruh scope Fase 4 dan **panduan implementasi** item yang belum selesai.

---

## Pendahuluan

Fase 4 membangun sistem transaksi end-to-end untuk platform EduNusa: dari saat pengguna mengklik "Beli Sekarang" hingga kursus muncul di dasbor mereka sebagai hak akses seumur hidup. Fase ini mencakup integrasi pembayaran Midtrans (Snap Redirect / VT-Web), mesin polling status aktif, auto-enrollment otomatis, manajemen invoice, daur ulang pesanan, countdown timer, dashboard terpadu `/dashboard`, serta chatbot AI EduBot berbasis Ollama.

Scope Fase 4 juga mencakup dua item yang **belum selesai** dan perlu diselesaikan:
1. **Webhook Integration** — endpoint `e-learning-external` sebagai penerima callback asinkron dari Midtrans.
2. **Admin Transaction Report** — modul laporan keuangan di dashboard admin.

---

## Glosarium

| Istilah | Definisi |
|---|---|
| **The System** | Keseluruhan platform EduNusa (semua layanan microservices) |
| **The Frontend** | Aplikasi Next.js `e-learning-public` (App Router + Server Actions) |
| **The API_Gateway** | Layanan CodeIgniter 4 `e-learning-api` — validasi JWT, sanitasi, routing |
| **The Internal_Service** | Layanan CodeIgniter 4 `e-learning-internal` — satu-satunya DAO yang menyentuh DB |
| **The External_Service** | Layanan CodeIgniter 4 `e-learning-external` — penerima webhook pihak ketiga |
| **The Admin_Dashboard** | Aplikasi CodeIgniter 4 `e-learning-admin` — backoffice Admin & Finance |
| **The Midtrans_Gateway** | Layanan pembayaran Midtrans (Snap API & Status API) |
| **The Ollama_Service** | Layanan AI lokal Ollama (`host.docker.internal:11434`) |
| **Order** | Catatan pesanan di tabel `orders` — dibuat saat checkout |
| **Order_Code** | Kode unik pesanan format `TRX-{TIMESTAMP}-{RANDOM}` |
| **Invoice_Number** | Nomor invoice format `INV/{YEAR}/{SEQUENCE}` — HANYA untuk pesanan berstatus `paid` |
| **Enrollment** | Catatan kepemilikan kursus di tabel `enrollments` — dibuat setelah pembayaran sukses |
| **Settlement** | Status Midtrans untuk pembayaran transfer bank yang terverifikasi |
| **Capture** | Status Midtrans untuk pembayaran kartu kredit yang berhasil ditagih |
| **Active_Polling** | Mekanisme polling berkala dari Server Action Next.js ke Midtrans API |
| **Auto-Enrollment** | Proses otomatis membuat `Enrollment` saat pembayaran terkonfirmasi sukses |
| **Lifetime_Access** | Hak akses kursus seumur hidup — kursus tidak bisa dibeli ulang |
| **EduBot** | Chatbot AI berbasis model `deepseek-r1:8b` melalui Ollama |
| **VT-Web** | Metode Snap Redirect Midtrans — mengarahkan ke halaman Midtrans penuh (bukan popup) |
| **WIB** | Waktu Indonesia Barat (UTC+7) — timezone default tampilan |
| **MVVM** | Arsitektur Model-View-ViewModel: Entity → Repository → UseCase → ViewModel |
| **UUID_v4** | Universally Unique Identifier versi 4, format CHAR(36) |
| **Toast** | Notifikasi non-blocking menggunakan `react-hot-toast` (frontend) atau `AgyToast` (admin) |

---

## Alur Proses Bisnis (Business Process Flows)

### Alur 1 — Checkout & Pembayaran

```
[Siswa] → Klik "Beli Sekarang"
         → Cek Login? ──Tidak──→ Redirect /masuk
                       ↓Ya
         → Cek Lifetime Access? ──Sudah Punya──→ Redirect /dashboard
                                   ↓Belum
         → POST /api/v1/transactions/create
         → Internal_Service: cek order recycle / buat baru
         → Midtrans: create transaction → snap_redirect_url
         → Redirect ke snap_redirect_url (VT-Web layar penuh)
         → [Pengguna bayar di halaman Midtrans]
         → Midtrans redirect finish_redirect_url → /transaction/status?order_code=...
```

### Alur 2 — Active Polling (Verifikasi Pembayaran)

```
[Frontend /transaction/status] ← polling tiap N detik
    → Server Action checkPaymentStatus(order_code)
    → API_Gateway GET /api/v1/transactions/status/{order_code}
    → Internal_Service: cek status di DB
        ├─ Jika DB masih pending → cek Midtrans API
        │     ├─ settlement/capture → update orders.status=paid
        │     │                     → Auto-Enrollment
        │     │                     → update mentor_earnings
        │     │                     → return {status: "paid"}
        │     ├─ pending → return {status: "pending"}
        │     ├─ expire → update DB expired → return {status: "expired"}
        │     └─ deny/cancel → update DB failed → return {status: "failed"}
        └─ Jika DB sudah paid/failed/expired → return status DB langsung
```

### Alur 3 — Auto-Enrollment

```
[Internal_Service: payment confirmed]
    → SELECT * FROM orders WHERE order_code = ? AND status = 'paid'
    → CHECK: SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?
        ├─ Sudah ada → SKIP (idempotent)
        └─ Belum ada → INSERT INTO enrollments (id=UUID_v4, user_id, course_id, order_id, enrolled_at)
    → INSERT INTO mentor_earnings (komisi = price * revenue_share_pct)
    → return enrollment_id
```

### Alur 4 — Webhook Callback (⏳ BELUM SELESAI)

```
[Midtrans Server] → HTTP POST /webhook/midtrans
    → External_Service: terima, verifikasi signature_key
    → Validasi: hash SHA512(order_id + status_code + gross_amount + server_key)
    → Forward ke Internal_Service POST /api/webhooks/midtrans
    → Internal_Service: sama seperti Alur 2 (update DB + Auto-Enrollment)
    → Return HTTP 200 OK ke Midtrans (wajib < 15 detik)
```

### Alur 5 — Invoice Generation

```
[Pengguna minta invoice]
    → Cek orders.status
        ├─ status = 'paid' → ambil invoice_number (INV/{YEAR}/{SEQ})
        │                  → generate invoice PDF/tampilan
        └─ status ≠ 'paid' → tampilkan Order_Code TRX-... (BUKAN invoice)
```

### Alur 6 — Order Recycle & Auto-Expire

```
[Siswa klik "Beli Sekarang" untuk kursus yang sudah punya order pending/expired]
    → Internal_Service: cari order lama (user_id + course_id + status IN ['pending','expired'])
    → Jika ditemukan → UPDATE orders SET created_at=NOW(), status='pending'
                     → request token Midtrans baru
    → Jika tidak ditemukan → INSERT orders baru

[Auto-Expire Job / trigger]
    → SELECT orders WHERE status='pending' AND created_at < NOW() - INTERVAL 1 HOUR
    → UPDATE status='expired'
```

### Alur 7 — EduBot AI Chatbot

```
[Siswa kirim pesan di ChatWidget]
    → Client Component → Server Action chatWithEduBot(message, history)
    → Server Action (proxy) → POST http://host.docker.internal:11434/api/chat
        body: {model: "deepseek-r1:8b", messages: [{role,content}...], stream: false}
    → Ollama response → parse → return assistant_message
    → ChatWidget update UI dengan pesan baru
    → Jika ada tag [ESKALASI|KATEGORI|PRIORITAS|ROLE] → (Fase 8) buat support_ticket
```

---

## Requirements

### Requirement 1 — Pembuatan Pesanan (Order Creation)

**User Story:** Sebagai siswa, saya ingin membeli kursus dengan mudah sehingga saya bisa langsung mengakses materi pembelajaran.

#### Acceptance Criteria

1. WHEN seorang pengguna yang belum login mengklik tombol "Beli Sekarang", THEN THE Frontend SHALL mengarahkan pengguna ke halaman `/masuk` dengan parameter redirect kembali ke halaman kursus tersebut.

2. WHEN seorang pengguna yang sudah login mengklik "Beli Sekarang" pada kursus yang sudah dimilikinya, THEN THE Frontend SHALL menampilkan tombol "Lanjutkan Belajar" dan menonaktifkan aksi pembelian.

3. WHEN seorang pengguna yang sudah login mengklik "Beli Sekarang" pada kursus yang belum dimilikinya, THEN THE API_Gateway SHALL meneruskan request `POST /api/v1/transactions/create` ke `Internal_Service` dengan `user_id` dan `course_id`.

4. WHEN `Internal_Service` menerima request pembuatan order, THEN THE Internal_Service SHALL memeriksa apakah sudah ada order dengan status `pending` atau `expired` untuk kombinasi `user_id` dan `course_id` yang sama.

5. WHEN order lama dengan status `pending` atau `expired` ditemukan, THEN THE Internal_Service SHALL mendaur ulang order tersebut dengan memperbarui `created_at` ke waktu sekarang dan mereset status ke `pending`, agar countdown timer sinkron dengan batas waktu Midtrans.

6. WHEN tidak ada order lama yang ditemukan, THEN THE Internal_Service SHALL membuat catatan order baru di tabel `orders` dengan:
   - `id`: UUID v4 baru
   - `order_code`: format `TRX-{TIMESTAMP_EPOCH_MS}-{6_DIGIT_RANDOM}`
   - `status`: `pending`
   - `created_at`: waktu sekarang (server time)
   - `user_id`, `course_id`, `amount` dari data kursus

7. WHEN order berhasil dibuat atau didaur ulang, THEN THE Internal_Service SHALL meminta token transaksi ke `Midtrans_Gateway` dengan `order_id`, `gross_amount`, dan data pelanggan.

8. WHEN `Midtrans_Gateway` mengembalikan `snap_redirect_url`, THEN THE Frontend SHALL mengarahkan browser pengguna ke URL tersebut menggunakan metode redirect penuh (VT-Web), **bukan** popup atau iframe.

9. IF `Midtrans_Gateway` mengembalikan error saat pembuatan transaksi, THEN THE Frontend SHALL menampilkan Toast notifikasi error yang deskriptif dan mempertahankan state checkout tanpa reload halaman.

10. IF pengguna sudah memiliki enrollment aktif untuk kursus tersebut (Lifetime Access), THEN THE Internal_Service SHALL menolak pembuatan order baru dengan kode error `409 CONFLICT` dan pesan "Anda sudah memiliki akses kursus ini selamanya."

---

### Requirement 2 — Active Polling Engine (Verifikasi Pembayaran)

**User Story:** Sebagai siswa, saya ingin mengetahui status pembayaran saya secara real-time tanpa harus merefresh halaman secara manual.

#### Acceptance Criteria

1. WHEN halaman `/transaction/status?order_code={kode}` dibuka, THE Frontend SHALL langsung menampilkan status terkini dari order tersebut dari database.

2. WHILE status order adalah `pending`, THE Frontend SHALL mengeksekusi Server Action `checkPaymentStatus(order_code)` secara berkala dengan interval yang ditentukan untuk memperbarui tampilan status.

3. WHEN Server Action `checkPaymentStatus` dipanggil, THE API_Gateway SHALL memverifikasi status langsung ke `Midtrans_Gateway` melalui Midtrans Transaction Status API.

4. WHEN `Midtrans_Gateway` mengembalikan status `settlement` atau `capture`, THEN THE Internal_Service SHALL:
   - Memperbarui `orders.status` menjadi `paid`
   - Mencatat `orders.payment_date` ke waktu sekarang
   - Mengeksekusi proses Auto-Enrollment (lihat Requirement 3)
   - Mengembalikan status `paid` ke frontend

5. WHEN `Midtrans_Gateway` mengembalikan status `expire`, THEN THE Internal_Service SHALL memperbarui `orders.status` menjadi `expired` dan mengembalikan status tersebut.

6. WHEN `Midtrans_Gateway` mengembalikan status `deny` atau `cancel`, THEN THE Internal_Service SHALL memperbarui `orders.status` menjadi `failed` dan mengembalikan status tersebut.

7. WHEN status order sudah `paid`, `failed`, atau `expired` di database, THE Internal_Service SHALL mengembalikan status tersebut **tanpa** melakukan request ulang ke `Midtrans_Gateway` (short-circuit untuk efisiensi).

8. WHEN polling mendeteksi status berubah menjadi `paid`, THE Frontend SHALL menghentikan polling, menampilkan Toast sukses "Pembayaran berhasil! Selamat belajar 🎉", dan mengarahkan pengguna ke `/dashboard` setelah 3 detik.

9. WHEN polling mendeteksi status berubah menjadi `expired` atau `failed`, THE Frontend SHALL menghentikan polling dan menampilkan halaman status yang sesuai dengan tombol "Coba Lagi" atau "Hubungi Bantuan".

10. IF Server Action `checkPaymentStatus` gagal terhubung ke `Midtrans_Gateway` (timeout/network error), THEN THE Frontend SHALL menampilkan Toast peringatan sementara tanpa menghentikan polling, dan mencoba kembali pada interval berikutnya.

---

### Requirement 3 — Auto-Enrollment Engine

**User Story:** Sebagai siswa, saya ingin kursus yang sudah saya bayar langsung tersedia di dashboard saya tanpa perlu menunggu konfirmasi manual.

#### Acceptance Criteria

1. WHEN pembayaran terkonfirmasi sukses (`settlement` atau `capture`), THE Internal_Service SHALL secara otomatis membuat catatan enrollment baru di tabel `enrollments` dengan:
   - `id`: UUID v4 baru
   - `user_id`: ID pengguna yang membayar
   - `course_id`: ID kursus yang dibeli
   - `order_id`: ID order yang terkait
   - `enrolled_at`: timestamp waktu konfirmasi pembayaran

2. THE Auto-Enrollment SHALL bersifat **idempotent**: jika record dengan `user_id` dan `course_id` yang sama sudah ada di `enrollments`, THE Internal_Service SHALL melewati proses insert dan mengembalikan enrollment yang sudah ada tanpa error.

3. WHEN enrollment berhasil dibuat, THE Internal_Service SHALL membuat catatan alokasi komisi instruktur di tabel `mentor_earnings` dengan:
   - `order_id`, `mentor_id` (dari `courses.instructor_id`), `course_id`
   - `amount`: `orders.amount * revenue_share_percentage`
   - `status`: `pending_payout`

4. WHEN auto-enrollment selesai, THE System SHALL memperbarui status tampilan "Beli Sekarang" menjadi "Lanjutkan Belajar" di semua halaman yang menampilkan kursus tersebut pada sesi pengguna yang bersangkutan.

5. IF proses insert `enrollments` gagal karena alasan teknis (duplicate key, DB error), THEN THE Internal_Service SHALL mencatat error ke log sistem dan mengembalikan respons error `500` agar polling frontend dapat menanganinya — status `orders.status` TIDAK boleh diubah ke `paid` tanpa keberhasilan enrollment.

---

### Requirement 4 — Proteksi Akses Seumur Hidup (Lifetime Access)

**User Story:** Sebagai siswa, saya ingin yakin bahwa kursus yang sudah saya beli tidak akan pernah hilang dan saya tidak perlu membayar ulang.

#### Acceptance Criteria

1. THE Frontend SHALL memanggil Server Action `getUserEnrollments(user_id)` setiap kali halaman katalog (`/kursus`) atau detail kursus (`/kursus/[slug]`) dimuat oleh pengguna yang sudah login.

2. WHEN `getUserEnrollments` mengembalikan daftar enrollment yang berisi `course_id` yang cocok dengan kursus yang sedang ditampilkan, THE Frontend SHALL mengganti tombol "Beli Sekarang" dengan tombol "Lanjutkan Belajar" berwarna hijau yang mengarah ke `/dashboard`.

3. THE tombol "Lanjutkan Belajar" SHALL dinonaktifkan untuk aksi pembelian — klik pada tombol ini HANYA mengarahkan ke `/dashboard`, TIDAK memanggil endpoint pembuatan order.

4. WHEN pengguna yang sudah login mengakses halaman detail kursus yang dimilikinya, THE Frontend SHALL menampilkan badge "Sudah Dimiliki" pada thumbnail atau header kursus.

5. IF Internal_Service menerima request pembuatan order untuk `user_id` + `course_id` yang sudah ada di tabel `enrollments`, THEN THE Internal_Service SHALL menolak dengan HTTP `409 Conflict`.

6. THE Lifetime Access check SHALL dilakukan di dua level: frontend (UX) dan backend (API enforcement), sehingga tidak bisa di-bypass dengan manipulasi client-side.

---

### Requirement 5 — Manajemen Status Order & Countdown Timer

**User Story:** Sebagai siswa, saya ingin tahu berapa waktu tersisa untuk menyelesaikan pembayaran saya agar tidak kehabisan waktu.

#### Acceptance Criteria

1. WHILE status order adalah `pending`, THE Frontend SHALL menampilkan countdown timer yang menghitung mundur dari `orders.created_at + 1 jam` ke waktu sekarang dalam format `HH:MM:SS`.

2. WHEN countdown timer mencapai `00:00:00`, THE Frontend SHALL menampilkan status "Pesanan Kedaluwarsa" dan meminta pengguna untuk membuat pesanan baru.

3. WHEN status order adalah `expired`, THE Frontend SHALL menampilkan tanggal dan jam kedaluwarsa dalam format lokal WIB (UTC+7) yang mudah dibaca.

4. THE countdown timer SHALL menggunakan zona waktu WIB (UTC+7) untuk semua kalkulasi dan tampilan, dengan memastikan string tanggal dari API di-parse dengan suffix `Z` untuk menghindari ambiguitas timezone.

5. WHEN order baru dibuat dari daur ulang order lama, THE Frontend SHALL memulai countdown dari `created_at` yang baru (telah diperbarui oleh server), bukan dari `created_at` lama.

6. THE System SHALL mengeksekusi proses Auto-Expire pada order yang melewati batas 1 jam:
   - `SELECT orders WHERE status='pending' AND created_at < (NOW() - INTERVAL 1 HOUR)`
   - `UPDATE orders SET status='expired'`

7. IF pengguna membuka halaman status order yang sudah expired, THEN THE Frontend SHALL menampilkan tombol "Buat Pesanan Baru" yang mengulang alur checkout dari awal (bukan mendaur ulang order lama yang sudah expired lebih dari threshold tertentu).

---

### Requirement 6 — Halaman Riwayat Transaksi & Invoice

**User Story:** Sebagai siswa/orang tua, saya ingin melihat seluruh riwayat pembelian dan mendownload invoice resmi untuk keperluan administrasi.

#### Acceptance Criteria

1. WHEN pengguna yang sudah login mengakses `/transaksi`, THE Frontend SHALL menampilkan daftar seluruh order milik pengguna tersebut diurutkan dari yang terbaru.

2. THE halaman `/transaksi` SHALL menampilkan untuk setiap order: nomor referensi (Order Code atau Invoice Number), nama kursus, tanggal transaksi, jumlah bayar (format Rupiah), dan status (badge berwarna).

3. WHEN status order adalah `paid`, THE Frontend SHALL menampilkan `Invoice_Number` format `INV/{YEAR}/{SEQUENCE}` sebagai nomor referensi, beserta tombol "Cetak Invoice" dan "Unduh Invoice (PDF)".

4. WHEN status order adalah `pending`, `failed`, atau `expired`, THE Frontend SHALL menampilkan `Order_Code` format `TRX-...` sebagai nomor referensi. **Invoice Number TIDAK BOLEH ditampilkan** untuk transaksi yang belum lunas.

5. THE halaman `/transaksi` SHALL menyediakan filter dinamis berdasarkan status (Semua, Menunggu, Lunas, Gagal, Kedaluwarsa) yang bekerja di sisi klien tanpa reload halaman.

6. WHEN pengguna mengklik "Cetak Invoice" atau "Unduh Invoice", THE Frontend SHALL menghasilkan atau mengarahkan ke tampilan invoice resmi EduNusa yang berisi:
   - Header logo EduNusa + nama perusahaan
   - Invoice Number (`INV/{YEAR}/{SEQUENCE}`)
   - Tanggal terbit invoice (= `orders.payment_date`)
   - Data pembeli (nama, email)
   - Rincian item (nama kursus, harga satuan, jumlah)
   - Total bayar (sudah termasuk pajak jika ada)
   - Stempel/watermark "LUNAS"

7. THE halaman `/transaksi` SHALL menggunakan pendekatan **Client-Side Fetch** (bukan server-side blocking fetch) untuk memuat data transaksi, sehingga Time to First Byte (TTFB) halaman tetap rendah (< 200ms) dan data dimuat secara asinkron dengan Skeleton Loading.

8. WHEN data transaksi sedang dimuat, THE Frontend SHALL menampilkan Skeleton Loading yang me-mirror layout kartu transaksi (header, grid info 2 kolom, baris tabel, summary total) dengan animasi shimmer kiri-ke-kanan.

---

### Requirement 7 — Dashboard Terpadu Siswa (`/dashboard`)

**User Story:** Sebagai siswa/orang tua/profesional, saya ingin memiliki satu halaman dasbor yang menampilkan semua informasi relevan sesuai peran saya.

#### Acceptance Criteria

1. THE System SHALL menyediakan satu rute URL tunggal `/dashboard` untuk semua pengguna publik yang sudah login (role: `student`, `parent`, `general`). Rute bercabang seperti `/dashboard/murid`, `/dashboard/ortu`, atau `/dashboard/umum` TIDAK BOLEH muncul di address bar browser.

2. WHEN pengguna dengan role `student` mengakses `/dashboard`, THE Frontend SHALL menampilkan:
   - Daftar kursus aktif yang sudah di-enroll dengan progress bar capaian belajar
   - Materi terakhir yang diakses
   - Shortcut ke kuis yang belum diselesaikan
   - Widget EduBot chatbot

3. WHEN pengguna dengan role `parent` mengakses `/dashboard`, THE Frontend SHALL menampilkan:
   - Pantauan nilai kuis anak
   - Persentase kelulusan materi per kursus
   - Riwayat aktivitas belajar
   - Widget EduBot chatbot

4. WHEN pengguna dengan role `general` mengakses `/dashboard`, THE Frontend SHALL menampilkan:
   - Koleksi keahlian (kursus yang dimiliki)
   - Riwayat kelas terakhir
   - Widget EduBot chatbot

5. WHEN Middleware mendeteksi pengguna belum login mengakses `/dashboard`, THE Frontend SHALL mengarahkan ke `/masuk` dengan query parameter `redirect=/dashboard`.

6. WHEN Middleware mendeteksi pengguna sudah login mengakses `/masuk` atau `/daftar`, THE Frontend SHALL mengarahkan ke `/dashboard`.

---

### Requirement 8 — EduBot AI Chatbot

**User Story:** Sebagai siswa, saya ingin mendapat bantuan cepat dari asisten AI yang memahami platform EduNusa tanpa harus menunggu respons dari CS manusia.

#### Acceptance Criteria

1. WHEN pengguna membuka `/dashboard`, THE Frontend SHALL menampilkan widget EduBot di pojok kanan bawah layar dalam kondisi tertutup (tombol chat icon).

2. WHEN pengguna mengklik tombol chat icon EduBot, THE Frontend SHALL menampilkan panel chat yang menampilkan riwayat percakapan sesi aktif dan input field pesan.

3. WHEN pengguna mengirim pesan melalui EduBot, THE Frontend SHALL memanggil Server Action `chatWithEduBot(message, history)` yang bertindak sebagai proxy ke `Ollama_Service`.

4. WHEN Server Action meneruskan request ke `Ollama_Service`, THE Server_Action SHALL menggunakan endpoint `POST http://host.docker.internal:11434/api/chat` dengan:
   - `model`: `deepseek-r1:8b`
   - `messages`: array riwayat percakapan + pesan baru
   - `stream`: `false`
   - System prompt sebagai Customer Service tier-1 EduNusa

5. WHILE EduBot sedang memproses respons, THE Frontend SHALL menampilkan indikator loading (animasi "..." atau typing indicator) di dalam panel chat.

6. WHEN `Ollama_Service` mengembalikan respons, THE Frontend SHALL menampilkan pesan balasan EduBot di panel chat dan menyimpan ke riwayat percakapan sesi aktif.

7. IF `Ollama_Service` tidak dapat dijangkau atau timeout, THEN THE Frontend SHALL menampilkan pesan fallback "Maaf, EduBot sedang tidak tersedia. Silakan hubungi CS kami di support@edunusa.edu.id" tanpa crash.

8. THE System SHALL menghindari CORS error dengan menggunakan **Server Action sebagai proxy** — request dari browser client tidak boleh langsung menyentuh `Ollama_Service`.

---

### Requirement 9 — Webhook Integration (`e-learning-external`) ⏳ TO-DO

**User Story:** Sebagai sistem, saya membutuhkan endpoint yang menerima notifikasi pembayaran asinkron dari Midtrans agar status order dapat diperbarui secara real-time tanpa polling aktif.

> **Status**: **BELUM SELESAI** — Endpoint sudah dibuat di `Webhooks.php` (`e-learning-api`) dan forwarding ke `e-learning-internal`, tetapi implementasi di `e-learning-external` (sebagai penerima dari Midtrans langsung) dan validasi signature belum diimplementasikan.

#### Acceptance Criteria

1. THE External_Service SHALL menyediakan endpoint `POST /webhook/midtrans` yang dapat diakses publik dari server Midtrans.

2. WHEN `Midtrans_Gateway` mengirimkan HTTP POST ke endpoint webhook, THE External_Service SHALL:
   - Menerima payload JSON dari Midtrans
   - Memvalidasi `signature_key` menggunakan algoritma: `SHA512(order_id + status_code + gross_amount + server_key)`
   - Menolak request dengan HTTP `403 Forbidden` jika signature tidak valid

3. WHEN signature valid, THE External_Service SHALL meneruskan payload ke `Internal_Service` via `POST http://internal/api/webhooks/midtrans`.

4. WHEN `Internal_Service` menerima payload webhook, THE Internal_Service SHALL memproses status yang sama seperti Active Polling (settlement → paid + auto-enrollment, expire → expired, dsb.).

5. THE External_Service SHALL mengembalikan HTTP `200 OK` ke `Midtrans_Gateway` dalam waktu **kurang dari 15 detik** untuk menghindari retry Midtrans.

6. IF `Internal_Service` tidak dapat dijangkau, THEN THE External_Service SHALL mencatat payload ke log file dan mengembalikan HTTP `200 OK` ke Midtrans (agar tidak di-retry), lalu mencoba proses ulang secara periodik.

7. THE webhook endpoint SHALL menangani duplikasi notifikasi secara **idempotent** — jika order sudah `paid`, notifikasi `settlement` kedua tidak boleh menghasilkan enrollment ganda.

8. THE External_Service SHALL memvalidasi bahwa `order_id` dalam payload benar-benar ada di tabel `orders` sebelum memproses update.

---

### Requirement 10 — Admin Transaction Report ⏳ TO-DO

**User Story:** Sebagai Finance Admin, saya ingin melihat laporan rekap penjualan kursus secara real-time untuk memantau arus kas dan kinerja platform.

> **Status**: **BELUM SELESAI** — Modul ini belum diimplementasikan di `e-learning-admin`.

#### Acceptance Criteria

1. WHEN Finance Admin atau Superadmin mengakses modul Laporan Transaksi di `Admin_Dashboard`, THE Admin_Dashboard SHALL menampilkan summary cards yang berisi:
   - Total Penerimaan Kotor (bulan ini & total)
   - Jumlah Transaksi Sukses (bulan ini & total)
   - Jumlah Transaksi Pending
   - Jumlah Transaksi Gagal/Expired

2. THE Admin_Dashboard SHALL menyediakan tabel rincian transaksi dengan kolom: No. Invoice/Order Code, Nama Pembeli, Nama Kursus, Metode Pembayaran, Jumlah, Status, Tanggal.

3. THE tabel transaksi SHALL mendukung filter berdasarkan: rentang tanggal (date range picker), status transaksi, dan nama kursus.

4. THE tabel transaksi SHALL mendukung pencarian berdasarkan nama pembeli atau nomor invoice/order code.

5. WHEN Finance Admin mengklik baris transaksi, THE Admin_Dashboard SHALL menampilkan detail order yang berisi: data pembeli lengkap, rincian kursus, metode pembayaran Midtrans, dan riwayat status perubahan.

6. THE Admin_Dashboard SHALL menyediakan fitur **Export ke CSV/Excel** untuk keperluan rekonsiliasi akuntansi.

7. THE laporan SHALL menampilkan data rekapitulasi komisi platform:
   - Gross Revenue
   - Platform Fee (persentase yang ditetapkan)
   - Net to Mentor (gross - platform fee)

8. WHEN data laporan sedang dimuat, THE Admin_Dashboard SHALL menampilkan Skeleton Loading yang menggantikan posisi tabel dan summary cards.

9. THE akses ke modul Laporan Transaksi SHALL dibatasi hanya untuk role `finance_admin` dan `superadmin` menggunakan filter/middleware backoffice.

---

### Requirement 11 — Standardisasi Notifikasi (Anti-Native Alert)

**User Story:** Sebagai pengguna, saya ingin menerima notifikasi yang konsisten dan tidak mengganggu alur kerja saya.

#### Acceptance Criteria

1. THE Frontend (Next.js) SHALL menggunakan `react-hot-toast` untuk semua notifikasi pengguna. Penggunaan `window.alert()`, `window.confirm()`, atau `window.prompt()` **dilarang keras** di seluruh codebase frontend.

2. THE Admin_Dashboard (CodeIgniter) SHALL menggunakan `AgyToast` untuk semua notifikasi. Penggunaan native browser alert **dilarang keras**.

3. WHEN terjadi error pada proses checkout, THE Frontend SHALL menampilkan Toast error berwarna merah dengan pesan yang deskriptif (bukan kode error mentah) dan durasi tampil minimal 4 detik.

4. WHEN pembayaran berhasil diverifikasi, THE Frontend SHALL menampilkan Toast sukses berwarna hijau dengan pesan "Pembayaran berhasil! Selamat belajar 🎉" sebelum redirect ke `/dashboard`.

5. WHEN terjadi proses loading yang memerlukan waktu > 500ms, THE Frontend SHALL menampilkan Skeleton Loading (bukan spinner bulat polos) yang me-mirror layout konten yang sedang dimuat.

---

### Requirement 12 — Keamanan & Validasi Pembayaran

**User Story:** Sebagai platform, sistem harus memastikan tidak ada transaksi palsu atau manipulasi data yang dapat membahayakan integritas finansial.

#### Acceptance Criteria

1. THE API_Gateway SHALL memvalidasi JWT token pada setiap request yang membutuhkan autentikasi sebelum meneruskan ke `Internal_Service`.

2. THE Internal_Service SHALL memvalidasi bahwa `user_id` pada JWT token cocok dengan `user_id` pada data order sebelum memproses operasi apapun.

3. WHEN menerima webhook dari Midtrans, THE External_Service SHALL selalu memvalidasi `signature_key` sebelum memproses payload apapun. Request tanpa signature yang valid SHALL dikembalikan HTTP `403`.

4. THE System SHALL mencegah pembuatan enrollment ganda (double enrollment) untuk kombinasi `user_id` + `course_id` yang sama melalui constraint UNIQUE di database dan pengecekan idempotent di application layer.

5. THE `order_code` SHALL bersifat unik dan tidak dapat diprediksi (berisi timestamp epoch millisecond + 6 digit random) untuk mencegah enumerasi order.

6. THE Internal_Service SHALL tidak mempercayai status pembayaran dari klaim client-side — semua verifikasi status pembayaran HARUS dikonfirmasi langsung ke `Midtrans_Gateway` atau melalui webhook yang sudah divalidasi signature-nya.

---

## Ringkasan Status Implementasi

| # | Fitur | Status | Keterangan |
|---|---|---|---|
| R1 | Pembuatan Pesanan (Order Creation) | ✅ Selesai | Termasuk order recycle |
| R2 | Active Polling Engine | ✅ Selesai | Server Action `checkPaymentStatus` |
| R3 | Auto-Enrollment Engine | ✅ Selesai | Idempotent, UUID v4 |
| R4 | Lifetime Access Protection | ✅ Selesai | Frontend + Backend enforcement |
| R5 | Status Order & Countdown Timer | ✅ Selesai | WIB timezone, auto-expire |
| R6 | Riwayat Transaksi & Invoice | ✅ Selesai | Client-side fetch, skeleton loading |
| R7 | Dashboard Terpadu `/dashboard` | ✅ Selesai | Single route, conditional render |
| R8 | EduBot AI Chatbot | ✅ Selesai | Ollama proxy, deepseek-r1:8b |
| R9 | Webhook Integration | ⏳ PENDING | Perlu implementasi validasi signature |
| R10 | Admin Transaction Report | ⏳ PENDING | Belum dimulai |
| R11 | Anti-Native Alert | ✅ Selesai | react-hot-toast & AgyToast |
| R12 | Keamanan & Validasi | ✅ Sebagian | Webhook signature validation pending |
