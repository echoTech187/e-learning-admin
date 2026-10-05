# Dokumen Desain — Fase 4: Sistem Enrollment, Pembayaran Midtrans & Dashboard Siswa

> **Status Fase**: Sebagian besar selesai. Dokumen ini adalah arsitektur definitif dan panduan untuk item pending.

---

## Overview

Fase 4 mengimplementasikan ekosistem transaksi lengkap pada platform EduNusa. Arsitektur dirancang dengan **multi-tier microservices** di mana setiap layanan memiliki tanggung jawab yang terisolasi:

- **Next.js** (`e-learning-public`): UI/UX, MVVM, Server Actions sebagai BFF (Backend for Frontend)
- **CodeIgniter 4 API Gateway** (`e-learning-api`): Validasi JWT, routing, sanitasi
- **CodeIgniter 4 Internal** (`e-learning-internal`): Satu-satunya lapisan DAO yang menyentuh MySQL
- **CodeIgniter 4 External** (`e-learning-external`): Penerima webhook dari Midtrans (akses publik)
- **CodeIgniter 4 Admin** (`e-learning-admin`): Backoffice Finance & Superadmin
- **Midtrans Gateway**: Pemrosesan pembayaran eksternal
- **Ollama Service**: Model AI lokal untuk EduBot

---

## Architecture

### Diagram Topologi Layanan

```mermaid
graph TB
    subgraph "Public Internet"
        Browser["🌐 Browser Pengguna"]
        Midtrans["💳 Midtrans Payment Gateway"]
    end

    subgraph "Docker Network: edunusa_net"
        subgraph "e-learning-public (Next.js)"
            Pages["App Router Pages"]
            ServerActions["Server Actions (BFF)"]
            MVVM["MVVM: Entity → Repo → UseCase → ViewModel"]
        end

        subgraph "e-learning-api (CI4 Gateway)"
            APIRoutes["REST Routes /api/v1/..."]
            JWTFilter["JWT Validation Filter"]
        end

        subgraph "e-learning-external (CI4 Webhook)"
            WebhookEndpoint["POST /webhook/midtrans"]
            SigValidator["Signature Validator (SHA512)"]
        end

        subgraph "e-learning-internal (CI4 DAO)"
            OrderModel["OrderModel"]
            EnrollmentModel["EnrollmentModel"]
            MentorEarningModel["MentorEarningModel"]
            MySQL[("MySQL: elearning DB")]
        end

        subgraph "e-learning-admin (CI4 Backoffice)"
            AdminPages["Admin Pages (MVC)"]
            FinanceReport["Finance Report Module ⏳"]
        end
    end

    subgraph "Host Network"
        Ollama["🤖 Ollama (deepseek-r1:8b)\nhost.docker.internal:11434"]
    end

    Browser -->|"HTTPS"| Pages
    Pages --> ServerActions
    ServerActions -->|"HTTP"| APIRoutes
    ServerActions -->|"HTTP"| Ollama
    APIRoutes -->|"JWT Validated"| JWTFilter
    JWTFilter -->|"Internal HTTP"| OrderModel
    Midtrans -->|"HTTP POST callback"| WebhookEndpoint
    WebhookEndpoint --> SigValidator
    SigValidator -->|"Verified Forward"| OrderModel
    OrderModel --> MySQL
    EnrollmentModel --> MySQL
    MentorEarningModel --> MySQL
    AdminPages -->|"Internal HTTP"| OrderModel
```

### Prinsip Arsitektur

| Prinsip | Implementasi |
|---|---|
| **Single Source of Truth** | Hanya `e-learning-internal` yang membaca/menulis DB |
| **Zero Direct DB Access** | `e-learning-public`, `e-learning-api`, `e-learning-external`, `e-learning-admin` TIDAK boleh konek langsung ke MySQL |
| **JWT Stateless Auth** | Token di HTTP-Only Cookie; API Gateway memvalidasi setiap request |
| **MVVM Clean Architecture** | Semua logika frontend: Entity → Repository → UseCase → ViewModel |
| **Idempotent Operations** | Auto-Enrollment dan Webhook processing harus aman dieksekusi berulang |
| **VT-Web Redirect Only** | Pembayaran selalu full-screen redirect, TIDAK pernah iframe/popup |

