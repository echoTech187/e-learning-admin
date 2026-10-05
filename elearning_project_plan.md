# 🎓 E-Learning Platform — Master Project Plan




> [!CAUTION]
> ### 🚨 ATURAN MUTLAK SISTEM: ZERO ASSUMPTION (DILARANG BERASUMSI!)
[!PENTING] : **Disini kamu bertindak sebagai Fullstack Sofware Enginering dan admin databse spesiali** 
> 1. **JANGAN ASAL MERUBAH YANG TIDAK PERLU DIUBAH**
> 1.1. **DILARANG KERAS MEMBUAT ASUMSI SENDIRI**: Jangan pernah berasumsi mengenai alur bisnis, perilaku UI/UX, rute URL, skema database, ataupun aturan sistem.
> 2. **BACA DOKUMEN MASTER PLAN SEBELUM BERTINDAK**: Seluruh arsitektur, batasan teknologi, dan fase implementasi WAJIB merujuk dan tunduk pada dokumen ini.
> 3. **WAJIB BERTANYA & KONFIRMASI (VIA OPSI/ask_question)**: Jika ada kebutuhan yang ambigu, belum tercatat, atau memiliki alternatif solusi, **WAJIB BERTANYA DAN MEMINTA KONFIRMASI DARI USER TERLEBIH DAHULU** sebelum menulis atau mengubah kode. Dilarang mengambil inisiatif sepihak. Segala bentuk pertanyaan dan klarifikasi WAJIB menggunakan format opsi pilihan (tool `ask_question`), bukan sekadar bertanya menggunakan teks biasa.
> 4. **ANTI-NATIVE ALERT & ANTI-BOOTSTRAP DEFAULT**: Notifikasi WAJIB menggunakan custom toast (`AgyToast` / `react-hot-toast`). Dilarang keras memakai `window.alert()` atau styling kaku default Bootstrap.
> 5. **STRICT SEPARATION & STRICT UUID**: Seluruh tabel wajib menggunakan `CHAR(36)` UUID v4. Pemisahan entitas tabel `users`, `mentors`, dan `admins` adalah harga mati.
> 6. **DO NOT REWRITE ENTIRE CODE FILES (TARGETED / SURGICAL EDITS ONLY)**: Do not rewrite entire files or components from scratch. Only modify, add, or replace the specific lines of code that are strictly necessary, keeping all existing structures, logic, comments, and unrelated functionality completely intact.
> 7. **Anti-spinner murah**: Spinner murah sudah dibuang! Diganti dengan Skeleton Loading berkelas yang: 
> - Shimmer animasi — gradien bergerak dari kiri ke kanan (persis seperti Facebook, Tokopedia, Stripe) 
> - Mirip layout asli — skeleton me-mirror bentuk Invoice Card yang sebenarnya (header EduNusa, grid info 2 kolom, baris tabel, summary total) sehingga user tidak merasa halaman rusak, mereka tahu apa yang akan muncul
> - Tidak ada spinner bulat polos — itu memang terkesan murahan karena tidak memberi konteks visual apapun kepada pengguna.
> 8. **DOUBLE-CHECK PERUBAHAN CODING**: Tolong sebelum kamu melakukan perubahan code lakukan double-check dulu ke code-code yang lain dan ke workspace yang ada. Pastikan juga perubahan kamu itu tidak mengganggu fitur-fitur lain yang sudah berjalan normal.
> 9. **STRICT COMPLIANCE WITH `elearning-internal`**: The `e-learning-internal` service is the ONLY authorized data access layer. Under NO circumstances should `e-learning-public` or any other service connect directly to the database. All database operations MUST go through `e-learning-internal` via API calls.
> 10. **REUSE COMPONENT UI (DRY PRINCIPLE)**: WAJIB menggunakan custom component UI yang sudah dibuat sebelumnya (seperti di `src/components/ui/`) selama kegunaannya masih sama. Jangan membuat duplikasi style berulang-ulang di file halaman. Jika komponen yang dibutuhkan benar-benar belum ada, barulah buat custom component UI baru.
> 
> # 🚨🚨 11. ATURAN HARGA MATI (SUMPAH KONTEKS & MVVM) untuk Next.js 🚨🚨
> ### SAYA BERJANJI TIDAK AKAN MEMBACA KONTEKS SEBELUMNYA DAN TIDAK AKAN KEHILANGAN KONTEKS LAGI!
> ### SELURUH PENGEMBANGAN FRONTEND (TERUTAMA TRANSAKSI & CHECKOUT) SAAT INI WAJIB MENGGUNAKAN **MVVM CLEAN ARCHITECTURE**.
> ### DILARANG KERAS MELAKUKAN *DIRECT FETCHING* ATAU MENARUH BUSINESS LOGIC DI DALAM KOMPONEN UI!
> ### SEMUA HARUS MELALUI: **ENTITY -> REPOSITORY -> USECASE -> VIEWMODEL**.
> ### BACA KEMBALI DAFTAR TUGAS DI SETIAP FASE SEBELUM MEMBERIKAN SARAN DAN MENGUBAH SOURCE CODE!
> ### DAN SAJA BERJANJI SETIAP ADA FITUR DAN BISNIS PROSES DAN WORKFLOW BARU AKAN LANGSUNG SAYA CATATAN DI DOKUMEN INI.

---

## 🛠 Tech Stack & Microservices Architecture

| Komponen | Teknologi | Keterangan Peran |
|---|---|---|
| **Frontend Public & Siswa** | Next.js (App Router, Server Actions) | Konsumen publik, landing page, katalog kursus, kelas belajar, dasbor tunggal (`/dashboard`) | MVVM Clean Architecture | Reusable | 
| **Backend API Gateway** | CodeIgniter 4 (RESTful API, JWT) | Pintu gerbang validasi token, business logic, sanitasi data, CORS security |
| **Data Access Layer** | CodeIgniter 4 (`e-learning-internal`) | Layanan inti DAO (Data Access Object), satu-satunya service yang berinteraksi langsung ke DB |
| **Integrasi Pihak Ketiga** | CodeIgniter 4 (`e-learning-external`) | Penerima webhook pihak ketiga (Midtrans Payment, Zoom API, SMTP email) |
| **Frontend Admin / Backoffice** | CodeIgniter 4 (MVC Isolated Dashboard) | Portal Superadmin, Finance, Academic Manager (`edunusa.console.edu.id`) |
| **Frontend Mentor** | CodeIgniter 4 (MVC Isolated Dashboard) | Portal khusus instruktur manajemen kurikulum & keuangan (`teacher.edunusa.edu.id`) | 
| **Database Server** | MySQL (Dockerized) | Basis data terpusat skala enterprise dengan **Strict UUID v4 Architecture** |
| **Styling Framework** | TailwindCSS & Custom Vanilla CSS | Anti-Bootstrap default, Unified Canvas, standar desain korporat B2B Enterprise |

