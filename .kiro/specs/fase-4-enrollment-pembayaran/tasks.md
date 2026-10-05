# Implementation Plan: Fase 4 — Sistem Enrollment, Pembayaran Midtrans & Dashboard Siswa

## Overview

Dokumen ini adalah rencana implementasi terperinci untuk Fase 4 EduNusa. Sebagian besar task sudah **selesai (✅)**. Hanya **dua task utama yang masih pending (⏳)**:

1. **Webhook Integration** (`e-learning-external`) — Estimasi: 4–6 jam
2. **Admin Transaction Report** (`e-learning-admin`) — Estimasi: 8–12 jam

Task yang sudah selesai didokumentasikan sebagai **referensi arsitektur** dan panduan untuk maintenance/debugging di masa mendatang.

---

## Legenda Status

| Simbol | Arti |
|---|---|
| `[x]` | ✅ Sudah selesai dan berfungsi |
| `[ ]` | ⏳ Belum selesai — perlu dikerjakan |
| `[ ]*` | ⏳ Opsional / dapat di-skip untuk MVP |

---

## Tasks

### 1. Fondasi Database & Skema Transaksi ✅

- [x] 1.1 Buat / verifikasi tabel `orders` dengan kolom lengkap
  - Kolom: `id` (UUID v4), `order_code` (TRX-...), `invoice_number` (nullable), `user_id`, `course_id`, `amount`, `status` (ENUM), `payment_method`, `snap_redirect_url`, `payment_date`, `created_at`, `updated_at`
  - Index: `(user_id, course_id)`, `(status, created_at)`
  - _Requirements: R1, R5_

- [x] 1.2 Buat / verifikasi tabel `enrollments` dengan UNIQUE constraint
  - Kolom: `id` (UUID v4), `user_id`, `course_id`, `order_id`, `enrolled_at`
  - UNIQUE KEY `uq_user_course (user_id, course_id)` — enforce lifetime access
  - _Requirements: R3, R4, R12.4_

- [x] 1.3 Buat / verifikasi tabel `mentor_earnings`
  - Kolom: `id`, `order_id` (UNIQUE), `mentor_id`, `course_id`, `gross_amount`, `platform_fee`, `net_amount`, `status` (ENUM)
  - _Requirements: R3.3_

---

### 2. Backend Internal Service — Order Management ✅

- [x] 2.1 Implementasi endpoint `POST /api/transactions` (create/recycle order)
  - Logika: cek existing order → recycle (UPDATE created_at) atau INSERT baru
  - Generate `order_code`: `TRX-{epoch_ms}-{6_random_uppercase}`
  - Request token ke Midtrans Snap API
  - _Requirements: R1.4, R1.5, R1.6, R1.7_

- [x] 2.2 Implementasi endpoint `GET /api/transactions/status/{order_code}`
  - Logika short-circuit: jika DB sudah terminal → return langsung
  - Jika DB masih `pending` → query Midtrans Status API
  - Proses settlement/capture: UPDATE orders + Auto-Enrollment chain
  - _Requirements: R2.3, R2.4, R2.5, R2.6, R2.7_

- [x] 2.3 Implementasi Auto-Enrollment chain (idempotent)
  - `BEGIN TRANSACTION` → `SELECT ... FOR UPDATE` → cek existing → INSERT atau SKIP
  - Insert `mentor_earnings` setelah enrollment berhasil
  - _Requirements: R3.1, R3.2, R3.3_

- [x] 2.4 Implementasi endpoint `GET /api/transactions/user/{user_id}`
  - JOIN dengan tabel `courses` untuk data kursus
  - Sertakan `invoice_number` hanya jika `status = 'paid'`
  - _Requirements: R6.1, R6.2_

- [x] 2.5 Implementasi endpoint `GET /api/transactions/enrollments/{user_id}`
  - Return daftar `course_id` yang sudah di-enroll
  - _Requirements: R4.1_

- [x] 2.6 Implementasi endpoint `POST /api/transactions/auto-expire`
  - Query: `UPDATE orders SET status='expired' WHERE status='pending' AND created_at < (NOW() - INTERVAL 1 HOUR)`
  - _Requirements: R5.6_

- [x] 2.7 Implementasi logika generate `invoice_number`
  - Format: `INV/{YEAR}/{LPAD(COUNT_PAID_THIS_YEAR+1, 6, '0')}`
  - Hanya dijalankan saat UPDATE status → `paid`
  - _Requirements: R6.3, R6.4_

---