---

## Components and Interfaces

### Frontend MVVM Layers (Next.js)

#### Entity Layer (`src/core/entities/`)

```typescript
// Order Entity
interface Order {
  id: string;                    // UUID v4
  orderCode: string;             // TRX-{TIMESTAMP}-{RANDOM}
  invoiceNumber?: string;        // INV/{YEAR}/{SEQ} — hanya jika paid
  userId: string;
  courseId: string;
  courseName: string;
  courseThumbnail?: string;
  amount: number;
  status: OrderStatus;           // 'pending' | 'paid' | 'failed' | 'expired' | 'refunded'
  paymentMethod?: string;
  snapRedirectUrl?: string;
  createdAt: string;             // ISO 8601 UTC, di-parse dengan suffix Z
  paymentDate?: string;
  expiresAt: string;             // createdAt + 1 jam
}

// Enrollment Entity
interface Enrollment {
  id: string;                    // UUID v4
  userId: string;
  courseId: string;
  orderId: string;
  enrolledAt: string;
  course?: Course;               // Joined data
}

// Chat Entity (EduBot)
interface ChatMessage {
  id: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  timestamp: Date;
}
```

#### Repository Interfaces (`src/core/repositories/`)

```typescript
interface ITransactionRepository {
  createOrder(userId: string, courseId: string): Promise<{ order: Order; snapUrl: string }>;
  getOrderStatus(orderCode: string): Promise<Order>;
  getUserOrders(userId: string): Promise<Order[]>;
  checkAndUpdatePaymentStatus(orderCode: string): Promise<Order>;
}

interface IEnrollmentRepository {
  getUserEnrollments(userId: string): Promise<Enrollment[]>;
  isEnrolled(userId: string, courseId: string): Promise<boolean>;
}

interface IChatRepository {
  sendMessage(message: string, history: ChatMessage[]): Promise<string>;
}
```

#### Use Cases (`src/core/usecases/`)

```typescript
// Checkout flow
class CreateOrderUseCase {
  execute(userId: string, courseId: string): Promise<{ snapUrl: string; order: Order }>
}

// Polling engine
class CheckPaymentStatusUseCase {
  execute(orderCode: string): Promise<Order>
}

// Lifetime access check
class GetUserEnrollmentsUseCase {
  execute(userId: string): Promise<Enrollment[]>
}

// EduBot
class ChatWithEduBotUseCase {
  execute(message: string, history: ChatMessage[]): Promise<string>
}
```

#### ViewModel Layer (`src/viewmodels/`)

```typescript
// Checkout page state
interface CheckoutViewModel {
  course: Course | null;
  order: Order | null;
  isLoading: boolean;
  error: string | null;
  isOwned: boolean;
  createOrder: () => Promise<void>;
}

// Transaction status page state
interface TransactionStatusViewModel {
  order: Order | null;
  isLoading: boolean;
  pollingActive: boolean;
  countdown: string;            // "HH:MM:SS"
  startPolling: () => void;
  stopPolling: () => void;
}

// Transaction history page state
interface TransactionHistoryViewModel {
  orders: Order[];
  filteredOrders: Order[];
  isLoading: boolean;
  activeFilter: OrderStatus | 'all';
  setFilter: (status: OrderStatus | 'all') => void;
}

// EduBot widget state
interface EduBotViewModel {
  messages: ChatMessage[];
  isOpen: boolean;
  isLoading: boolean;
  inputValue: string;
  toggleOpen: () => void;
  sendMessage: (content: string) => Promise<void>;
}
```

### Backend API Endpoints

#### `e-learning-api` (API Gateway)

| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/transactions/create` | JWT | Buat order baru |
| `GET` | `/api/v1/transactions/status/{order_code}` | Public | Cek status order |
| `GET` | `/api/v1/transactions/enrollments/{user_id}` | JWT | Daftar enrollment user |
| `GET` | `/api/v1/transactions/user` | JWT | Riwayat order user |
| `POST` | `/api/v1/transactions/auto-expire` | Internal | Ekspirasi order |
| `POST` | `/api/v1/webhooks/midtrans` | Internal | Forward webhook |

#### `e-learning-external` (Webhook Receiver) ⏳

| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| `POST` | `/webhook/midtrans` | None (Public) | Terima callback Midtrans |

#### `e-learning-internal` (DAO Layer)

| Method | Path | Deskripsi |
|---|---|---|
| `POST` | `/api/transactions` | Buat/recycle order + Midtrans token |
| `GET` | `/api/transactions/status/{order_code}` | Cek status (DB + Midtrans polling) |
| `GET` | `/api/transactions/user/{user_id}` | Riwayat order user |
| `GET` | `/api/transactions/enrollments/{user_id}` | Daftar enrollment |
| `POST` | `/api/transactions/auto-expire` | Trigger expire job |
| `POST` | `/api/webhooks/midtrans` | Proses webhook payload |

### Server Actions (Next.js BFF)

```typescript
// src/app/actions/transactionActions.ts

export async function createOrderAction(courseId: string): Promise<ActionResult<Order>>

export async function checkPaymentStatusAction(orderCode: string): Promise<ActionResult<Order>>

export async function getUserEnrollmentsAction(): Promise<ActionResult<Enrollment[]>>

export async function getUserOrdersAction(): Promise<ActionResult<Order[]>>

// src/app/actions/chatActions.ts