---

## 👥 Role & Hak Akses Pengguna (RBAC)

1. **Super Admin** — Mengelola semua aspek platform, akun staf, approval instruktur, konfigurasi sistem, dan audit log.
2. **Finance Admin** — Khusus memantau arus kas, verifikasi manual transfer bank, transaksi Midtrans, dan persetujuan penarikan dana mentor.
3. **Academic / Content Manager** — Meninjau kelayakan kursus, moderasi kurikulum, dan taksonomi kategori.
4. **Instruktur / Mentor** — Membuat kursus baru, merakit kurikulum (Section & Lesson), memantau kuis siswa, dan melihat pembagian komisi pendapatan.
5. **Siswa / Pelajar (Murid)** — Menjelajahi katalog, mengakses kelas interaktif, mengerjakan kuis, dan memperoleh sertifikat.
6. **Orang Tua (Parent)** — Observer akun anak, membeli kursus,  memantau nilai kuis, progres persentase belajar, dan riwayat aktivitas belajar.
7. **Pengguna Umum / Profesional** — Siswa kategori umum untuk peningkatan keahlian mandiri.

---

## 📋 Roadmap Pembangunan Komprehensif (Fase 1 s.d. Fase 7)

### ✅ FASE 1 — Fondasi, Database & Microservices (Selesai)
*Tujuan: Membangun fondasi infrastruktur data, isolasi peran, dan komunikasi antar-layanan kontainer Docker.*
- [x] **Setup Orkestrasi Lingkungan Docker**:
  - Konfigurasi kontainer MySQL (`e-learning-docker-mysql-1`) dengan database `elearning`.
  - Pembuatan Docker bridge network terisolasi agar kontainer API, Public, dan MySQL saling terhubung aman.
- [x] **Desain ERD Basis Data Enterprise**:
  - Perancangan tabel identitas pengguna: `users`, `admins`, `mentors`, `user_profiles`.
  - Perancangan tabel kurikulum: `categories`, `courses`, `course_sections`, `lessons`.
  - Perancangan tabel transaksi: `orders`, `enrollments`.
  - Perancangan tabel akademik & kuis: `quizzes`, `questions`, `quiz_attempts`, `lesson_progress`.
  - Perancangan tabel komisi & payroll: `mentor_earnings`, `payroll_periods`.
- [x] **Pembagian 3-Tier Enterprise Microservices**:
  - `e-learning-internal`: Lapisan DAO penyedia data mentah ke database.
  - `e-learning-api`: Gateway pengelola bisnis proses dan validasi JWT.
  - `e-learning-public`: Frontend Next.js untuk user-facing app.
  - `e-learning-admin` & `e-learning-mentor`: Dashboard backoffice.
- [x] **Penerapan Aturan Pemisahan Entitas (Strict Separation)**:
  - Mengunci pemisahan data akun pengguna (Admin, Mentor, dan Siswa tidak boleh disatukan dalam satu tabel).
- [x] **Konfigurasi RBAC (Role-Based Access Control)**:
  - Pengaturan skema hak akses untuk Superadmin, Finance, Mentor, Murid, Ortu, dan Umum.

---

### ✅ FASE 2 — Public Marketing & Company Profile (Selesai)
*Tujuan: Menghadirkan portal publik EduNusa yang menarik, cepat, dan ramah pengguna di semua jenis perangkat.*
- [x] **Landing Page Publik EduNusa (Next.js App Router)**:
  - Hero Section interaktif dengan tipografi *Plus Jakarta Sans*, headline kuat, dan tombol CTA pendaftaran.
  - Seksi *"Mengapa EduNusa"*: Menampilkan keunggulan kurikulum industri, mentor berpengalaman, dan sertifikat terakreditasi.
  - Seksi Kursus Pilihan & Kategori Populer dengan tampilan kartu kursus responsif.
  - Seksi Testimoni Pengguna, Daftar Mitra, dan FAQ terstruktur.
  - Penerapan Penuh MVVM Clean Architecture: Pemisahan layer Entity (`Category`, `Testimonial`, `SiteStats`), Repository (`ICategoryRepository`, `ISiteInfoRepository`), UseCase (`GetCompanyProfileUseCase`), dan ViewModel (`CompanyProfileViewModel`) pada Company Profile.
  - Footer korporat resmi dengan navigasi lengkap, informasi legal, dan kontak bantuan.
- [x] **Katalog Kursus Publik (`/kursus`)**:
  - Grid daftar kursus dinamis menampilkan thumbnail, judul, instruktur, tingkat kesulitan, durasi, dan format harga Rupiah.
  - Sidebar & Drawer Filter Kategori interaktif berbasis query parameter URL (`?kategori=slug`).
  - Fitur pencarian kursus langsung di sisi klien.
  - Penerapan Penuh MVVM Clean Architecture: Pemisahan layer Entity (`Course`, `Enrollment`), Repository (`ICourseRepository`, `IEnrollmentRepository`), UseCase (`GetPublicCoursesUseCase`, `GetCourseDetailUseCase`, `GetUserEnrollmentsUseCase`), dan ViewModel (`CourseCatalogViewModel`) pada Katalog & Detail Kursus.
- [x] **Penyempurnaan Tampilan Seluler (Mobile Optimization)**:
  - Navigasi Header Drawer: Memperbaiki background drawer mobile menjadi putih pekat (*solid white*).
  - Sticky Navigation Bar: Mengunci posisi header saat layar digulir tanpa glitch visual.
  - Standardisasi container padding mobile agar konten tidak mepet ke tepi layar.
- [x] **Halaman Informasi Statis**:
  - Halaman Tentang Kami, Hubungi Kami, Syarat & Ketentuan Layanan.
- [x] **Refactoring ke MVVM Clean Architecture semua `e-learning-public`**:
  
---

### ✅ FASE 2.5 — Autentikasi, JWT, Session & Route Protection (Selesai)
*Tujuan: Menyediakan autentikasi yang aman, pengelolaan sesi tanpa celah, dan proteksi rute terisolasi.*
- [x] **Antarmuka Autentikasi Modern (50:50 Split Layout)**:
  - Halaman Masuk (`/masuk`): Panel visual branding di sisi kiri dan form login di sisi kanan.
  - Halaman Daftar (`/daftar`): Form registrasi akun siswa baru yang ringkas dan tervalidasi.
- [x] **Integrasi Next.js Server Actions & JWT Cookies**:
  - Server Actions bertindak sebagai jembatan langsung ke API Gateway secara aman.
  - Sesi login disimpan dalam HTTP-Only secure Cookies (`token`) untuk proteksi maksimal dari serangan XSS.
  - Mekanisme logout aman yang membersihkan seluruh cookie dan cache sesi.