### 3. Backend API Gateway — Routing & JWT ✅

- [x] 3.1 Implementasi route `POST /api/v1/transactions/create` di `e-learning-api`
  - Validasi JWT Bearer token → extract `user_id`
  - Forward ke `Internal_Service`
  - _Requirements: R1.3, R12.1_

- [x] 3.2 Implementasi route `GET /api/v1/transactions/status/{order_code}`
  - Public endpoint (tidak perlu JWT)
  - Forward ke `Internal_Service`
  - _Requirements: R2.1_

- [x] 3.3 Implementasi route `GET /api/v1/transactions/user` (riwayat order)
  - Validasi JWT → extract `user_id` → forward ke `Internal_Service`
  - _Requirements: R6.1_

- [x] 3.4 Implementasi route `GET /api/v1/transactions/enrollments/{user_id}`
  - Forward ke `Internal_Service`
  - _Requirements: R4.1_

- [x] 3.5 Implementasi route `POST /api/v1/transactions/auto-expire`
  - Internal-only route (dipanggil dari cron atau manual)
  - _Requirements: R5.6_

- [x] 3.6 Implementasi route `POST /api/v1/webhooks/midtrans` (forwarding webhook)
  - Forward payload JSON ke `Internal_Service`
  - _Requirements: R9.3_

---

### 4. Frontend MVVM — Entity, Repository, UseCase ✅

- [x] 4.1 Definisi entity TypeScript: `Order`, `Enrollment`, `ChatMessage`
  - File: `src/core/entities/order.ts`, `src/core/entities/enrollment.ts`, `src/core/entities/chat.ts`
  - Tipe `OrderStatus`: `'pending' | 'paid' | 'failed' | 'expired' | 'refunded'`
  - _Requirements: Design — Entity Layer_

- [x] 4.2 Implementasi Repository interfaces & implementations
  - `ITransactionRepository` → `TransactionRepository` (fetch ke API Gateway)
  - `IEnrollmentRepository` → `EnrollmentRepository`
  - `IChatRepository` → `ChatRepository` (Server Action proxy)
  - _Requirements: Design — Repository Layer_

- [x] 4.3 Implementasi Use Cases
  - `CreateOrderUseCase`, `CheckPaymentStatusUseCase`, `GetUserEnrollmentsUseCase`, `ChatWithEduBotUseCase`
  - _Requirements: Design — UseCase Layer_

- [x] 4.4 Implementasi ViewModel hooks
  - `useCheckoutViewModel`, `useTransactionStatusViewModel`, `useTransactionHistoryViewModel`, `useEduBotViewModel`
  - _Requirements: Design — ViewModel Layer_

---

### 5. Frontend — Checkout & Pembayaran ✅

- [x] 5.1 Halaman Checkout (`/checkout?course_id=...` atau `/checkout/[slug]`)
  - Tampilkan ringkasan kursus (thumbnail, judul, instruktur, harga)
  - Tombol "Bayar Sekarang" → trigger `createOrderAction(courseId)`
  - Guard: jika belum login → redirect `/masuk`
  - Guard: jika sudah enroll → tombol "Lanjutkan Belajar"
  - _Requirements: R1.1, R1.2, R1.8_

- [x] 5.2 Server Action `createOrderAction`
  - File: `src/app/actions/transactionActions.ts`
  - Kirim `POST /api/v1/transactions/create` dengan Bearer token dari cookie
  - Return `{snapUrl}` → frontend redirect ke `snapUrl` (window.location.href)
  - _Requirements: R1.3, R1.8_

- [x] 5.3 Halaman Status Pembayaran (`/transaction/status`)
  - Query param: `?order_code=TRX-...`
  - Tampilkan: status badge, nama kursus, jumlah bayar, instruksi pembayaran
  - Jika `pending`: tampilkan countdown timer HH:MM:SS + instruksi VA/QRIS
  - Jika `expired`: tampilkan tanggal kedaluwarsa + tombol "Buat Pesanan Baru"
  - Jika `paid`: tampilkan konfirmasi sukses + tombol "Mulai Belajar"
  - _Requirements: R2.1, R5.1, R5.2, R5.3_

- [x] 5.4 Implementasi countdown timer (WIB timezone)
  - Hitung dari `order.createdAt + 3600000 ms` ke `Date.now()`
  - Parse date string dengan suffix `Z` untuk menghindari bug timezone
  - Format output: `HH:MM:SS`, clamp ke `00:00:00` saat mencapai nol
  - _Requirements: R5.1, R5.4_