export async function chatWithEduBotAction(
  message: string, 
  history: ChatMessage[]
): Promise<ActionResult<string>>
```

---

## Data Models

### Tabel `orders`

```sql
CREATE TABLE orders (
    id            CHAR(36)        PRIMARY KEY,             -- UUID v4
    order_code    VARCHAR(50)     NOT NULL UNIQUE,         -- TRX-{TIMESTAMP}-{RANDOM}
    invoice_number VARCHAR(50)    DEFAULT NULL,            -- INV/{YEAR}/{SEQ}, NULL jika belum paid
    user_id       CHAR(36)        NOT NULL,
    course_id     CHAR(36)        NOT NULL,
    amount        DECIMAL(12, 2)  NOT NULL,
    status        ENUM(
                    'pending',
                    'paid',
                    'failed',
                    'expired',
                    'refunded'
                  )               DEFAULT 'pending',
    payment_method VARCHAR(50)    DEFAULT NULL,            -- 'bank_transfer', 'qris', 'credit_card'
    payment_channel VARCHAR(50)   DEFAULT NULL,            -- 'bca', 'mandiri', 'gopay', dsb.
    midtrans_transaction_id VARCHAR(100) DEFAULT NULL,
    snap_redirect_url TEXT        DEFAULT NULL,
    payment_date  DATETIME        DEFAULT NULL,            -- Diisi saat status → paid
    created_at    DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_user_course (user_id, course_id),
    INDEX idx_status_created (status, created_at)
);
```

### Tabel `enrollments`

```sql
CREATE TABLE enrollments (
    id            CHAR(36)  PRIMARY KEY,                   -- UUID v4
    user_id       CHAR(36)  NOT NULL,
    course_id     CHAR(36)  NOT NULL,
    order_id      CHAR(36)  NOT NULL,
    enrolled_at   DATETIME  DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_course (user_id, course_id),       -- Enforce lifetime access: 1 enrollment per user per course
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)  REFERENCES orders(id)  ON DELETE CASCADE
);
```

### Tabel `mentor_earnings`

```sql
CREATE TABLE mentor_earnings (
    id            CHAR(36)        PRIMARY KEY,             -- UUID v4
    order_id      CHAR(36)        NOT NULL UNIQUE,        -- 1 earning per order
    mentor_id     CHAR(36)        NOT NULL,
    course_id     CHAR(36)        NOT NULL,
    gross_amount  DECIMAL(12, 2)  NOT NULL,               -- orders.amount
    platform_fee  DECIMAL(12, 2)  NOT NULL,               -- gross * platform_fee_pct
    net_amount    DECIMAL(12, 2)  NOT NULL,               -- gross - platform_fee
    status        ENUM('pending_payout', 'paid_out', 'on_hold') DEFAULT 'pending_payout',
    created_at    DATETIME        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)  REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id)  ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
);
```

### Invoice Number Sequence

Karena tidak ada tabel sequence terpisah, `invoice_number` dihasilkan oleh `Internal_Service` saat order diupdate ke `paid`:

```sql
-- Generate invoice number: INV/{YEAR}/{AUTO_SEQ}
SELECT CONCAT(
    'INV/', YEAR(NOW()), '/',
    LPAD(
        (SELECT COUNT(*) + 1 FROM orders WHERE status = 'paid' AND YEAR(payment_date) = YEAR(NOW())),
        6, '0'
    )
) AS invoice_number;
```

> **Catatan**: Implementasi produksi sebaiknya menggunakan tabel sequence tersendiri untuk atomic increment. Saat ini menggunakan COUNT+1 yang cukup untuk volume transaksi awal.

### Entity Relationship Diagram (Fase 4)

```mermaid
erDiagram
    users ||--o{ orders : "melakukan"
    users ||--o{ enrollments : "memiliki"
    courses ||--o{ orders : "dibeli melalui"
    courses ||--o{ enrollments : "diakses via"
    orders ||--o{ enrollments : "menghasilkan"
    orders ||--|| mentor_earnings : "mengalokasikan"
    mentors ||--o{ mentor_earnings : "menerima"
    mentors ||--o{ courses : "mengajar"

    users {
        CHAR36 id PK
        VARCHAR150 name
        VARCHAR150 email
        ENUM role
        TINYINT is_active
    }

    orders {
        CHAR36 id PK
        VARCHAR50 order_code UK
        VARCHAR50 invoice_number
        CHAR36 user_id FK
        CHAR36 course_id FK
        DECIMAL amount
        ENUM status
        DATETIME payment_date
        DATETIME created_at
    }

    enrollments {
        CHAR36 id PK
        CHAR36 user_id FK
        CHAR36 course_id FK
        CHAR36 order_id FK
        DATETIME enrolled_at
    }

    mentor_earnings {
        CHAR36 id PK
        CHAR36 order_id FK
        CHAR36 mentor_id FK
        DECIMAL gross_amount
        DECIMAL platform_fee
        DECIMAL net_amount
        ENUM status
    }
```

---

## Sequence Diagrams

### Sequence 1 — Checkout & Pembuatan Order

```mermaid
sequenceDiagram
    actor Siswa
    participant FE as Next.js Frontend
    participant SA as Server Action (BFF)
    participant GW as API Gateway (e-learning-api)
    participant INT as Internal Service
    participant MT as Midtrans Gateway
    participant DB as MySQL

    Siswa->>FE: Klik "Beli Sekarang"
    FE->>FE: Cek cookie token + enrollments
    alt Belum login
        FE-->>Siswa: Redirect /masuk
    else Sudah punya kursus
        FE-->>Siswa: Tampilkan "Lanjutkan Belajar"
    else Bisa beli
        FE->>SA: createOrderAction(courseId)
        SA->>GW: POST /api/v1/transactions/create {JWT, course_id}
        GW->>GW: Validasi JWT → extract user_id
        GW->>INT: POST /api/transactions {user_id, course_id}
        INT->>DB: SELECT orders WHERE user_id=? AND course_id=? AND status IN ('pending','expired')
        alt Order lama ada (recycle)
            INT->>DB: UPDATE orders SET created_at=NOW(), status='pending', updated_at=NOW()
        else Tidak ada
            INT->>DB: INSERT orders (id=UUID4, order_code='TRX-...', status='pending')
        end
        INT->>MT: POST /snap/v1/transactions {order_id, gross_amount, customer_details}
        MT-->>INT: {token, redirect_url}
        INT->>DB: UPDATE orders SET snap_redirect_url=?
        INT-->>GW: {order, snap_redirect_url}
        GW-->>SA: {order, snap_redirect_url}
        SA-->>FE: {order, snap_redirect_url}
        FE->>Siswa: window.location.href = snap_redirect_url (VT-Web full screen)
    end
```

### Sequence 2 — Active Polling Engine

```mermaid
sequenceDiagram
    actor Siswa
    participant FE as Next.js Frontend
    participant SA as Server Action
    participant GW as API Gateway
    participant INT as Internal Service
    participant MT as Midtrans API
    participant DB as MySQL

    Siswa->>FE: Buka /transaction/status?order_code=TRX-...
    FE->>FE: Render halaman + mulai countdown timer (WIB)
    
    loop Polling (setiap N detik, selama status=pending)
        FE->>SA: checkPaymentStatusAction(orderCode)
        SA->>GW: GET /api/v1/transactions/status/{orderCode}
        GW->>INT: GET /api/transactions/status/{orderCode}
        INT->>DB: SELECT status FROM orders WHERE order_code=?
        
        alt Status DB sudah terminal (paid/failed/expired)
            INT-->>GW: {status: "paid"|"failed"|"expired"} (short-circuit)
        else Status DB masih pending
            INT->>MT: GET /v2/{order_id}/status
            MT-->>INT: {transaction_status, fraud_status}
            
            alt settlement atau capture
                INT->>DB: UPDATE orders SET status='paid', payment_date=NOW(), invoice_number='INV/...'
                INT->>DB: INSERT enrollments (idempotent check dulu)
                INT->>DB: INSERT mentor_earnings
                INT-->>GW: {status: "paid", enrollment_id: "..."}
            else expire
                INT->>DB: UPDATE orders SET status='expired'
                INT-->>GW: {status: "expired"}
            else deny atau cancel
                INT->>DB: UPDATE orders SET status='failed'
                INT-->>GW: {status: "failed"}
            else masih pending di Midtrans
                INT-->>GW: {status: "pending"}
            end
        end
        
        GW-->>SA: {status, order}
        SA-->>FE: {status, order}
        
        alt status = "paid"
            FE->>FE: Stop polling
            FE->>Siswa: Toast sukses "Pembayaran berhasil! 🎉"
            FE->>Siswa: Redirect /dashboard (setelah 3 detik)
        else status = "expired" | "failed"
            FE->>FE: Stop polling
            FE->>Siswa: Tampilkan halaman status akhir
        else status = "pending"
            FE->>FE: Lanjutkan polling (tunggu interval)
        end
    end
```

### Sequence 3 — Auto-Enrollment Chain

```mermaid
sequenceDiagram
    participant INT as Internal Service
    participant DB as MySQL

    Note over INT,DB: Dipanggil dari Sequence 2 (polling) atau Sequence 4 (webhook)

    INT->>DB: BEGIN TRANSACTION
    INT->>DB: SELECT id FROM enrollments WHERE user_id=? AND course_id=? FOR UPDATE
    
    alt Enrollment sudah ada (idempotent)
        INT->>DB: ROLLBACK
        INT-->>INT: Return existing enrollment_id
    else Belum ada
        INT->>DB: INSERT INTO enrollments (id=UUID4, user_id, course_id, order_id, enrolled_at=NOW())
        INT->>DB: SELECT instructor_id, amount, platform_fee_pct FROM courses JOIN orders
        INT->>DB: INSERT INTO mentor_earnings (id=UUID4, order_id, mentor_id, gross_amount, platform_fee, net_amount, status='pending_payout')
        INT->>DB: COMMIT
        INT-->>INT: Return new enrollment_id
    end
```

### Sequence 4 — Webhook Flow ⏳ (TO-DO)

```mermaid
sequenceDiagram
    participant MT as Midtrans Server
    participant EXT as External Service (e-learning-external)
    participant INT as Internal Service
    participant DB as MySQL

    MT->>EXT: POST /webhook/midtrans\n{order_id, status_code, gross_amount, signature_key, ...}
    
    EXT->>EXT: Validasi signature_key\n= SHA512(order_id + status_code + gross_amount + SERVER_KEY)
    
    alt Signature tidak valid
        EXT-->>MT: HTTP 403 Forbidden
    else Signature valid
        EXT->>INT: POST /api/webhooks/midtrans {payload}
        
        alt INT tidak bisa dijangkau (timeout)
            EXT->>EXT: Log payload ke file (retry queue)
            EXT-->>MT: HTTP 200 OK (prevent Midtrans retry)
        else INT berhasil diproses
            INT->>DB: Proses sama seperti Sequence 2 (paid/expired/failed)
            INT-->>EXT: {success: true, status: "paid"}
            EXT-->>MT: HTTP 200 OK
        end
    end
    
    Note over MT,EXT: Midtrans wajib terima 200 dalam < 15 detik
    Note over MT,EXT: Midtrans akan retry webhook jika tidak terima 200
```

### Sequence 5 — Invoice Generation

```mermaid
sequenceDiagram
    actor Siswa
    participant FE as Next.js Frontend
    participant SA as Server Action
    participant INT as Internal Service
    participant DB as MySQL

    Siswa->>FE: Klik "Lihat Invoice" di /transaksi
    FE->>SA: getOrderDetailAction(orderCode)
    SA->>INT: GET /api/transactions/status/{orderCode}
    INT->>DB: SELECT o.*, c.title, u.name, u.email FROM orders o\nJOIN courses c ON o.course_id=c.id\nJOIN users u ON o.user_id=u.id\nWHERE o.order_code=?
    DB-->>INT: order data
    
    alt status = 'paid'
        INT-->>SA: {order dengan invoice_number='INV/2025/000001'}
        SA-->>FE: invoice data
        FE->>Siswa: Render halaman invoice dengan:\n- Logo EduNusa\n- Invoice Number: INV/2025/000001\n- Tanggal: payment_date (format WIB)\n- Data pembeli\n- Rincian kursus\n- Stempel LUNAS
    else status ≠ 'paid'
        INT-->>SA: {order dengan order_code='TRX-...', NO invoice_number}
        SA-->>FE: order data (tanpa invoice)
        FE->>Siswa: Tampilkan Order Code TRX-... (BUKAN invoice)
    end
```

### Sequence 6 — EduBot AI Chatbot

```mermaid
sequenceDiagram
    actor Siswa
    participant FE as Next.js Chat Widget
    participant SA as Server Action (Proxy)
    participant OLL as Ollama Service (host.docker.internal:11434)

    Siswa->>FE: Ketik pesan + tekan Enter
    FE->>FE: Tambah pesan user ke state messages[]
    FE->>FE: isLoading = true (tampilkan typing indicator)
    FE->>SA: chatWithEduBotAction(message, history)
    SA->>OLL: POST /api/chat\n{model: "deepseek-r1:8b",\nmessages: [system_prompt, ...history, user_msg],\nstream: false}
    
    alt Ollama berhasil
        OLL-->>SA: {message: {role: "assistant", content: "..."}}
        SA-->>FE: {success: true, reply: "..."}
        FE->>FE: Tambah reply ke messages[], isLoading = false
        FE->>Siswa: Tampilkan balasan EduBot
    else Ollama tidak tersedia
        OLL-->>SA: Connection refused / timeout
        SA-->>FE: {success: false, error: "service_unavailable"}
        FE->>Siswa: Tampilkan pesan fallback: "EduBot sedang tidak tersedia..."
    end
    
    Note over SA,OLL: Server Action mencegah CORS:\nBrowser tidak langsung hit Ollama
```

---

## State Machine — Order Status Lifecycle

```mermaid
stateDiagram-v2
    [*] --> pending : createOrder() / recycleOrder()
    
    pending --> paid : settlement / capture\n(via polling atau webhook)
    pending --> expired : created_at + 1 jam terlewati\n(auto-expire job)
    pending --> failed : deny / cancel\n(via polling atau webhook)
    
    paid --> refunded : admin manual refund\n(Fase 7)
    
    expired --> pending : siswa buat pesanan baru\n(recycle: UPDATE created_at=NOW())
    
    paid --> [*] : terminal state
    failed --> [*] : terminal state
    refunded --> [*] : terminal state
    
    note right of pending
        Countdown timer aktif
        Active polling berjalan
        Midtrans token masih valid (1 jam)
    end note
    
    note right of paid
        Invoice Number diterbitkan
        Enrollment dibuat (idempotent)
        Mentor earnings dicatat
        Tombol → "Lanjutkan Belajar"
    end note
    
    note right of expired
        Tampilkan waktu kedaluwarsa
        Tombol → "Buat Pesanan Baru"
        Order dapat di-recycle
    end note
```

### Status Badge Mapping (UI)

| Status | Warna Badge | Label Tampil | Nomor Referensi |
|---|---|---|---|
| `pending` | 🟡 Kuning | "Menunggu Pembayaran" | Order Code `TRX-...` |
| `paid` | 🟢 Hijau | "Lunas" | Invoice Number `INV/...` |
| `failed` | 🔴 Merah | "Gagal" | Order Code `TRX-...` |
| `expired` | ⚫ Abu-abu | "Kedaluwarsa" | Order Code `TRX-...` |
| `refunded` | 🔵 Biru | "Dikembalikan" | Invoice Number `INV/...` |

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Lifetime Access — Enrollment Uniqueness Invariant

*For any* user ID and course ID pair, at any point in time, the `enrollments` table SHALL contain **at most one** record for that combination.

**Validates: Requirements 3.2, 4.5, 12.4**

---

### Property 2: Invoice Exclusivity — Paid-Only Invoice Numbers

*For any* order record, if and only if `orders.status = 'paid'`, the record SHALL have a non-null `invoice_number` following the format `INV/{YEAR}/{SEQUENCE}`. For all other status values (`pending`, `failed`, `expired`, `refunded`), `invoice_number` SHALL be null or not displayed to the user.

**Validates: Requirements 6.3, 6.4**

---

### Property 3: Auto-Enrollment Idempotency

*For any* `(user_id, course_id)` pair, triggering the Auto-Enrollment process N times (N ≥ 1) SHALL result in exactly ONE enrollment record — identical to running it once. Subsequent runs SHALL return the existing record without error and without creating duplicates.

**Validates: Requirements 3.2, 9.7, 12.4**

---

### Property 4: Countdown Timer Correctness

*For any* `created_at` timestamp from an order with status `pending`, the remaining payment time displayed SHALL equal `max(0, (created_at + 3_600_000ms) - now())`, displayed in `HH:MM:SS` format using WIB (UTC+7) timezone. When remaining time reaches zero, the display SHALL show `00:00:00` — never a negative value.

**Validates: Requirements 5.1, 5.4**

---

### Property 5: Polling Short-Circuit for Terminal Orders

*For any* order with status `paid`, `failed`, or `expired` already stored in the database, calling `checkPaymentStatus(orderCode)` SHALL return the stored status **without** making an external HTTP request to the Midtrans API.

**Validates: Requirements 2.7**

---

### Property 6: Webhook Signature Rejection

*For any* HTTP POST to the webhook endpoint, if the computed `SHA512(order_id + status_code + gross_amount + SERVER_KEY)` does NOT match the `signature_key` field in the payload, the endpoint SHALL return HTTP `403 Forbidden` and SHALL NOT modify any database records.

**Validates: Requirements 9.2, 12.3**

---

### Property 7: Button State Reflects Enrollment

*For any* authenticated user viewing any course, the purchase button state SHALL be "Lanjutkan Belajar" (disabled for purchasing) if and only if there exists an enrollment record for `(user.id, course.id)`. Otherwise, the button SHALL show "Beli Sekarang" (enabled for purchasing).

**Validates: Requirements 1.2, 4.1, 4.2**

---

### Property 8: Order Recycle Resets Countdown

*For any* order recycled from `pending` or `expired` state, the `created_at` field after recycling SHALL be within 1 second of the server's current time at the moment of recycling. This ensures the frontend countdown timer always reflects a fresh 1-hour window.

**Validates: Requirements 1.5, 5.5**

---

## Error Handling

### Error Codes & Responses

| Skenario | HTTP Status | Respons | Handling Frontend |
|---|---|---|---|
| User belum login saat checkout | `401 Unauthorized` | `{error: "unauthenticated"}` | Redirect ke `/masuk` |
| Kursus sudah dimiliki (lifetime) | `409 Conflict` | `{error: "already_enrolled"}` | Toast info + tombol "Lanjutkan Belajar" |
| Midtrans gagal buat transaksi | `502 Bad Gateway` | `{error: "payment_gateway_error"}` | Toast error + tombol "Coba Lagi" |
| Order tidak ditemukan | `404 Not Found` | `{error: "order_not_found"}` | Halaman 404 |
| Webhook signature invalid | `403 Forbidden` | `{error: "invalid_signature"}` | Log ke sistem, tidak ada respons ke user |
| Ollama tidak tersedia | `503 Service Unavailable` | `{error: "ai_unavailable"}` | Pesan fallback di chat widget |
| DB timeout / error | `500 Internal Server Error` | `{error: "internal_error"}` | Toast error generik |

### Strategi Retry

- **Polling**: Jika request `checkPaymentStatus` gagal (timeout/network), frontend mencatat error di console dan mencoba lagi pada interval berikutnya. Maksimal 3 consecutive failures sebelum menampilkan pesan "Koneksi bermasalah, coba refresh halaman."
- **Webhook**: Jika `Internal_Service` tidak dapat dijangkau saat menerima webhook, `External_Service` harus:
  1. Menyimpan payload ke antrian/log file
  2. Mengembalikan HTTP `200` ke Midtrans (mencegah retry dari Midtrans)
  3. Mengimplementasikan mekanisme retry periodik untuk memproses payload yang tersimpan

---

## Testing Strategy

### Pendekatan Pengujian

Fase 4 menggunakan pendekatan **dual testing**: unit tests untuk skenario konkret + property-based tests untuk validasi kebenaran universal pada logika bisnis kritikal.

### Property-Based Testing

Library yang direkomendasikan:
- **TypeScript (Frontend)**: `fast-check`
- **PHP (Backend)**: `eris` (PHP property-based testing)

Setiap property dalam dokumen ini harus diimplementasikan sebagai **satu property-based test** dengan minimum 100 iterasi.

```typescript
// Contoh: Property 2 - Invoice Exclusivity
import fc from 'fast-check';

test('Feature: fase-4-enrollment-pembayaran, Property 2: Invoice Exclusivity', () => {
  fc.assert(
    fc.property(
      fc.record({
        status: fc.constantFrom('pending', 'paid', 'failed', 'expired', 'refunded'),
        invoice_number: fc.option(fc.string({ minLength: 10 }))
      }),
      (order) => {
        const display = getDisplayReference(order);
        if (order.status === 'paid') {
          expect(display).toMatch(/^INV\/\d{4}\/\d+$/);
        } else {
          expect(display).toMatch(/^TRX-\d+-[A-Z0-9]+$/);
          expect(display).not.toMatch(/^INV\//);
        }
      }
    ),
    { numRuns: 100 }
  );
});
```

### Unit Tests (Contoh Prioritas Tinggi)

| Komponen | Skenario Uji |
|---|---|
| `CreateOrderUseCase` | Buat order baru, recycle order lama, tolak jika sudah enrolled |
| `CheckPaymentStatusUseCase` | Status pending, settlement, expire, short-circuit terminal |
| `getDisplayReference()` | INV untuk paid, TRX untuk semua status lain |
| `formatCountdownTimer()` | Countdown akurat, tidak negatif, format HH:MM:SS |
| `formatToWIB()` | UTC → UTC+7, edge case DST |
| `Signature Validator` | Valid SHA512, invalid signature ditolak |
| `Auto-Enrollment` | Insert baru, idempotent skip, mentor_earnings created |

### Integration Tests

| Flow | Skenario |
|---|---|
| Checkout end-to-end | Frontend → API → Internal → Midtrans → redirect |
| Payment confirmation | Midtrans settlement → DB update → enrollment → dashboard |
| Webhook processing | POST webhook → validate → update DB → return 200 |
| Transaction history | Load `/transaksi` → client-side fetch → render dengan skeleton |

### Testing Infrastruktur — Tidak Menggunakan PBT

Berikut komponen yang **tidak** cocok untuk PBT dan menggunakan pendekatan alternatif:

| Komponen | Pendekatan |
|---|---|
| API Gateway routing | Integration test (1-3 contoh per endpoint) |
| Midtrans redirect URL | Smoke test (verifikasi format URL) |
| Skeleton Loading UI | Snapshot test / visual regression |
| EduBot widget rendering | Snapshot test |
| WIB timezone display | Unit test dengan contoh UTC datetime tertentu |