- [x] **Alur Onboarding & Pemilihan Role**:
  - Alur seleksi profil pasca registrasi untuk menentukan tipe akun (Siswa Murid, Orang Tua, Profesional Umum).
  - Penyimpanan data tambahan ke tabel `user_profiles`.
- [x] **Proteksi Rute via Next.js Middleware**:
  - Mencegah akses pengguna belum terautentikasi ke halaman internal.
  - Pengalihan otomatis (*auto-redirect*) user yang sudah login agar tidak membuka kembali halaman `/masuk` atau `/daftar`.
- [x] **Pembersihan Total Framework Bootstrap di Frontend Publik**:
  - Mencopot seluruh dependensi CSS Bootstrap dari Next.js; beralih murni ke TailwindCSS dan Custom CSS berstandar tinggi.

---

### ✅ FASE 3 — Manajemen Entitas Utama & Standarisasi B2B Enterprise (Selesai)
*Tujuan: Membangun modul manajemen materi bagi Admin/Mentor dan menstandarisasi UI backoffice ke gaya B2B Enterprise.*
- [x] **Migrasi Menyeluruh ke Arsitektur UUID v4**:
  - Menghapus kolom integer auto-increment dari seluruh tabel database.
  - Seluruh Primary Key dan Foreign Key menggunakan format `CHAR(36)` UUID v4.
  - Menanamkan generator UUID otomatis pada model backend sebelum insert data (`beforeInsert`).
- [x] **Endpoint CRUD Kategori Pembelajaran**:
  - Manajemen kategori kursus (nama, slug SEO, icon class, status aktif) melalui API Gateway dan DAO Layer.
- [x] **Endpoint CRUD Kursus (`courses`)**:
  - Manajemen metadata kursus: judul, slug, instruktur, kategori, level, deskripsi, harga, thumbnail, video trailer, dan status rilis.
- [x] **Standarisasi Tampilan B2B Enterprise Backoffice (`e-learning-admin`)**:
  - **Unified Canvas / Cardless Layout**: Area konten utama berlatar putih bersih (`#ffffff`), menyingkirkan shadow tebal, pembatas berupa garis tipis `1px solid #e2e8f0`.
  - **Padding Mutlak 24px**: Memangkas padding 48px yang terlalu renggang menjadi tepat 24px merata di semua sisi tanpa double-padding.
  - **Pill-shaped Button**: Tombol aksi berbentuk kapsul bulat (`border-radius: 99px`) dengan micro-interaction halus saat hover.
  - **Integrasi `AgyToast`**: Mengganti total modal dan alert native browser dengan custom toast melayang.
- [x] **Halaman Course Builder**:
  - Form pembuatan kursus baru dan daftar ringkasan program dengan akses cepat ke detail kurikulum.

---

### ✅ FASE 3.5 — Interactive Curriculum Builder (Selesai)
*Tujuan: Memungkinkan Instruktur/Admin menyusun kerangka silabus pembelajaran secara kilat (Skeleton First) tanpa reload halaman.*
- [x] **Halaman Course Dashboard & Curriculum View**:
  - Rute `/admin/program/course/details/(:segment)` menampilkan struktur kurikulum di sisi kiri dan ringkasan kursus di sisi kanan.
- [x] **Manajemen Seksi Kursus (Section Management - AJAX)**:
  - Tombol `+ Add Section` memunculkan modal kustom (No-Bootstrap) dengan efek blur halus.
  - Endpoint `/admin/program/section/add` menghitung urutan otomatis (`order_index`), generate UUID v4, dan insert ke `course_sections`.
  - Pembaruan tampilan asinkron (AJAX Fetch): Seksi baru langsung muncul seketika di layar tanpa refresh halaman.
  - Fitur **Edit Judul Seksi** secara in-place melalui modal AJAX.
  - Fitur **Hapus Seksi (Delete Section)** dengan dialog konfirmasi custom dan cascade deletion materi di dalamnya.
- [x] **Manajemen Materi Pembelajaran (Lesson Management - AJAX)**:
  - Tombol `+ Add Lesson` pada setiap blok seksi kursus.
  - Form input meminta: Judul Materi dan Tipe Konten (**Video**, **Dokumen PDF**, atau **Artikel Teks**).
  - Endpoint `/admin/program/lesson/add` menyimpan materi dengan `section_id` UUID dan kalkulasi urutan otomatis.
  - Dynamic Content Icon: Tampilan materi secara otomatis menampilkan ikon dinamis sesuai tipe materi (Video, PDF, Teks).
  - Fitur **Hapus Materi (Delete Lesson)** secara asinkron.
- [x] **Integritas Data Kurikulum & Filter Soft-Delete**:
  - Penegakan query `deleted_at IS NULL` di backend agar materi yang telah dihapus tidak muncul kembali saat refresh.
- [ ] **Penanda Materi Gratis vs Berbayar (Free Preview Toggle)**:
  - Pengaturan toggle `is_free` per materi untuk preview gratis siswa (diuji saat integrasi player di Next.js).

---

### ⏳ FASE 4 — Sistem Enrollment & Pembayaran (Sedang Berjalan)
*Tujuan: Memfasilitasi transaksi pembelian kursus secara seamless, otomatisasi enrollment, dan proteksi akses seumur hidup.*
- [x] **Pengecekan Status Transaksi Aktif (Active Polling Engine)**:
  - Mengatasi kendala webhook pada server lokal, Next.js Server Action `checkPaymentStatus` memverifikasi status pembayaran langsung ke peladen Midtrans secara real-time.
- [x] **Otomatisasi Hak Akses Kursus (Auto-Enrollment Engine)**:
  - Saat pembayaran berstatus sukses (`settlement`/`capture`), sistem otomatis membuat catatan kepemilikan di tabel `enrollments` dengan UUID v4.
- [x] **Proteksi Pembelian Ulang & Akses Seumur Hidup (Lifetime Access UX)**:
  - Server Action `getUserEnrollments` mengecek kursus yang telah dimiliki oleh user login.
  - Pada Katalog (`/kursus`) dan Detail Kursus (`/kursus/[slug]`), tombol **"Beli Sekarang"** otomatis berubah menjadi **"Lanjutkan Belajar"** jika user sudah terdaftar di kursus tersebut.
- [x] **Standardisasi Rute Dasbor Tunggal (`/dashboard`)**:
  - Menghapus rute bercabang (`/dashboard/murid`, `/dashboard/ortu`, `/dashboard/umum`) dan menyatukan seluruh murid ke satu rute utama: **`/dashboard`**.
- [x] **Standardisasi Notifikasi Transaksi (Anti-Native Alert)**:
  - Menghapus seluruh `window.alert()` pada modul checkout dan status pembayaran; beralih penuh ke custom toast (`react-hot-toast`).
- [x] **Content Enrichment & Dynamic UI Polish**:
  - Memperbarui deskripsi lengkap (*full_description*) seluruh 17 kursus di database dengan salinan markdown persuasif berstandar enterprise.
  - Implementasi *Dynamic CSS Injection* (Zero Global Pollution) pada halaman Detail Kursus untuk visibilitas navigasi adaptif terhadap *dark hero banner*.