- [x] 5.5 Polling loop `checkPaymentStatusAction`
  - Interval: setiap N detik (misal 5 detik)
  - Stop otomatis jika status terminal (paid/failed/expired)
  - Redirect ke `/dashboard` setelah 3 detik jika `paid`
  - _Requirements: R2.2, R2.8, R2.9_

---

### 6. Frontend — Lifetime Access & Enrollment Check ✅

- [x] 6.1 Server Action `getUserEnrollmentsAction`
  - Fetch dari `GET /api/v1/transactions/enrollments/{user_id}`
  - Return array of `course_id`
  - _Requirements: R4.1_

- [x] 6.2 Integrasi enrollment check di halaman Katalog Kursus (`/kursus`)
  - Cek setiap kursus di katalog: jika `enrolledCourseIds.includes(course.id)` → tampilkan "Lanjutkan Belajar"
  - _Requirements: R4.2, R4.3_

- [x] 6.3 Integrasi enrollment check di halaman Detail Kursus (`/kursus/[slug]`)
  - Tombol CTA adaptif: "Beli Sekarang" vs "Lanjutkan Belajar"
  - Badge "Sudah Dimiliki" pada header kursus jika sudah enrolled
  - _Requirements: R4.2, R4.4_

- [x] 6.4 Standardisasi rute Dashboard tunggal `/dashboard`
  - Hapus semua rute bercabang `/dashboard/murid`, `/dashboard/ortu`, `/dashboard/umum`
  - Server Component `/app/dashboard/page.tsx` → conditional render berdasarkan `user.role`
  - _Requirements: R7.1, R7.2, R7.3, R7.4_

---

### 7. Frontend — Riwayat Transaksi & Invoice ✅

- [x] 7.1 Halaman Riwayat Transaksi (`/transaksi`)
  - Client-side fetch (bukan server-side blocking) → skeletons → data
  - Tampilkan daftar order: nomor referensi, kursus, tanggal, jumlah, status badge
  - Filter dinamis (Semua/Menunggu/Lunas/Gagal/Kedaluwarsa) tanpa reload
  - _Requirements: R6.1, R6.2, R6.5, R6.7_

- [x] 7.2 Logic tampilan nomor referensi
  - `status === 'paid'` → tampilkan `invoice_number` (INV/...)
  - `status !== 'paid'` → tampilkan `order_code` (TRX-...)
  - DILARANG tampilkan INV untuk order non-paid
  - _Requirements: R6.3, R6.4_

- [x] 7.3 Halaman/komponen Invoice resmi
  - Header: Logo EduNusa + nama perusahaan + alamat
  - Invoice Number, tanggal (format WIB), data pembeli
  - Tabel rincian item (kursus, harga, qty=1, subtotal)
  - Total bayar + stempel/watermark "LUNAS"
  - Tombol "Cetak" (window.print()) + "Unduh PDF"
  - _Requirements: R6.6_

- [x] 7.4 Skeleton Loading untuk halaman transaksi
  - Mirror layout kartu transaksi: header gradient, grid 2 kolom, baris tabel
  - Animasi shimmer kiri-ke-kanan (bukan spinner)
  - _Requirements: R6.8, R11.5_

---

### 8. Frontend — EduBot AI Chatbot ✅

- [x] 8.1 Komponen `EduBotWidget` (Chat Widget UI)
  - File: `src/components/edubot/EduBotWidget.tsx`
  - Tombol chat icon di pojok kanan bawah
  - Panel chat dengan riwayat percakapan sesi aktif
  - Input field + tombol kirim + typing indicator
  - _Requirements: R8.1, R8.2_

- [x] 8.2 Server Action `chatWithEduBotAction`
  - File: `src/app/actions/chatActions.ts`
  - Proxy ke `POST http://host.docker.internal:11434/api/chat`
  - Body: `{model: "deepseek-r1:8b", messages: [...history, userMsg], stream: false}`
  - System prompt sebagai CS tier-1 EduNusa
  - _Requirements: R8.3, R8.4, R8.8_

- [x] 8.3 Error handling EduBot (Ollama unavailable)
  - Catch connection error / timeout
  - Tampilkan pesan fallback: "EduBot sedang tidak tersedia. Hubungi support@edunusa.edu.id"
  - _Requirements: R8.7_

- [x] 8.4 Integrasi EduBotWidget di halaman `/dashboard`
  - Import dan render `<EduBotWidget />` di layout dashboard
  - _Requirements: R8.1_