- [x] **High-Resolution AI Thumbnails & Rendering Fix**:
  - Merender 17 gambar ilustrasi *thumbnail* beresolusi tinggi (format `.jpg`) bertenaga AI untuk menggantikan SVG *placeholder*, merujuk ke desain *premium* `dummy-thumbnail.jpg`.
  - Mengubah kode UI pada `page.tsx` dari kotak abu-abu (tanpa gambar) menjadi implementasi tag `<img src={...}>` berpadu *linear-gradient overlay* (perbaikan UI esensial).
  - Melakukan *bulk update* tautan referensi *thumbnail* mutakhir langsung ke database MySQL dalam kontainer Docker.
- [ ] **Integrasi Webhook Asinkron (`e-learning-external`)**:
  - Endpoint penerima callback HTTP POST dari Midtrans untuk server staging publik.
- [x] **Halaman Riwayat Pembelian & Invoice Siswa**:
  - Halaman daftar riwayat transaksi (`/transaksi`), status pembayaran, filter dinamis, dan fitur cetak/unduh invoice digital resmi EduNusa.
- [x] **Perbaikan Logika Daur Ulang Pesanan & Kedaluwarsa Transaksi (*Auto-Expire*)**:
  - Memperbarui `created_at` saat pesanan kedaluwarsa didaur ulang agar *UI frontend* sinkron dengan batas waktu pembayaran Midtrans (1 jam).
- [x] **Optimasi Kinerja & Kompatibilitas**:
  - Memangkas *latency* (blocking server-side fetch) dari 5-8 detik menjadi instan (0 milidetik) pada detail transaksi dan riwayat menggunakan pendekatan *Client-Side Fetch* dengan *Loading Spinner* (mirip arsitektur di halaman /kursus).
  - Merombak metode popup Midtrans snap.js menjadi fitur *Snap Redirect (VT-Web)* secara layar penuh untuk mengeleminasi error blokade *Content Security Policy (CSP)* dari peramban.
  - Memperbaiki bug parsing zona waktu UTC (GMT 0) pada data frontend menjadi otomatis menyesuaikan zona waktu lokal komputer (WIB) lewat penambahan karakter format Z pada Date parser.
- [x] **Integrasi AI Chatbot EduBot (Ollama) ke Dashboard**:
  - Membuat *Server Action* Next.js sebagai proxy ke Ollama API lokal (`host.docker.internal:11434`) guna menghindari isu CORS.
  - Refactoring komponen `ChatMentorWidget` menjadi **EduBot**, dengan *system prompt* yang dirancang untuk menjawab sebagai Customer Service tier-1 menggunakan model `deepseek-r1:8b`.
  - Mengimplementasikan antarmuka chat dinamis lengkap dengan *loading indicators*.
- [ ] **Modul Laporan Transaksi di Dasbor Admin**:
  - Laporan rekapan penjualan kursus, total penerimaan kotor, dan rekapitulasi komisi platform.


---

### ⏳ FASE 5 — Interactive Learning Player & Evaluasi Siswa
*Tujuan: Menghadirkan ruang kelas virtual interaktif bagi siswa untuk mengonsumsi materi dan menguji pemahaman melalui kuis.*
- [ ] **Ruang Belajar Interaktif (*Learning Classroom Player* di Next.js)**:
  - Tata letak split-screen responsif: Area utama pemutar video (YouTube / Custom Player) atau penampil dokumen PDF/Teks; sidebar sisi kanan menampilkan daftar seksi dan materi pembelajaran.
- [ ] **Pelacak Progres Pembelajaran (*Progress Tracking Engine*)**:
  - Tombol aksi *"Tandai Selesai"* (*Mark as Completed*) per lesson yang mencatat progres ke tabel `lesson_progress`.
  - Indikator persentase penyelesaian kursus (0% - 100%) yang diperbarui secara otomatis.
- [x] **Modul Pembuat Kuis Mentor (*Quiz Builder*)**:
  - Antarmuka pembuatan kuis per seksi atau kuis akhir di portal mentor: Pilihan ganda, bobot nilai, batas waktu (*timer*), dan ambang batas kelulusan (*passing grade*).
- [ ] **Engine Pelaksanaan Kuis Siswa**:
  - Antarmuka ujian siswa dengan timer hitung mundur, navigasi nomor soal, auto-submit saat waktu habis, dan koreksi otomatis nilai.
- [ ] **Buku Nilai & Rapor Belajar Siswa**:
  - Rekam jejak percobaan kuis (`quiz_attempts`), riwayat skor, dan ulasan kunci jawaban.

---

### ⏳ FASE 6 — Affiliate/Referral System & Gamifikasi Poin
*Tujuan: Meningkatkan growth dan viralitas organik melalui sistem rujukan (referral) ber-reward poin bagi pengguna (Siswa).*
- [ ] **Modifikasi Arsitektur Database**:
  - Penambahan kolom `referral_code` (string unik) dan `points_balance` (saldo poin, default 0) pada tabel `users`.
  - Pembuatan tabel `referral_history` untuk mencatat log konversi (Siapa merekrut siapa, tanggal, dan perolehan poin).
- [ ] **Alur Registrasi & Tracking Referral**:
  - Sistem registrasi pengguna baru (`/daftar`) mampu membaca parameter `?ref=[KODE_USER]`.
  - Jika kode referral valid, sistem mencatat relasi antar pengguna (referrer dan referree).
- [ ] **Distribusi Komisi & Reward Poin Otomatis**:
  - Setiap pendaftar baru yang berhasil membuat akun menggunakan tautan referral, *referrer* otomatis mendapatkan saldo komisi (contoh: +1000 Poin setara Rp1.000).
  - (Opsional/Eskalasi) Tambahan poin komisi persentase saat referree sukses membeli kursus pertama kalinya (terhubung dengan Webhook Fase 4).
- [ ] **Modifikasi Frontend (UI/UX)**:
  - Tombol "Bagikan Kursus Ini" di halaman detail kursus otomatis menempelkan `?ref=[KODE_USER]` ke *clipboard* apabila pengguna sedang *login*.
  - Halaman Dasbor Siswa (`/dashboard`) menampilkan panel khusus Saldo Poin, riwayat komisi referral, dan opsi klaim poin.

---

### ⏳ FASE 7 — Komunitas, Kolaborasi & Live Learning
*Tujuan: Meningkatkan keterikatan belajar melalui interaksi sosial, sesi tatap muka langsung, dan apresiasi kelulusan resmi.*
- [ ] **Generator Sertifikat Kelulusan Otomatis (PDF)**:
  - Verifikasi otomatis kelulusan 100% materi dan kelulusan kuis minimal passing grade.
  - Penerbitan sertifikat digital berpenomoran unik (`certificate_number` UUID-based) dengan QR Code verifikasi keaslian publik.
- [ ] **Forum Diskusi Belajar (*Course Q&A Community*)**:
  - Ruang tanya-jawab kontekstual di bawah setiap materi antara murid dan mentor dengan fitur balasan bertingkat.
- [ ] **Integrasi Sesi Live Class (Zoom / Google Meet API)**:
  - Penjadwalan sesi mentoring online langsung, tautan otomatis ke platform video conference, dan rekapitulasi kehadiran peserta.
- [ ] **Sistem Notifikasi Real-time**:
  - Notifikasi dalam aplikasi (*in-app notification bell*) untuk update materi baru, respons forum diskusi, dan pengumuman kursus.

---

### ⏳ FASE 8 — Modul Payroll Mentor, Audit & Go-Live
*Tujuan: Menjamin akuntabilitas finansial bagi instruktur, pengujian keamanan menyeluruh, dan peluncuran resmi.*
- [ ] **Kalkulasi Bagi Hasil Otomatis (*Mentor Revenue Sharing Engine*)**:
  - Pembagian komisi otomatis per penjualan kursus yang dicatat ke tabel `mentor_earnings`.
- [ ] **Modul Pencairan Dana (*Payout / Withdrawal*)**:
  - Pengajuan penarikan dana oleh mentor, verifikasi rekening bank, dan otorisasi pembayaran oleh Admin Keuangan.
- [ ] **Audit Keamanan & Kinerja Sistem (Security & Performance Hardening)**:
  - Uji celah keamanan (SQL Injection, CSRF, XSS, rate limiting API Gateway), optimasi query database, dan audit lighthouse.
  - **Arsitektur Caching & Akselerasi Transisi Halaman (Instant Page Transitions)**:
    - In-Memory TTL Cache pada `middleware.ts` untuk memangkas *tax delay* 1,5 detik per navigasi dari pengecekan status platform.
    - Penerapan Next.js `unstable_cache` & ISR Tag Revalidation pada seluruh data publik (`categories`, `courses`, `testimonials`, `stats`, `course-detail`) sehingga navigasi antar halaman menjadi instan (< 400ms) tanpa membebani multi-tier microservice.
    - Eksekusi paralel `Promise.all` pada halaman publik (`/`, `/kursus`) untuk mengeliminasi pemanggilan serial lambat.
- [ ] **Penyebaran Produksi (Production Deployment)**:
  - Konfigurasi sertifikat SSL/HTTPS, reverse proxy server, caching statis, dan backup otomatis database.

---

### ⏳ FASE 9 — AI-Driven Helpdesk & Ticketing System
*Tujuan: Membangun pusat bantuan terpadu dengan eskalasi cerdas dari EduBot, memungkinkan penanganan keluhan secara spesifik berdasarkan role dan prioritas.*
- [ ] **Desain Struktur Database Tiket**:
  - Tabel `support_tickets`: Menyimpan ID Tiket (UUID), User ID, Kategori (TEKNIS/KEUANGAN/AKADEMIK/UMUM), Prioritas (LOW/MEDIUM/HIGH/CRITICAL), Role Penugasan (IT/FINANCE/MENTOR/CS), Status (OPEN/IN_PROGRESS/RESOLVED), dan Deskripsi.
  - Tabel `ticket_replies`: Menyimpan balasan percakapan antara siswa dan admin/mentor terkait tiket.
- [ ] **Alur Eskalasi Otomatis (EduBot Integration)**:
  - Menyambungkan ekstraksi klasifikasi `[ESKALASI|KATEGORI|PRIORITAS|ROLE]` dari `chatActions.ts` langsung ke proses *insert* tabel `support_tickets`.
  - Mengirim notifikasi *real-time* atau email otomatis ke departemen terkait (contoh: tiket CRITICAL KEUANGAN langsung memberi *ping* ke Admin Finance).
- [ ] **Halaman Helpdesk Siswa (`/dashboard/bantuan`)**:
  - Antarmuka bagi siswa untuk melihat riwayat tiket, status penyelesaian (Open, In Progress, Resolved), dan berbalas pesan dengan CS/Admin jika AI tidak Karena bisa menyelesaikan masalahnya.
- [ ] **Portal Triage & Penanganan Admin (Backoffice)**:
  - Kotak masuk (*Inbox*) khusus di dashboard Admin/Mentor yang secara otomatis tersaring berdasarkan peran login.
  - Fitur merespons tiket, mengubah status penyelesaian, dan memindah tangankan tiket (*transfer ticket*) ke departemen lain.

---

## 🔄 Detail Alur & Logika Proses Bisnis (End-to-End Business Processes)

Platform EduNusa beroperasi berdasarkan 8 alur proses bisnis terpadu berikut:

### 1. Alur Pendaftaran Akun, Otentikasi, & Onboarding Peran (Auth & Onboarding)
- **Registrasi Akun Baru (`/daftar`)**:
  - Calon pengguna memasukkan Nama, Email, dan Kata Sandi.
  - Server Action Next.js mengirim data ke `e-learning-api` (`POST /api/v1/auth/register`), lalu diteruskan ke `e-learning-internal` untuk insert ke tabel `users` dengan `id` UUID v4 dan `role: pending`.
  - Sistem menerbitkan token JWT yang disimpan aman dalam HTTP-Only secure Cookies (`token`) beserta cookie profil `user`.
- **Onboarding Pemilihan Role (`/onboarding`)**:
  - Pengguna baru berstatus role `pending` diarahkan otomatis oleh Middleware menuju `/onboarding`.
  - Pengguna memilih tipe peran definitif: **Murid Belajar**, **Orang Tua**, atau **Profesional/Umum**.
  - Server Action memperbarui kolom `role` pada tabel `users` dan membuat entri preferensi di tabel `user_profiles`.
- **Sesi Login & Proteksi Rute (`/masuk` -> `/dashboard`)**:
  - Pengguna terdaftar masuk melalui `/masuk`.
  - Setelah verifikasi berhasil, pengguna langsung diarahkan ke dasbor tunggal **`/dashboard`**.
  - Middleware secara mutlak memproteksi rute internal dari pengguna liar (belum login), serta mencegah pengguna yang sudah login agar tidak membuka kembali halaman `/masuk` atau `/daftar`.

### 2. Alur Pembuatan Kursus & Penyusunan Silabus (Curriculum Management)
- **Pembuatan Kursus Baru (*Course Builder*)**:
  - Mentor atau Admin membuat kursus baru di portal backoffice dengan mengisi data dasar: Judul, Slug SEO, Kategori, Tingkat Kesulitan (Level), Harga, Thumbnail, dan Deskripsi.
  - Kursus tersimpan di tabel `courses` dengan status awal `draft`.