---

### 9. Optimasi Performa ✅

- [x] 9.1 Migrasi ke Client-Side Fetch di `/transaksi`
  - Ubah server-side fetch blocking menjadi `useEffect` + client fetch
  - Tambah Skeleton Loading state
  - Tujuan: TTFB < 200ms, data dimuat asinkron
  - _Requirements: R6.7, R6.8_

- [x] 9.2 Migrasi ke Snap Redirect (VT-Web)
  - Ganti `snap.pay()` (popup) dengan `window.location.href = snapUrl` (full-screen redirect)
  - Mengatasi CSP blocking di browser modern
  - _Requirements: R1.8_

- [x] 9.3 Fix bug timezone WIB
  - Tambah suffix `Z` pada semua Date parsing dari API: `new Date(dateString + 'Z')`
  - Pastikan countdown timer dan tampilan tanggal menggunakan WIB (UTC+7)
  - _Requirements: R5.4_

---

### 10. Webhook Integration (`e-learning-external`) ⏳ **PERLU DIKERJAKAN**

> **Estimasi waktu**: 4–6 jam
> **Dependensi**: Requires `e-learning-internal` endpoint `/api/webhooks/midtrans` sudah berfungsi (✅ sudah ada di task 3.6)

- [ ] 10.1 Setup route webhook di `e-learning-external`
  - File: `app/Controllers/Webhooks/Midtrans.php` (baru)
  - Route: `POST /webhook/midtrans`
  - Pastikan tidak ada JWT filter pada route ini (Midtrans tidak mengirim JWT)
  - Tambahkan whitelist IP Midtrans jika diperlukan
  - _Requirements: R9.1_

- [ ] 10.2 Implementasi validasi signature_key Midtrans
  - Algoritma: `SHA512(order_id + status_code + gross_amount + SERVER_KEY)`
  - `SERVER_KEY`: ambil dari environment variable `MIDTRANS_SERVER_KEY`
  - Return `HTTP 403` jika signature tidak cocok
  - Catat semua upaya signature invalid ke log
  - _Requirements: R9.2, R12.3_

- [ ] 10.3 Implementasi forwarding payload ke `e-learning-internal`
  - Jika signature valid → `POST http://internal/api/webhooks/midtrans` dengan payload JSON
  - Gunakan cURL CI4 dengan `http_errors: false`
  - _Requirements: R9.3_

- [ ] 10.4 Implementasi error handling & idempotency
  - Jika `e-learning-internal` tidak bisa dijangkau → log payload ke `writable/logs/webhook_queue.log`
  - SELALU return `HTTP 200 OK` ke Midtrans (mencegah retry storm)
  - Batasi waktu respons < 15 detik (set cURL timeout)
  - _Requirements: R9.5, R9.6_

- [ ] 10.5 Verifikasi `order_id` ada di database sebelum proses
  - Call `GET http://internal/api/transactions/status/{order_id}` untuk verifikasi
  - Jika `order_id` tidak ditemukan → log sebagai anomali, return HTTP 200 (jangan crash)
  - _Requirements: R9.8_

- [ ]* 10.6 Unit test validasi signature
  - Test valid signature → proses lanjut
  - Test invalid signature → return 403
  - Test signature dengan berbagai kombinasi input
  - **Property 6: Webhook Signature Rejection**
  - _Requirements: R9.2_

- [ ] 10.7 Daftarkan URL webhook di Midtrans Dashboard
  - URL: `https://{domain-staging}/webhook/midtrans`
  - Aktifkan notifikasi: `settlement`, `capture`, `expire`, `deny`, `cancel`
  - _(Langkah manual, bukan coding — panduan ada di README)_

- [ ] 10.8 Checkpoint webhook: Test end-to-end
  - Simulasikan payment di Midtrans Sandbox
  - Verifikasi webhook diterima, signature valid, DB terupdate, enrollment terbuat
  - Verifikasi `e-learning-internal` endpoint `/api/webhooks/midtrans` memproses dengan benar

---

### 11. Admin Transaction Report (`e-learning-admin`) ⏳ **PERLU DIKERJAKAN**

> **Estimasi waktu**: 8–12 jam
> **Dependensi**: Data dari `e-learning-internal` sudah tersedia

- [ ] 11.1 Buat endpoint `GET /api/admin/transactions` di `e-learning-internal`
  - Support filter: `status`, `date_from`, `date_to`, `course_id`, `search` (nama pembeli / invoice)
  - JOIN: `orders → users, courses, mentor_earnings`
  - Return: pagination + data array + summary aggregates
  - _Requirements: R10.2, R10.3, R10.4_

- [ ] 11.2 Buat endpoint `GET /api/admin/transactions/summary` di `e-learning-internal`
  - Aggregasi: total_gross (bulan ini), total_gross (all-time), count_paid (bulan ini), count_pending, count_failed_expired
  - Platform fee summary, net to mentor
  - _Requirements: R10.1, R10.7_

- [ ] 11.3 Tambahkan routing dan filter akses di `e-learning-admin`
  - Route: `/admin/finance/transactions`
  - Filter akses: hanya `superadmin` dan `finance_admin` (role check via session/filter)
  - _Requirements: R10.9_

- [ ] 11.4 Buat halaman Laporan Transaksi di `e-learning-admin`
  - File: `app/Views/admin/finance/transactions.php`
  - Layout sesuai standar B2B Enterprise (Unified Canvas, padding 24px)
  - _Requirements: R10.2_

- [ ] 11.5 Summary Cards (4 kartu KPI)
  - Gross Revenue bulan ini & total
  - Jumlah Transaksi Sukses bulan ini & total
  - Jumlah Transaksi Pending
  - Jumlah Transaksi Gagal/Expired
  - Skeleton Loading saat data dimuat
  - _Requirements: R10.1, R10.8_

- [ ] 11.6 Implementasi tabel transaksi dengan DataTables / server-side pagination
  - Kolom: No. Invoice/Order Code, Pembeli, Kursus, Metode, Jumlah (Rp), Status, Tanggal
  - Status badge berwarna sesuai mapping (paid=hijau, pending=kuning, expired=abu)
  - _Requirements: R10.2_

- [ ] 11.7 Implementasi filter dan pencarian
  - Date range picker (dari - sampai)
  - Dropdown filter status
  - Input pencarian (nama pembeli / invoice number)
  - Semua filter bekerja secara real-time atau on-submit
  - _Requirements: R10.3, R10.4_

- [ ] 11.8 Halaman/modal detail order
  - Klik baris tabel → tampilkan detail: data pembeli lengkap, kursus, metode Midtrans, riwayat status
  - _Requirements: R10.5_

- [ ] 11.9 Implementasi Export CSV/Excel
  - Tombol "Export CSV" → generate file CSV dari filtered data
  - Kolom: semua kolom tabel + kolom tambahan untuk rekonsiliasi
  - _Requirements: R10.6_

- [ ] 11.10 Skeleton Loading untuk halaman laporan
  - Skeleton cards (4 kartu KPI)
  - Skeleton tabel (header + 5 baris placeholder)
  - Animasi shimmer, bukan spinner
  - _Requirements: R10.8, R11.5_

- [ ] 11.11 Checkpoint Admin Report: Verifikasi akses dan data
  - Login sebagai `finance_admin` → harus bisa akses
  - Login sebagai `mentor` → harus di-redirect/403
  - Data summary sesuai dengan data di database
  - Export CSV berhasil diunduh dan datanya akurat

---

### 12. Property-Based Tests ⏳ **OPSIONAL — Direkomendasikan**

> **Library**: `fast-check` untuk TypeScript (frontend), `eris` untuk PHP (backend)

- [ ]* 12.1 Property test: Enrollment Uniqueness Invariant
  - Setup: generate random user_id + course_id + N enrollment attempts
  - Assert: `enrollments.filter(e => e.userId===uid && e.courseId===cid).length <= 1`
  - **Property 1: Lifetime Access — Enrollment Uniqueness Invariant**
  - _Requirements: R3.2, R4.5, R12.4_

- [ ]* 12.2 Property test: Invoice Exclusivity
  - Setup: generate orders dengan random status
  - Assert: `status==='paid' ↔ hasInvoiceNumber` (iff condition)
  - **Property 2: Invoice Exclusivity — Paid-Only Invoice Numbers**
  - _Requirements: R6.3, R6.4_

- [ ]* 12.3 Property test: Auto-Enrollment Idempotency
  - Setup: panggil auto-enrollment 2–10 kali untuk user+course yang sama
  - Assert: jumlah record enrollment tetap 1, tidak ada error
  - **Property 3: Auto-Enrollment Idempotency**
  - _Requirements: R3.2, R9.7_

- [ ]* 12.4 Property test: Countdown Timer Correctness
  - Setup: generate random `created_at` timestamps (past & future)
  - Assert: `remaining = max(0, expiresAt - now)`, selalu non-negatif, format HH:MM:SS valid
  - **Property 4: Countdown Timer Correctness**
  - _Requirements: R5.1, R5.4_