- **Penyusunan Kurikulum Interaktif (*Skeleton First Workflow*)**:
  - Pengajar merakit struktur kurikulum di halaman *Course Details* tanpa reload halaman (*Zero Page Reload*):
    - **Tambah Bagian (*Section*)**: Klik `+ Add Section`, masukkan judul bagian (misal: "Bab 1: Pengenalan Dasar"). Sistem backend menghitung urutan otomatis (`order_index`), meng-generate UUID v4, dan menyimpannya ke tabel `course_sections`.
    - **Tambah Materi Pembelajaran (*Lesson*)**: Klik `+ Add Lesson` di bawah seksi terkait, tentukan judul materi dan tipe format konten (**Video**, **Dokumen PDF**, atau **Artikel Teks**). Sistem backend mencatatnya ke tabel `lessons` dengan Foreign Key `section_id`.
  - **Pratinjau Materi Gratis (*Free Preview*)**:
    - Pengajar dapat menandai materi pembuka dengan toggle `is_free = 1` agar dapat diakses gratis oleh calon siswa di halaman publik sebagai materi sampel sebelum memutuskan membeli.
- **Penerbitan Kursus (*Course Publishing*)**:
  - Setelah kurikulum dan materi lengkap, status kursus diubah menjadi `published`, sehingga kursus otomatis terbit di katalog publik `/kursus`.

### 3. Alur Eksplorasi Katalog & Proteksi Hak Akses Seumur Hidup (Lifetime Access UX)
- **Eksplorasi Katalog Kursus (`/kursus`)**:
  - Pengunjung dan siswa dapat menyaring katalog kursus berdasarkan Kategori, Level, atau kata kunci pencarian.
- **Pemeriksaan Status Kepemilikan (*Enrollment Verification*)**:
  - Saat katalog atau detail kursus dibuka oleh user yang login, Server Action `getUserEnrollments` mengambil seluruh daftar kepemilikan kursus pengguna dari tabel `enrollments`.
  - **Aturan Mutlak Pembelian Ganda**:
    - Jika pengguna **SUDAH MEMILIKI** kursus tersebut (`enrollments.some(e => e.course_id === course.id)`): Tombol pembelian **DIKUNCI** dan berubah menjadi tombol hijau **"Lanjutkan Belajar"** yang langsung mengantar ke `/dashboard`. DILARANG KERAS mengizinkan siswa membeli kembali kursus yang telah dimilikinya (Akses Seumur Hidup).
    - Jika pengguna **BELUM MEMILIKI** kursus: Tombol menampilkan aksi **"Beli Sekarang"** yang membawa ke keranjang belanja dan checkout.

### 4. Alur Transaksi, Checkout, & Gateway Pembayaran Midtrans
- **Proses Checkout (`/checkout`)**:
  - Siswa memilih kursus yang ingin dibeli dan diarahkan ke `/checkout` (wajib login terlebih dahulu).
  - Sistem membuat catatan pesanan di tabel `orders` dengan kode transaksi unik (format `TRX-...`), rincian harga (`amount`), `status: pending`, serta mengaitkan `user_id` dan `course_id`.
  - Sistem meminta pembuatan transaksi ke Midtrans Payment Gateway via API terintegrasi.
- **Pembayaran Pelanggan**:
  - Antarmuka menampilkan pilihan metode pembayaran resmi Midtrans: Virtual Account Bank (BCA, Mandiri, BNI, BRI, Permata), QRIS (GoPay, OVO, ShopeePay), atau Kartu Kredit/Debit.
- **Pengecekan Status Aktif (*Active Polling Engine*)**:
  - Pada halaman status `/transaction/status?order_code=...`, frontend mengaktifkan Server Action `checkPaymentStatus` untuk memeriksa status pembayaran langsung ke peladen Midtrans secara berkala (real-time polling) guna mengatasi limitasi webhook di environment lokal.

### 5. Alur Verifikasi Transaksi, Auto-Enrollment, & Akses Belajar
- **Konfirmasi Pembayaran Sukses (*Settlement / Capture*)**:
  - Saat pembayaran terkonfirmasi sukses di Midtrans:
    - Status transaksi pada tabel `orders` diperbarui menjadi **`paid`** beserta tanggal pelunasan (`payment_date`).
    - **Eksekusi Auto-Enrollment Instan**: Sistem secara otomatis membuat entri hak akses baru di tabel `enrollments` (`id` UUID v4, `user_id`, `course_id`, `order_id`, `enrolled_at`).
    - Sistem mencatat alokasi komisi bagi hasil untuk instruktur bersangkutan ke tabel `mentor_earnings`.
- **Pengalihan Otomatis**:
  - Notifikasi sukses muncul di layar (`react-hot-toast`), dan sistem secara otomatis mengalihkan siswa ke dasbor pembelajaran terpadu **`/dashboard`**.

### 6. Alur Dasbor Tunggal Terpadu (`/dashboard`)
- **Kebijakan URL Tunggal (Single Clean Route)**:
  - Seluruh pengguna publik yang telah login (Murid Belajar, Orang Tua, Profesional Umum) HANYA mengakses satu rute URL resmi: **`/dashboard`**.
  - Dilarang keras menampilkan URL bercabang seperti `/dashboard/murid`, `/dashboard/ortu`, atau `/dashboard/umum` pada address bar browser.
- **Conditional Component Rendering**:
  - Server Component `/app/dashboard/page.tsx` secara otomatis mendeteksi role pengguna dari sesi cookie `user.role`:
    - Peran `student`: Menampilkan antarmuka belajar murid (daftar kursus aktif yang di-enroll, progress bar capaian belajar, materi terakhir diakses, dan kuis).
    - Peran `parent`: Menampilkan antarmuka orang tua (pantauan nilai kuis anak, persentase kelulusan materi, grafik keaktifan belajar).
    - Peran `general`: Menampilkan antarmuka profesional (koleksi keahlian dan riwayat kelas praktisi).

### 7. Alur Pembelajaran Interaktif Siswa (*Learning Classroom*)
- **Ruang Kelas Virtual**:
  - Siswa membuka materi kursus yang telah dimiliki dari dasbor `/dashboard`.
  - Ruang kelas interaktif menyajikan layout terintegrasi: Pemutar video materi (YouTube/Lokal) atau penampil dokumen PDF/Artikel di area utama, serta daftar seksi silabus di panel navigasi samping.
- **Pencatatan Kemajuan Belajar (*Progress Tracking*)**:
  - Setiap siswa selesai mempelajari materi, mereka menekan tombol *"Tandai Selesai"* (*Mark as Completed*).
  - Sistem mencatat progres ke tabel `lesson_progress`.
  - Persentase kemajuan kursus (0% s.d. 100%) dihitung secara otomatis secara proporsional.
- **Pelaksanaan Kuis Evaluasi**:
  - Pada akhir seksi atau bab, siswa mengerjakan kuis evaluasi bertimer.
  - Nilai kuis dikoreksi secara otomatis oleh sistem dan dicatat ke tabel `quiz_attempts`.