- [ ]* 12.5 Property test: Polling Short-Circuit
  - Setup: mock DB returns terminal status (paid/failed/expired)
  - Assert: Midtrans API tidak dipanggil saat status terminal
  - **Property 5: Polling Short-Circuit for Terminal Orders**
  - _Requirements: R2.7_

- [ ]* 12.6 Property test: Button State Reflects Enrollment
  - Setup: generate random user + list enrolled course_ids + random course
  - Assert: button state `===` isEnrolled ? "Lanjutkan Belajar" : "Beli Sekarang"
  - **Property 7: Button State Reflects Enrollment**
  - _Requirements: R1.2, R4.2_

---

## Catatan Implementasi

- Task bernomor desimal (1.1, 2.3, dst.) adalah leaf tasks yang dapat dieksekusi.
- Task bertanda `*` adalah **opsional** dan dapat dilewati untuk pengiriman yang lebih cepat.
- Task 10.x (Webhook) adalah **prioritas tinggi** untuk environment staging/produksi — Active Polling hanya solusi untuk development lokal.
- Task 11.x (Admin Report) adalah **kebutuhan Finance** sebelum go-live.
- Semua perubahan database WAJIB melalui `e-learning-internal` — tidak boleh ada service lain yang direct query ke MySQL.
- Semua notifikasi di Next.js WAJIB menggunakan `react-hot-toast`. Di admin WAJIB menggunakan `AgyToast`.
- Tanggal dari API WAJIB di-parse dengan suffix `Z`: `new Date(apiDateString + 'Z')`.
- Referensi ke spesifikasi lengkap: `requirements.md` dan `design.md` di direktori ini.

---

## Task Dependency Graph

```json
{
  "waves": [
    {
      "id": 0,
      "tasks": ["1.1", "1.2", "1.3"],
      "note": "Fondasi skema database — harus selesai sebelum backend logic"
    },
    {
      "id": 1,
      "tasks": ["2.1", "2.7", "4.1"],
      "note": "Internal Service order creation, invoice logic, entity definitions"
    },
    {
      "id": 2,
      "tasks": ["2.2", "2.3", "2.4", "2.5", "2.6", "4.2"],
      "note": "Internal Service status/enrollment/history endpoints + Repository interfaces"
    },
    {
      "id": 3,
      "tasks": ["3.1", "3.2", "3.3", "3.4", "3.5", "3.6", "4.3"],
      "note": "API Gateway routes + Use Case implementations"
    },
    {
      "id": 4,
      "tasks": ["4.4", "5.1", "5.2", "6.1"],
      "note": "ViewModel hooks + Checkout page + Enrollments Server Action"
    },
    {
      "id": 5,
      "tasks": ["5.3", "5.4", "5.5", "6.2", "6.3", "8.2"],
      "note": "Status page + countdown + polling + catalog/detail integration + EduBot action"
    },
    {
      "id": 6,
      "tasks": ["6.4", "7.1", "7.2", "7.3", "7.4", "8.1", "8.3"],
      "note": "Dashboard unification + Transaction history + Invoice UI + EduBot widget"
    },
    {
      "id": 7,
      "tasks": ["8.4", "9.1", "9.2", "9.3"],
      "note": "EduBot integration + Performance optimizations"
    },
    {
      "id": 8,
      "tasks": ["10.1", "10.2", "11.1", "11.2"],
      "note": "⏳ Webhook setup + signature validation + Admin report endpoints — parallel start"
    },
    {
      "id": 9,
      "tasks": ["10.3", "10.4", "10.5", "11.3", "11.4", "11.5"],
      "note": "⏳ Webhook forwarding + error handling + Admin report layout + KPI cards"
    },
    {
      "id": 10,
      "tasks": ["10.6", "11.6", "11.7", "11.8"],
      "note": "⏳ Webhook unit test + Admin report table + filters + detail modal"
    },
    {
      "id": 11,
      "tasks": ["10.7", "10.8", "11.9", "11.10", "11.11"],
      "note": "⏳ Webhook registration (manual) + checkpoints + CSV export + skeleton"
    },
    {
      "id": 12,
      "tasks": ["12.1", "12.2", "12.3", "12.4", "12.5", "12.6"],
      "note": "⏳ Property-based tests — opsional, dapat dijalankan paralel setelah wave 7"
    }
  ]
}
```