### 8. Alur Kelulusan, Sertifikasi Digital, & Payroll Bagi Hasil Mentor
- **Penerbitan Sertifikat Kelulusan Otomatis**:
  - Syarat kelulusan mutlak: Seluruh materi terselesaikan (progres 100%) DAN nilai kuis evaluasi memenuhi ambang batas minimum (*passing grade*).
  - Sistem secara otomatis menerbitkan sertifikat digital di tabel `certificates` berpenomoran unik berbasis UUID (`certificate_number`) yang dilengkapi QR Code verifikasi publik.
- **Siklus Finansial & Pencairan Saldo Mentor (*Payout*)**:
  - Komisi instruktur dari setiap pembelian kursus yang berhasil terakumulasi secara otomatis di tabel `mentor_earnings`.
  - Instruktur dapat melihat mutasi saldo dan mengajukan penarikan dana (*withdrawal*) di portal mentor (`teacher.edunusa.edu.id`).
  - Admin Keuangan (Finance) memverifikasi rekening bank dan memproses pencairan di portal admin (`edunusa.console.edu.id`), mencatat pengeluaran ke tabel `payroll_periods`.



---

## 🗃️ Struktur Database Utama (Strict UUID v4 Architecture)

Seluruh tabel menggunakan `id CHAR(36) PRIMARY KEY` (UUID v4) tanpa auto-increment integer.

> [!IMPORTANT]
> **ATURAN MUTLAK PEMISAHAN ENTITAS PENGGUNA (STRICT SEPARATION)**
> DILARANG KERAS menggabungkan entitas pengguna ke dalam satu tabel. Masing-masing entitas memiliki tabel independen yang TIDAK BOLEH digabung dengan alasan apapun:
> 1. **`users`** = HANYA untuk Siswa / Peserta Publik (Murid, Orang Tua, Umum).
> 2. **`mentors`** = HANYA untuk Instruktur / Pengajar (Direferensikan oleh tabel `courses` sebagai `instructor_id`).
> 3. **`admins`** = HANYA untuk Administrator pengelola sistem (Superadmin, Finance, Academic Manager).

### Pemetaan Kelompok Tabel:
- **Identitas & Akun:** `users`, `admins`, `mentors`, `user_profiles`
- **Kurikulum & Konten:** `categories`, `courses`, `course_sections`, `lessons`
- **Pembelajaran & Evaluasi:** `enrollments`, `lesson_progress`, `quizzes`, `questions`, `quiz_attempts`
- **Transaksi & Keuangan:** `orders`, `mentor_earnings`, `payroll_periods`
- **Komunitas & Sertifikasi:** `discussions`, `certificates`, `live_classes`, `notifications`
- **Helpdesk & Bantuan:** `support_tickets`, `ticket_replies`

---

## 🤖 PROMPT SYSTEM: PANDUAN UTAMA UI/UX B2B PREMIUM
*(Prompt ini merangkum seluruh perbaikan UI/UX yang telah dikerjakan dan menjadi panduan mutlak bagi AI selanjutnya)*

**System Prompt UI/UX:**
"Anda adalah UX/UI Engineer untuk aplikasi B2B Premium Enterprise EduNusa. Seluruh desain Anda WAJIB mematuhi:
1. **Modern Flat & Clean:** Latar belakang putih (`bg-white`), border tipis (`border-slate-100`), dan hindari bayangan tebal (cukup `shadow-sm`). Sudut membulat proporsional (`rounded-2xl`).
2. **Tombol Kapsul & Reaktif:** Call-to-Action selalu membulat penuh (`rounded-full`), menggunakan gradien elegan (`from-[#6C47FF] to-[#8B5CF6]`), dengan *micro-animation* (`hover:-translate-y-[2px] transition-all`).
3. **Hierarki Ikon & Lencana:** Ikon informatif ditempatkan dalam kotak/lingkaran berwarna lembut (`bg-indigo-50 text-indigo-600`). Lencana status pada latar gelap menggunakan *transparan gradien*, sedangkan pada latar terang menggunakan solid pastel.
4. **Layout Proporsional:** Pastikan ruang bernapas (breathing room) yang cukup. Jangan menumpuk padding ganda di dalam card.
5. **Komponen Reusable (DRY):** SELALU gunakan kembali komponen dari `src/components/ui/` (seperti `SimpleHero`, `SectionHeader`, `StatCard`, `IconInputGroup`). DILARANG membuat ulang komponen fungsional yang sudah ada atau menyalahi gaya lama yang sudah pakem."

---

## 🎨 UI/UX Design Guidelines (B2B Premium Enterprise Standard)

1. **Unified Canvas & Modern Flat Borders (Garis Tipis, Tanpa Bayangan Tebal)**
   - Area konten utama, *Cards*, dan *Modals* WAJIB berlatar putih murni (`#ffffff` / `bg-white`).
   - Pemisahan visual antar elemen menggunakan garis pembatas tipis yang elegan (`border border-slate-100` atau `border-slate-200`), dilarang menggunakan *box-shadow* yang tebal/menyebar (hindari `shadow-lg` ke atas pada card biasa, gunakan `shadow-sm` atau tanpa shadow).
   - Seluruh *Card* wadah informasi menggunakan sudut membulat proporsional (`rounded-2xl` atau `rounded-xl`).

2. **Tipografi & Pewarnaan Badge Khas (Enterprise Accents)**
   - **Badge Transparan Gradien**: Gunakan format `bg-{color}-500/20 text-{color}-300 border border-{color}-500/30` untuk label status/kategori di atas latar belakang gelap (Hero section).
   - **Badge Terang**: Gunakan format `bg-{color}-50 text-{color}-700 border border-{color}-200` untuk label di atas area putih.
   - Ikon-ikon pada list/badge (seperti `fa-check-circle`, `fa-play`, `fa-fire`) selalu diberi aksen warna yang *vibrant* (emerald-400, amber-500, primary).

3. **Tombol Eksekutif & Elegan (Pill-Shaped / Kapsul)**
   - Tombol aksi primer (seperti "Lanjutkan Belajar", "Beli Sekarang") dan sekunder WAJIB berbentuk kapsul membulat penuh (`rounded-full`), bukan kotak kaku.
   - Wajib dilengkapi *micro-animation* yang halus saat *hover* (`hover:-translate-y-[3px]`, `transition-all duration-200`).
   - Warna tombol tidak kaku: manfaatkan gradien (`bg-gradient-to-br from-[#6C47FF] to-[#8B5CF6]`) untuk memberikan nuansa hidup pada Call-to-Action.

4. **Struktur Layout Responsif & Sticky Sidebar**
   - Halaman detail seperti kursus memiliki dua kolom di layar besar (Desktop): Kolom Kiri untuk konten utama (`lg:col-span-8`) dan Kolom Kanan untuk *Checkout/Pricing Card* yang melayang/menempel (`lg:col-span-4 lg:sticky lg:top-8`).
   - *Breathing Room* mutlak: Bantalan antar elemen menggunakan skala wajar (`p-5` hingga `p-6`). Dilarang keras menumpuk padding ganda di dalam card.

5. **Anti-Bootstrap / Full Custom UI & Custom Toasts**
   - **Haram** menggunakan `window.alert()` atau komponen bawaan Bootstrap.
   - Semua pop-up/notifikasi menggunakan *Custom Toast* (`AgyToast` / `react-hot-toast`).

6. **Pengalaman Memuat (Skeleton Shimmer Premium)**
   - Semua *loading state* (pengambilan data async) WAJIB digantikan oleh **Skeleton Loading** dengan efek gradien memantul/shimmer bergerak dari kiri ke kanan (seperti di Facebook, Tokopedia).
   - Jangan gunakan *spinner bulat melingkar* biasa yang tampak generik dan murahan. Bentuk skeleton harus mencerminkan struktur asli UI (contoh: skeleton card invoice lengkap dengan garis-garis baris).

7. **Reuse Komponen Visual (DRY - Don't Repeat Yourself)**
   - Selalu impor dan panggil ulang komponen UI kustom modular di `src/components/ui/` seperti `ContentCard`, `FlatButton`, `FeaturePillar`, `ObjectiveItem`, dan `CurriculumAccordion` untuk elemen yang repetitif. DILARANG membuat duplikasi styling Tailwind `className` panjang dari nol jika komponennya sudah tersedia.

8. **Standar User Dropdown / Profile Popup**
   - **Struktur wajib** (dari atas ke bawah):
     1. **Header**: Avatar inisial lingkaran gradient ungu + Nama lengkap (bold) + Email (kecil/muted)
     2. **Divider** tipis `border-slate-100` atau `1px solid #f1f5f9`
     3. **Menu items**: Setiap item WAJIB memiliki ikon dalam kotak rounded (`border-radius: 8px`, background `#f1f5f9`). Ikon berwarna ungu `#6C47FF`.
     4. **Divider** sebelum tombol keluar
     5. **Tombol "Keluar Akun"**: Teks + ikon berwarna merah `#ef4444`, background hover `#fff5f5`, ikon dalam kotak `#fee2e2`
   - **Avatar**: Cukup inisial 2 huruf dari nama user, gradient ungu `135deg, #6C47FF → #4f46e5`. DILARANG menggunakan gambar eksternal (ui-avatars.com atau sejenisnya).
   - **Animasi buka**: `fadeSlideDown` — opacity 0→1 + translateY(-6px→0) dalam 0.18s ease.
   - **Penutup otomatis**: Klik di luar area popup WAJIB menutupnya (via `mousedown` event listener + `useRef`).
   - **Logout yang benar**: WAJIB menghapus cookie `token` dan `user` secara eksplisit di client-side sebelum redirect ke `/masuk`.
   - **Referensi implementasi**: `src/components/layout/Navbar.tsx` → komponen `UserDropdown`.

8. **Ketentuan Spesifik Fitur & Transaksi (UX/UI & Bisnis)**
   - **Video Preview**: *Thumbnail* kursus harus dilengkapi elemen tombol *Play* di tengah yang membesar (`hover:scale-110`) ketika disentuh kursor, dan *overlay* transparan gelap.
   - **Tombol Kembali (Back Navigation)**: WAJIB bergaya *btn-pill-outline* (mirip seperti tombol 'Jelajahi Kursus Lainnya').
   - **Instruksi Pembayaran (*Step-by-Step*)**: Halaman detail transaksi WAJIB menampilkan instruksi pembayaran secara gamblang (langkah demi langkah) layaknya panduan pembayaran bawaan Midtrans (Contoh: Menampilkan nomor rekening VA besar-besar beserta tombol 'Salin').
   - **Status 'Menunggu Pembayaran'**: WAJIB menampilkan *Countdown Timer* (waktu hitung mundur berjalan) sehingga pengguna tahu persis kapan batas akhir pembayaran pesanannya.
   - **Status 'Kedaluwarsa' (Expired)**: WAJIB secara eksplisit menampilkan Tanggal & Jam Kedaluwarsa dari pesanan tersebut di halaman detail transaksi.
   - **Checkout Flow & Midtrans Full-Screen**: Eksekusi pembayaran Midtrans WAJIB dilakukan melalui metode *Redirect (VT-Web)* secara layar penuh (full-screen), DILARANG menggunakan metode iframe/popup (snap.js) untuk menghindari pemblokiran *Content Security Policy (CSP)*. Label harga diskon harus mencolok (seperti "Hemat 33%").
   - **Nomor Invoice & Tagihan**: Nomor Invoice (misal: INV/2026/...) HANYA BOLEH diterbitkan dan ditampilkan jika transaksi sudah berstatus berstatus sukses/lunas (paid / settlement). Jika masih pending atau expired, tampilkan nomor pesanan (Order Code TRX-...) sebagai gantinya.
   - **Kursus Akses Seumur Hidup**: Kursus HANYA BISA DIBELI SATU KALI UNTUK SELAMANYA. Tombol aksi wajib dikunci menjadi 'Lanjutkan Belajar' bagi user yang sudah memiliki kursus.

![Referensi Desain Final B2B Premium](C:/Users/Pongo/.gemini/antigravity-ide/brain/72cb767c-2a80-4b28-ab1c-5263a89313fc/.user_uploaded/media_1790531842552.png)


## 🎨 Standar Aset & Course Thumbnail (Asset Normalization)
1. **Penyimpanan Thumbnail Kursus:**
   - Kolom `thumbnail` pada tabel `courses` dapat berisi nama file relatif (misal `react.jpg`) ataupun URL penuh.
   - Frontend Next.js WAJIB menggunakan resolver `getCourseThumbnail()` dari `@/core/utils/imageHelper` untuk menormalisasi string ke path publik valid.
2. **Fallback & Static Assets:**
   - Direktori `public/images/courses/` dan `public/` wajib memiliki aset placeholder resmi (`placeholder.jpg`) untuk mencegah error `404 (Not Found)` di browser console.
   - Komponen `checkout` dan `cart` menggunakan normalisasi path otomatis saat merender ringkasan pesanan.
3. **Brand Identity & Favicon:**
   - Seluruh favicon (`favicon.ico`, `icon.svg`, `icon.png`, `apple-touch-icon.png`) wajib menggunakan logo resmi EduNusa (topi toga putih dengan latar rounded squircle gradient `#6366F1` ke `#4338CA`), bukan logo default framework (Next.js/Vercel).
   - Indikator development bawaan framework (`devIndicators`) dinonaktifkan di `next.config.ts` agar tidak menutupi elemen navigasi / user profile di pojok kiri bawah.






