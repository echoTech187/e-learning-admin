# Design Document

## Fase 1 — Fondasi, Database & Microservices
**Platform:** EduNusa E-Learning  
**Status:** ✅ Selesai Diimplementasikan  
**Versi Dokumen:** 1.0.0  
**Tanggal:** 2025

---

## Overview

Fase 1 membangun seluruh tulang punggung infrastruktur platform EduNusa. Setiap keputusan desain di fase ini bersifat permanen dan menjadi kontrak arsitektur bagi semua fase berikutnya. Ada empat prinsip desain yang tidak boleh dilanggar:

1. **UUID v4 Everywhere** — Tidak ada integer auto-increment sebagai primary key di level manapun.
2. **Strict Entity Separation** — `users`, `mentors`, dan `admins` adalah tiga namespace yang sepenuhnya independen.
3. **Zero Direct DB Access** — Hanya `e-learning-internal` yang boleh menyentuh MySQL. Semua service lain wajib lewat HTTP.
4. **Isolated Bridge Network** — Komunikasi antar-service terjadi di dalam jaringan Docker privat, tidak pernah terekspos publik.

Dokumen ini adalah referensi tunggal (*single source of truth*) untuk memahami bagaimana keenam service dan satu database MySQL bekerja bersama sebagai satu sistem terpadu.

---

## Architecture

### System Architecture Overview (C4-Style)

```mermaid
graph TB
    subgraph "Internet (Public Zone)"
        BROWSER["🌐 Browser / Mobile\n(Pengguna Akhir)"]
    end

    subgraph "Docker Bridge Network: edunusa-net (Private Zone)"
        subgraph "Layer 3 — Frontend Services (Presentation)"
            PUBLIC["e-learning-public\n(Next.js 14, App Router)\nPort :3000\nMVVM Clean Architecture"]
            ADMIN["e-learning-admin\n(CodeIgniter 4 MVC)\nPort :8080\nSession Auth"]
            MENTOR["e-learning-mentor\n(CodeIgniter 4 MVC)\nPort :8081\nSession Auth"]
        end

        subgraph "Layer 2 — Business Logic (API Gateway)"
            API["e-learning-api\n(CodeIgniter 4 RESTful)\nPort :80\nJWT Validation · CORS · Business Logic"]
        end

        subgraph "Layer 1 — Data Access (DAO)"
            INTERNAL["e-learning-internal\n(CodeIgniter 4 DAO)\nPort :80 (internal only)\nSatu-satunya akses ke DB"]
        end

        subgraph "Layer 0 — External Integrations"
            EXTERNAL["e-learning-external\n(CodeIgniter 4)\nPort :80 (internal only)\nMidtrans · Zoom · SMTP"]
        end

        subgraph "Persistence Layer"
            DB["🗄️ MySQL 8\n(e-learning-docker-mysql-1)\nPort :3306 (internal only)\nDatabase: elearning"]
        end
    end

    subgraph "Third-Party Services"
        MIDTRANS["💳 Midtrans\nPayment Gateway"]
        ZOOM["📹 Zoom API\nLive Classes"]
        SMTP["📧 SMTP Server\nEmail Notifications"]
    end

    BROWSER -->|"HTTPS"| PUBLIC
    BROWSER -->|"HTTPS"| ADMIN
    BROWSER -->|"HTTPS"| MENTOR

    PUBLIC -->|"HTTP /api/v1/*\n(Docker hostname: api)"| API
    ADMIN -->|"HTTP /api/v1/*\n(Docker hostname: api)"| API
    MENTOR -->|"HTTP /api/v1/*\n(Docker hostname: api)"| API

    API -->|"HTTP /api/*\n(Docker hostname: internal)"| INTERNAL
    API -->|"HTTP (webhook forward)"| EXTERNAL

    INTERNAL -->|"MySQL Protocol\n(hostname: mysql)"| DB

    EXTERNAL -->|"HTTPS API"| MIDTRANS
    EXTERNAL -->|"HTTPS API"| ZOOM
    EXTERNAL -->|"SMTP/TLS"| SMTP

    MIDTRANS -->|"Webhook HTTP POST\n(callback URL)"| EXTERNAL

    classDef frontend fill:#dbeafe,stroke:#3b82f6,color:#1e3a8a
    classDef gateway fill:#dcfce7,stroke:#22c55e,color:#14532d
    classDef internal fill:#fef9c3,stroke:#eab308,color:#713f12
    classDef external fill:#fce7f3,stroke:#ec4899,color:#831843
    classDef database fill:#f3e8ff,stroke:#a855f7,color:#581c87
    classDef thirdparty fill:#f1f5f9,stroke:#94a3b8,color:#334155

    class PUBLIC,ADMIN,MENTOR frontend
    class API gateway
    class INTERNAL internal
    class EXTERNAL external
    class DB database
    class MIDTRANS,ZOOM,SMTP thirdparty
```

### Penjelasan Setiap Service

| Service | Teknologi | Port | Tanggung Jawab |
|---|---|---|---|
| **e-learning-public** | Next.js 14, App Router, TypeScript | 3000 | Portal siswa: landing page, katalog kursus, ruang belajar, dashboard siswa. MVVM Clean Architecture. |
| **e-learning-admin** | CodeIgniter 4 MVC, PHP 8.2 | 8080 | Backoffice untuk Superadmin, Finance, dan Academic Manager. Session-based auth. |
| **e-learning-mentor** | CodeIgniter 4 MVC, PHP 8.2 | 8081 | Portal instruktur untuk manajemen kursus, kurikulum, dan keuangan. Session-based auth terisolasi. |
| **e-learning-api** | CodeIgniter 4 RESTful, PHP 8.2 | 80 | API Gateway: validasi JWT, business logic, sanitasi input, CORS, orchestrasi request ke internal. |
| **e-learning-internal** | CodeIgniter 4 DAO, PHP 8.2 | 80 | Data Access Layer satu-satunya. Direct MySQL connection. Menyediakan RESTful API ke service lain. |
| **e-learning-external** | CodeIgniter 4, PHP 8.2 | 80 | Integrasi pihak ketiga: Midtrans, Zoom, SMTP. Satu-satunya service yang boleh memanggil API eksternal. |
| **MySQL 8** | MySQL 8.0, Docker | 3306 | Database tunggal terpusat. Hanya `internal` yang boleh membuka koneksi langsung. |

---

## Components and Interfaces

### Docker Compose Architecture

```mermaid
graph TB
    subgraph "docker-compose.yml"
        subgraph "Networks"
            NET["edunusa-net\n(bridge, isolated)"]
        end

        subgraph "Volumes"
            VOL_DB["mysql-data\n(persistent volume)"]
            VOL_UPLOADS["uploads-data\n(shared uploads)"]
        end

        subgraph "Services"
            SVC_MYSQL["mysql\nimage: mysql:8.0\nhostname: mysql\nPort: 3306 (internal)\nDepends: none\nHealthcheck: mysqladmin ping"]
            
            SVC_INTERNAL["internal\nbuild: ./e-learning-internal\nhostname: internal\nPort: 80 (internal)\nDepends: mysql (healthy)\nEnv: DB_HOST=mysql"]
            
            SVC_API["api\nbuild: ./e-learning-api\nhostname: api\nPort: 80→8000 (external)\nDepends: internal (healthy)\nEnv: INTERNAL_URL=http://internal"]
            
            SVC_EXTERNAL["external\nbuild: ./e-learning-external\nhostname: external\nPort: 80 (internal)\nDepends: api\nEnv: MIDTRANS_KEY, ZOOM_KEY"]
            
            SVC_PUBLIC["public\nbuild: ./e-learning-public\nhostname: public\nPort: 3000→3000 (external)\nDepends: api\nEnv: API_URL=http://api"]
            
            SVC_ADMIN["admin\nbuild: ./e-learning-admin\nhostname: admin\nPort: 80→8080 (external)\nDepends: api\nEnv: API_URL=http://api"]
            
            SVC_MENTOR["mentor\nbuild: ./e-learning-mentor\nhostname: mentor\nPort: 80→8081 (external)\nDepends: api\nEnv: API_URL=http://api"]
        end
    end

    SVC_MYSQL -->|"MYSQL_DATA"| VOL_DB
    SVC_INTERNAL -->|"uploads"| VOL_UPLOADS
    SVC_INTERNAL -->|"depends_on healthy"| SVC_MYSQL
    SVC_API -->|"depends_on healthy"| SVC_INTERNAL
    SVC_EXTERNAL -->|"depends_on"| SVC_API
    SVC_PUBLIC -->|"depends_on"| SVC_API
    SVC_ADMIN -->|"depends_on"| SVC_API
    SVC_MENTOR -->|"depends_on"| SVC_API

    NET -.->|"joins"| SVC_MYSQL
    NET -.->|"joins"| SVC_INTERNAL
    NET -.->|"joins"| SVC_API
    NET -.->|"joins"| SVC_EXTERNAL
    NET -.->|"joins"| SVC_PUBLIC
    NET -.->|"joins"| SVC_ADMIN
    NET -.->|"joins"| SVC_MENTOR
```

**Konfigurasi Docker Compose yang Direkomendasikan:**

```yaml
# docker-compose.yml
version: '3.8'

networks:
  edunusa-net:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16

volumes:
  mysql-data:
    driver: local
  uploads-data:
    driver: local

services:
  mysql:
    image: mysql:8.0
    container_name: e-learning-docker-mysql-1
    hostname: mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: elearning
      MYSQL_CHARSET: utf8mb4
      MYSQL_COLLATION: utf8mb4_unicode_ci
    volumes:
      - mysql-data:/var/lib/mysql
      - ./schema_uuid.sql:/docker-entrypoint-initdb.d/schema_uuid.sql:ro
    networks:
      - edunusa-net
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 5
    # CATATAN PRODUKSI: Port 3306 TIDAK di-expose ke host
    # Hanya expose di development untuk debugging:
    # ports:
    #   - "127.0.0.1:3306:3306"

  internal:
    build:
      context: ./e-learning-internal
      dockerfile: Dockerfile
    container_name: e-learning-internal
    hostname: internal
    restart: unless-stopped
    environment:
      CI_ENVIRONMENT: production
      database.default.hostname: mysql
      database.default.database: elearning
      database.default.username: ${DB_USERNAME}
      database.default.password: ${DB_PASSWORD}
    volumes:
      - uploads-data:/var/www/html/public/uploads
    networks:
      - edunusa-net
    depends_on:
      mysql:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 15s
      timeout: 5s
      retries: 3

  api:
    build:
      context: ./e-learning-api
      dockerfile: Dockerfile
    container_name: e-learning-api
    hostname: api
    restart: unless-stopped
    environment:
      CI_ENVIRONMENT: production
      JWT_SECRET: ${JWT_SECRET}
      INTERNAL_BASE_URL: http://internal
    ports:
      - "8000:80"
    networks:
      - edunusa-net
    depends_on:
      internal:
        condition: service_healthy

  external:
    build:
      context: ./e-learning-external
      dockerfile: Dockerfile
    container_name: e-learning-external
    hostname: external
    restart: unless-stopped
    environment:
      CI_ENVIRONMENT: production
      MIDTRANS_SERVER_KEY: ${MIDTRANS_SERVER_KEY}
      MIDTRANS_CLIENT_KEY: ${MIDTRANS_CLIENT_KEY}
      ZOOM_API_KEY: ${ZOOM_API_KEY}
      SMTP_HOST: ${SMTP_HOST}
      INTERNAL_BASE_URL: http://internal
      API_BASE_URL: http://api
    networks:
      - edunusa-net
    depends_on:
      - api

  public:
    build:
      context: ./e-learning-public
      dockerfile: Dockerfile
    container_name: e-learning-public
    hostname: public
    restart: unless-stopped
    environment:
      NEXT_PUBLIC_API_URL: ${PUBLIC_API_URL}
      API_INTERNAL_URL: http://api
    ports:
      - "3000:3000"
    networks:
      - edunusa-net
    depends_on:
      - api

  admin:
    build:
      context: ./e-learning-admin
      dockerfile: Dockerfile
    container_name: e-learning-admin
    hostname: admin
    restart: unless-stopped
    environment:
      CI_ENVIRONMENT: production
      API_BASE_URL: http://api
    ports:
      - "8080:80"
    networks:
      - edunusa-net
    depends_on:
      - api

  mentor:
    build:
      context: ./e-learning-mentor
      dockerfile: Dockerfile
    container_name: e-learning-mentor
    hostname: mentor
    restart: unless-stopped
    environment:
      CI_ENVIRONMENT: production
      API_BASE_URL: http://api
    ports:
      - "8081:80"
    networks:
      - edunusa-net
    depends_on:
      - api
```

### Startup Dependency Chain

```mermaid
flowchart LR
    MySQL -->|"healthcheck OK"| Internal
    Internal -->|"healthcheck OK"| API
    API -->|"started"| External & Public & Admin & Mentor

    style MySQL fill:#f3e8ff,stroke:#a855f7
    style Internal fill:#fef9c3,stroke:#eab308
    style API fill:#dcfce7,stroke:#22c55e
    style External fill:#fce7f3,stroke:#ec4899
    style Public fill:#dbeafe,stroke:#3b82f6
    style Admin fill:#dbeafe,stroke:#3b82f6
    style Mentor fill:#dbeafe,stroke:#3b82f6
```

---

## Data Models

### Entity Relationship Diagram

```mermaid
erDiagram
    %% ====================================
    %% IDENTITY ENTITIES
    %% ====================================
    users {
        CHAR_36 id PK "UUID v4"
        VARCHAR_150 name "NOT NULL"
        VARCHAR_150 email UK "NOT NULL UNIQUE"
        VARCHAR_255 password "bcrypt hash"
        ENUM role "student|parent|general|pending"
        VARCHAR_255 photo
        VARCHAR_20 phone
        TINYINT_1 is_active "DEFAULT 1"
        VARCHAR_100 remember_token
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at "soft delete"
    }

    admins {
        CHAR_36 id PK "UUID v4"
        VARCHAR_150 name "NOT NULL"
        VARCHAR_150 email UK "NOT NULL UNIQUE"
        VARCHAR_255 password "bcrypt hash"
        ENUM role "superadmin|finance|academic"
        VARCHAR_255 photo
        VARCHAR_20 phone
        TINYINT_1 is_active "DEFAULT 1"
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at "soft delete"
    }

    mentors {
        CHAR_36 id PK "UUID v4"
        VARCHAR_150 name "NOT NULL"
        VARCHAR_150 email UK "NOT NULL UNIQUE"
        VARCHAR_255 password "bcrypt hash"
        VARCHAR_255 photo
        VARCHAR_20 phone
        TEXT bio
        TINYINT_1 is_active "DEFAULT 1"
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at "soft delete"
    }

    user_profiles {
        CHAR_36 id PK "UUID v4"
        CHAR_36 user_id UK "FK → users.id"
        DATE date_of_birth
        TEXT address
        VARCHAR_100 city
        VARCHAR_100 province
        DATETIME created_at
        DATETIME updated_at
    }

    %% ====================================
    %% CURRICULUM ENTITIES
    %% ====================================
    categories {
        CHAR_36 id PK "UUID v4"
        VARCHAR_255 name "NOT NULL"
        VARCHAR_255 slug UK "NOT NULL UNIQUE"
        VARCHAR_255 icon
        CHAR_36 parent_id "FK → categories.id (self-ref)"
        TINYINT_1 is_active "DEFAULT 1"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    courses {
        CHAR_36 id PK "UUID v4"
        VARCHAR_255 title "NOT NULL"
        VARCHAR_255 slug UK "NOT NULL UNIQUE"
        TEXT description
        TEXT full_description
        VARCHAR_255 thumbnail
        VARCHAR_500 trailer_url
        DECIMAL_10_2 price "DEFAULT 0.00"
        ENUM level "beginner|intermediate|advanced|all"
        ENUM status "draft|published|archived"
        CHAR_36 instructor_id "FK → mentors.id"
        CHAR_36 category_id "FK → categories.id"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    course_sections {
        CHAR_36 id PK "UUID v4"
        CHAR_36 course_id "FK → courses.id"
        VARCHAR_255 title "NOT NULL"
        INT order_index "DEFAULT 0"
        TIMESTAMP created_at
        TIMESTAMP updated_at
        DATETIME deleted_at "soft delete"
    }

    lessons {
        CHAR_36 id PK "UUID v4"
        CHAR_36 section_id "FK → course_sections.id"
        VARCHAR_255 title "NOT NULL"
        ENUM type "video|pdf|article"
        TEXT content
        INT duration "seconds"
        BOOLEAN is_free "DEFAULT FALSE"
        INT order_index "DEFAULT 0"
        TIMESTAMP created_at
        TIMESTAMP updated_at
        DATETIME deleted_at "soft delete"
    }

    %% ====================================
    %% TRANSACTION ENTITIES
    %% ====================================
    orders {
        CHAR_36 id PK "UUID v4"
        VARCHAR_100 order_code UK "TRX-... format"
        CHAR_36 user_id "FK → users.id"
        CHAR_36 course_id "FK → courses.id"
        DECIMAL_10_2 amount "NOT NULL"
        ENUM status "pending|paid|failed|expired|refunded"
        VARCHAR_100 payment_method
        DATETIME payment_date
        VARCHAR_500 midtrans_token
        DATETIME expired_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    enrollments {
        CHAR_36 id PK "UUID v4"
        CHAR_36 user_id "FK → users.id"
        CHAR_36 course_id "FK → courses.id"
        CHAR_36 order_id "FK → orders.id"
        DATETIME enrolled_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    mentor_earnings {
        CHAR_36 id PK "UUID v4"
        CHAR_36 mentor_id "FK → mentors.id"
        CHAR_36 order_id "FK → orders.id"
        CHAR_36 course_id "FK → courses.id"
        DECIMAL_10_2 gross_amount "NOT NULL"
        DECIMAL_5_2 commission_rate "DEFAULT 70.00"
        DECIMAL_10_2 net_amount "NOT NULL"
        ENUM status "pending|disbursed"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    payroll_periods {
        CHAR_36 id PK "UUID v4"
        CHAR_36 mentor_id "FK → mentors.id"
        DATE period_start "NOT NULL"
        DATE period_end "NOT NULL"
        DECIMAL_10_2 total_amount "DEFAULT 0.00"
        ENUM status "draft|processing|paid"
        DATETIME paid_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    %% ====================================
    %% ACADEMIC ENTITIES
    %% ====================================
    lesson_progress {
        CHAR_36 id PK "UUID v4"
        CHAR_36 user_id "FK → users.id"
        CHAR_36 lesson_id "FK → lessons.id"
        CHAR_36 course_id "FK → courses.id"
        BOOLEAN is_completed "DEFAULT FALSE"
        DATETIME completed_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    quizzes {
        CHAR_36 id PK "UUID v4"
        CHAR_36 course_id "FK → courses.id"
        CHAR_36 section_id "FK → course_sections.id"
        VARCHAR_255 title "NOT NULL"
        INT duration "minutes"
        INT passing_grade "DEFAULT 70"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    questions {
        CHAR_36 id PK "UUID v4"
        CHAR_36 quiz_id "FK → quizzes.id"
        TEXT question_text "NOT NULL"
        JSON options "array pilihan jawaban"
        VARCHAR_10 correct_answer "a/b/c/d"
        INT score_weight "DEFAULT 1"
        INT order_index "DEFAULT 0"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    quiz_attempts {
        CHAR_36 id PK "UUID v4"
        CHAR_36 quiz_id "FK → quizzes.id"
        CHAR_36 user_id "FK → users.id"
        DECIMAL_5_2 score "DEFAULT 0.00"
        BOOLEAN is_passed "DEFAULT FALSE"
        JSON answers "snapshot jawaban siswa"
        DATETIME started_at
        DATETIME completed_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    %% ====================================
    %% RELATIONSHIPS
    %% ====================================
    users ||--o| user_profiles : "1:1 has profile"
    categories ||--o{ categories : "self-ref parent_id"
    mentors ||--o{ courses : "1:N teaches"
    categories ||--o{ courses : "1:N belongs to"
    courses ||--o{ course_sections : "1:N has sections"
    course_sections ||--o{ lessons : "1:N has lessons"
    users ||--o{ orders : "1:N places"
    courses ||--o{ orders : "1:N purchased via"
    users ||--o{ enrollments : "1:N enrolled in"
    courses ||--o{ enrollments : "1:N enrolls students"
    orders ||--o| enrollments : "1:1 grants access"
    orders ||--o| mentor_earnings : "1:1 generates commission"
    mentors ||--o{ mentor_earnings : "1:N earns from"
    mentors ||--o{ payroll_periods : "1:N receives payout"
    users ||--o{ lesson_progress : "1:N tracks"
    lessons ||--o{ lesson_progress : "1:N tracked by"
    courses ||--o{ quizzes : "1:N has quizzes"
    course_sections ||--o{ quizzes : "1:N has section quiz"
    quizzes ||--o{ questions : "1:N contains"
    quizzes ||--o{ quiz_attempts : "1:N attempted by"
    users ||--o{ quiz_attempts : "1:N attempts"
```

### DDL SQL Lengkap — Semua Tabel

```sql
-- ============================================================
-- EduNusa Database Schema
-- MySQL 8.0 | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Arsitektur: Strict UUID v4 | Soft Delete | Audit Trail
-- ============================================================

DROP DATABASE IF EXISTS elearning;
CREATE DATABASE elearning
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE elearning;

-- ------------------------------------------------------------
-- IDENTITY TABLES
-- Tiga entitas pengguna yang sepenuhnya TERPISAH.
-- Dilarang keras menggabungkan ke satu tabel.
-- ------------------------------------------------------------

CREATE TABLE users (
    id             CHAR(36)      NOT NULL,
    name           VARCHAR(150)  NOT NULL,
    email          VARCHAR(150)  NOT NULL,
    password       VARCHAR(255)  NOT NULL COMMENT 'bcrypt PASSWORD_DEFAULT',
    role           ENUM('student', 'parent', 'general', 'pending') NOT NULL DEFAULT 'pending',
    photo          VARCHAR(255)  DEFAULT NULL,
    phone          VARCHAR(20)   DEFAULT NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    remember_token VARCHAR(100)  DEFAULT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME      DEFAULT NULL COMMENT 'soft delete — NULL = aktif',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admins (
    id             CHAR(36)      NOT NULL,
    name           VARCHAR(150)  NOT NULL,
    email          VARCHAR(150)  NOT NULL,
    password       VARCHAR(255)  NOT NULL COMMENT 'bcrypt PASSWORD_DEFAULT',
    role           ENUM('superadmin', 'finance', 'academic') NOT NULL DEFAULT 'superadmin',
    photo          VARCHAR(255)  DEFAULT NULL,
    phone          VARCHAR(20)   DEFAULT NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    remember_token VARCHAR(100)  DEFAULT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME      DEFAULT NULL COMMENT 'soft delete',
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_role (role),
    KEY idx_admins_deleted_at (deleted_at)
    -- CATATAN: Tidak ada FK ke users atau mentors (strict separation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mentors (
    id             CHAR(36)      NOT NULL,
    name           VARCHAR(150)  NOT NULL,
    email          VARCHAR(150)  NOT NULL,
    password       VARCHAR(255)  NOT NULL COMMENT 'bcrypt PASSWORD_DEFAULT',
    photo          VARCHAR(255)  DEFAULT NULL,
    phone          VARCHAR(20)   DEFAULT NULL,
    bio            TEXT          DEFAULT NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    remember_token VARCHAR(100)  DEFAULT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME      DEFAULT NULL COMMENT 'soft delete',
    PRIMARY KEY (id),
    UNIQUE KEY uq_mentors_email (email),
    KEY idx_mentors_deleted_at (deleted_at)
    -- CATATAN: Tidak ada FK ke users atau admins (strict separation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_profiles (
    id            CHAR(36)      NOT NULL,
    user_id       CHAR(36)      NOT NULL,
    date_of_birth DATE          DEFAULT NULL,
    address       TEXT          DEFAULT NULL,
    city          VARCHAR(100)  DEFAULT NULL,
    province      VARCHAR(100)  DEFAULT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_profiles_user_id (user_id) COMMENT '1:1 dengan tabel users',
    CONSTRAINT fk_user_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CURRICULUM TABLES
-- Hierarki: categories → courses → course_sections → lessons
-- ------------------------------------------------------------

CREATE TABLE categories (
    id         CHAR(36)      NOT NULL,
    name       VARCHAR(255)  NOT NULL,
    slug       VARCHAR(255)  NOT NULL,
    icon       VARCHAR(255)  DEFAULT NULL COMMENT 'CSS class name (e.g. fa-code)',
    parent_id  CHAR(36)      DEFAULT NULL COMMENT 'NULL = kategori root; FK ke self = sub-kategori',
    is_active  TINYINT(1)    NOT NULL DEFAULT 1,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_parent_id (parent_id),
    KEY idx_categories_is_active (is_active),
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE courses (
    id               CHAR(36)        NOT NULL,
    title            VARCHAR(255)    NOT NULL,
    slug             VARCHAR(255)    NOT NULL,
    description      TEXT            DEFAULT NULL,
    full_description TEXT            DEFAULT NULL,
    thumbnail        VARCHAR(255)    DEFAULT NULL,
    trailer_url      VARCHAR(500)    DEFAULT NULL,
    price            DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    level            ENUM('beginner', 'intermediate', 'advanced', 'all') NOT NULL DEFAULT 'all',
    status           ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    instructor_id    CHAR(36)        NOT NULL,
    category_id      CHAR(36)        DEFAULT NULL,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_courses_slug (slug),
    KEY idx_courses_status (status) COMMENT 'Digunakan untuk filter katalog published',
    KEY idx_courses_instructor_id (instructor_id),
    KEY idx_courses_category_id (category_id),
    CONSTRAINT fk_courses_instructor
        FOREIGN KEY (instructor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    CONSTRAINT fk_courses_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE course_sections (
    id          CHAR(36)      NOT NULL,
    course_id   CHAR(36)      NOT NULL,
    title       VARCHAR(255)  NOT NULL,
    order_index INT           NOT NULL DEFAULT 0 COMMENT 'Urutan tampil dalam kursus',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME      DEFAULT NULL COMMENT 'soft delete',
    PRIMARY KEY (id),
    KEY idx_sections_course_id (course_id),
    KEY idx_sections_order (course_id, order_index),
    CONSTRAINT fk_sections_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lessons (
    id          CHAR(36)                        NOT NULL,
    section_id  CHAR(36)                        NOT NULL,
    title       VARCHAR(255)                    NOT NULL,
    type        ENUM('video', 'pdf', 'article') NOT NULL DEFAULT 'video',
    content     TEXT                            DEFAULT NULL COMMENT 'URL video / path PDF / teks artikel',
    duration    INT                             NOT NULL DEFAULT 0 COMMENT 'Durasi dalam detik',
    is_free     TINYINT(1)                      NOT NULL DEFAULT 0 COMMENT 'Free preview untuk non-enrolled',
    order_index INT                             NOT NULL DEFAULT 0 COMMENT 'Urutan tampil dalam seksi',
    created_at  TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME                        DEFAULT NULL COMMENT 'soft delete',
    PRIMARY KEY (id),
    KEY idx_lessons_section_id (section_id),
    KEY idx_lessons_order (section_id, order_index),
    KEY idx_lessons_is_free (is_free),
    CONSTRAINT fk_lessons_section
        FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TRANSACTION TABLES
-- Alur: orders (pending) → paid → enrollments + mentor_earnings
-- ------------------------------------------------------------

CREATE TABLE orders (
    id              CHAR(36)        NOT NULL,
    order_code      VARCHAR(100)    NOT NULL COMMENT 'Format: TRX-{TIMESTAMP}-{RANDOM}',
    user_id         CHAR(36)        NOT NULL,
    course_id       CHAR(36)        NOT NULL,
    amount          DECIMAL(10, 2)  NOT NULL,
    status          ENUM('pending', 'paid', 'failed', 'expired', 'refunded') NOT NULL DEFAULT 'pending',
    payment_method  VARCHAR(100)    DEFAULT NULL,
    payment_date    DATETIME        DEFAULT NULL,
    midtrans_token  VARCHAR(500)    DEFAULT NULL COMMENT 'Snap token dari Midtrans',
    expired_at      DATETIME        DEFAULT NULL COMMENT 'Batas waktu pembayaran (1 jam)',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_order_code (order_code),
    KEY idx_orders_user_id (user_id),
    KEY idx_orders_course_id (course_id),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enrollments (
    id          CHAR(36)    NOT NULL,
    user_id     CHAR(36)    NOT NULL,
    course_id   CHAR(36)    NOT NULL,
    order_id    CHAR(36)    DEFAULT NULL COMMENT 'NULL jika enrollment manual/gratis',
    enrolled_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_enrollments_user_course (user_id, course_id) COMMENT 'Mencegah double enrollment di level DB',
    KEY idx_enrollments_user_id (user_id),
    KEY idx_enrollments_course_id (course_id),
    CONSTRAINT fk_enrollments_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mentor_earnings (
    id              CHAR(36)        NOT NULL,
    mentor_id       CHAR(36)        NOT NULL,
    order_id        CHAR(36)        NOT NULL,
    course_id       CHAR(36)        NOT NULL,
    gross_amount    DECIMAL(10, 2)  NOT NULL COMMENT 'Total pembayaran bruto dari siswa',
    commission_rate DECIMAL(5, 2)   NOT NULL DEFAULT 70.00 COMMENT 'Persentase komisi mentor (default 70%)',
    net_amount      DECIMAL(10, 2)  NOT NULL COMMENT 'gross_amount * (commission_rate / 100)',
    status          ENUM('pending', 'disbursed') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mentor_earnings_mentor_id (mentor_id),
    KEY idx_mentor_earnings_status (status),
    CONSTRAINT fk_mentor_earnings_mentor
        FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_earnings_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_earnings_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payroll_periods (
    id           CHAR(36)        NOT NULL,
    mentor_id    CHAR(36)        NOT NULL,
    period_start DATE            NOT NULL,
    period_end   DATE            NOT NULL,
    total_amount DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    status       ENUM('draft', 'processing', 'paid') NOT NULL DEFAULT 'draft',
    paid_at      DATETIME        DEFAULT NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payroll_mentor_id (mentor_id),
    KEY idx_payroll_status (status),
    CONSTRAINT fk_payroll_mentor
        FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ACADEMIC TABLES
-- Progress tracking, kuis, soal, dan riwayat pengerjaan
-- ------------------------------------------------------------

CREATE TABLE lesson_progress (
    id           CHAR(36)    NOT NULL,
    user_id      CHAR(36)    NOT NULL,
    lesson_id    CHAR(36)    NOT NULL,
    course_id    CHAR(36)    NOT NULL,
    is_completed TINYINT(1)  NOT NULL DEFAULT 0,
    completed_at DATETIME    DEFAULT NULL,
    created_at   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lesson_progress (user_id, lesson_id) COMMENT 'Satu entri per siswa per materi',
    KEY idx_progress_course_id (course_id),
    CONSTRAINT fk_progress_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_lesson
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quizzes (
    id           CHAR(36)      NOT NULL,
    course_id    CHAR(36)      NOT NULL,
    section_id   CHAR(36)      DEFAULT NULL COMMENT 'NULL = kuis akhir kursus; NOT NULL = kuis seksi',
    title        VARCHAR(255)  NOT NULL,
    duration     INT           NOT NULL DEFAULT 0 COMMENT 'Durasi dalam menit; 0 = tanpa batas waktu',
    passing_grade INT          NOT NULL DEFAULT 70 COMMENT 'Nilai minimum kelulusan (0-100)',
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_quizzes_course_id (course_id),
    CONSTRAINT fk_quizzes_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_quizzes_section
        FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questions (
    id             CHAR(36)      NOT NULL,
    quiz_id        CHAR(36)      NOT NULL,
    question_text  TEXT          NOT NULL,
    options        JSON          NOT NULL COMMENT 'Array JSON: [{"key":"a","text":"..."},{"key":"b","text":"..."}]',
    correct_answer VARCHAR(10)   NOT NULL COMMENT 'Kunci jawaban: a, b, c, atau d',
    score_weight   INT           NOT NULL DEFAULT 1 COMMENT 'Bobot nilai soal',
    order_index    INT           NOT NULL DEFAULT 0,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_questions_quiz_id (quiz_id),
    CONSTRAINT fk_questions_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quiz_attempts (
    id           CHAR(36)        NOT NULL,
    quiz_id      CHAR(36)        NOT NULL,
    user_id      CHAR(36)        NOT NULL,
    score        DECIMAL(5, 2)   NOT NULL DEFAULT 0.00,
    is_passed    TINYINT(1)      NOT NULL DEFAULT 0,
    answers      JSON            DEFAULT NULL COMMENT 'Snapshot: {"q_id":"jawaban_user", ...}',
    started_at   DATETIME        DEFAULT NULL,
    completed_at DATETIME        DEFAULT NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_quiz_id (quiz_id),
    KEY idx_attempts_user_id (user_id),
    CONSTRAINT fk_attempts_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempts_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Index Strategy

```mermaid
mindmap
  root((Index Strategy))
    Authentication Lookup
      users.email
        Login query: WHERE email = ? AND deleted_at IS NULL
        Estimated selectivity: ~99%
      admins.email
        Admin login: WHERE email = ?
      mentors.email
        Mentor login: WHERE email = ?
    Catalog Performance
      courses.status
        Filter published: WHERE status = 'published'
        High frequency read
      courses.slug
        SEO URL: WHERE slug = ?
        High selectivity unique
      categories.parent_id
        Hierarchical query: WHERE parent_id IS NULL
    Access Verification
      enrollments(user_id, course_id)
        Composite index
        Lifetime access check per request
        UNIQUE constraint = index
      lesson_progress(user_id, lesson_id)
        Composite index
        Progress tracking per lesson
        UNIQUE constraint = index
    Financial
      orders.status
        Filter: pending, paid, expired
      mentor_earnings.status
        Filter: pending disbursement
```

---

## API Flow Diagrams

### Alur 1: Registrasi User Baru (daftar → pending → onboarding → role definitif)

```mermaid
sequenceDiagram
    autonumber
    actor Browser as Browser (Next.js)
    participant API as e-learning-api<br/>(API Gateway)
    participant Internal as e-learning-internal<br/>(DAO)
    participant DB as MySQL

    Note over Browser,DB: TAHAP 1: Registrasi Akun Baru

    Browser->>+API: POST /api/v1/auth/register<br/>{ name, email, password }
    API->>API: Validasi input (empty check, format email)
    API->>+Internal: GET /api/users/find_by_email?email=X
    Internal->>+DB: SELECT * FROM users WHERE email=? AND deleted_at IS NULL
    DB-->>-Internal: NULL (email belum terdaftar)
    Internal-->>-API: 404 Not Found

    API->>API: password_hash(password, PASSWORD_DEFAULT)
    API->>+Internal: POST /api/users<br/>{ name, email, password_hash, role: "pending", is_active: 1 }
    Internal->>Internal: beforeInsert hook → generate UUID v4
    Internal->>+DB: INSERT INTO users (id, name, email, password, role, ...)
    DB-->>-Internal: OK
    Internal-->>-API: { id, name, email, role: "pending" }

    API->>API: generateJWT({ uid, email, name, role: "pending", exp: +24h })
    API-->>-Browser: 201 Created<br/>{ token: "JWT...", user: { role: "pending" } }

    Note over Browser,DB: TAHAP 2: Middleware Deteksi Pending Role

    Browser->>Browser: Simpan token di HTTP-Only cookie
    Browser->>Browser: Next.js Middleware baca role dari JWT
    Browser->>Browser: role === "pending" → redirect /onboarding

    Note over Browser,DB: TAHAP 3: Onboarding — Pemilihan Role Definitif

    Browser->>+API: POST /api/v1/auth/onboarding<br/>Authorization: Bearer {JWT_pending}<br/>{ user_id, role: "student"|"parent"|"general" }
    API->>API: Validasi JWT → decode payload → cek role === "pending"
    API->>+Internal: POST /api/users/onboarding<br/>{ user_id, role: "student", profile: {...} }
    Internal->>+DB: UPDATE users SET role="student" WHERE id=?
    DB-->>-Internal: OK
    Internal->>Internal: generate UUID v4 untuk user_profiles
    Internal->>+DB: INSERT INTO user_profiles (id, user_id, ...)
    DB-->>-Internal: OK
    Internal-->>-API: { id, name, email, role: "student" }

    API->>API: generateJWT({ uid, email, name, role: "student", exp: +24h })
    API-->>-Browser: 200 OK<br/>{ token: "JWT_baru...", user: { role: "student" } }

    Browser->>Browser: Perbarui cookie token
    Browser->>Browser: Redirect ke /dashboard
```

### Alur 2: Login & JWT Validation

```mermaid
sequenceDiagram
    autonumber
    actor Browser as Browser / Client
    participant API as e-learning-api<br/>(API Gateway)
    participant Internal as e-learning-internal<br/>(DAO)
    participant DB as MySQL

    Browser->>+API: POST /api/v1/auth/login<br/>{ email, password }

    API->>API: Validasi input tidak kosong

    API->>+Internal: GET /api/users/find_by_email?email=X
    Internal->>+DB: SELECT * FROM users<br/>WHERE email=? AND deleted_at IS NULL
    
    alt Email tidak ditemukan
        DB-->>Internal: NULL
        Internal-->>API: 404 Not Found
        API-->>Browser: 401 Unauthorized<br/>"Alamat email atau password tidak sesuai"
        Note over API: Pesan IDENTIK untuk email tidak ada<br/>maupun password salah (anti user enumeration)
    else Email ditemukan
        DB-->>-Internal: { id, email, password_hash, is_active, role, ... }
        Internal-->>-API: user object

        alt Akun tidak aktif (is_active = 0)
            API-->>Browser: 401 Unauthorized<br/>"Akun Anda saat ini tidak aktif"
        else Akun aktif
            API->>API: password_verify(input_password, hash)
            
            alt Password tidak cocok
                API-->>Browser: 401 Unauthorized<br/>"Alamat email atau password tidak sesuai"
            else Password cocok
                API->>API: Buat JWT Payload:<br/>{ iat, exp: +86400s, uid, email, name, role, photo }
                API->>API: JWT::encode(payload, JWT_SECRET, "HS256")
                API-->>Browser: 200 OK<br/>{ message, token: "JWT...", user: {...} }
            end
        end
    end

    Note over Browser,DB: Validasi JWT pada Request Berikutnya

    Browser->>+API: GET /api/v1/protected-resource<br/>Authorization: Bearer {JWT}
    API->>API: Ekstrak token dari header Authorization
    API->>API: JWT::decode(token, JWT_SECRET, ["HS256"])
    
    alt Token invalid / expired / tampered
        API-->>Browser: 401 Unauthorized<br/>{ status: "error", message: "Token tidak valid" }
    else Token valid
        API->>API: Inject decoded payload ke request context
        API->>+Internal: Forward request ke internal service
        Internal-->>-API: Data response
        API-->>-Browser: 200 OK + data
    end
```

### Alur 3: Request Data Frontend → API → Internal → DB

```mermaid
sequenceDiagram
    autonumber
    actor User as User (Browser/Next.js)
    participant MW as Next.js Middleware /<br/>CI4 Auth Filter
    participant FE as Frontend Service<br/>(public/admin/mentor)
    participant API as e-learning-api<br/>(API Gateway)
    participant Internal as e-learning-internal<br/>(DAO Layer)
    participant DB as MySQL 8

    User->>+FE: Request halaman / aksi
    FE->>+MW: Cek autentikasi & otorisasi
    
    alt Token tidak ada / invalid
        MW-->>FE: Redirect ke /masuk
        FE-->>User: Halaman login
    else Token valid
        MW-->>-FE: Izinkan akses
        
        FE->>+API: HTTP Request<br/>GET /api/v1/courses<br/>Authorization: Bearer {JWT}
        
        API->>API: AuthFilter::before()<br/>Validasi JWT signature & expiry
        API->>API: Cek role dari JWT payload<br/>untuk endpoint yang memerlukan RBAC
        
        API->>API: Business logic processing<br/>(sanitasi input, format query param)
        
        API->>+Internal: HTTP Request<br/>GET /api/courses?status=published<br/>(Docker hostname: internal)
        
        Internal->>Internal: Inisialisasi Model CI4<br/>Terapkan $useSoftDeletes = true
        
        Internal->>+DB: SELECT c.*, m.name as instructor_name<br/>FROM courses c<br/>LEFT JOIN mentors m ON c.instructor_id = m.id<br/>WHERE c.status = 'published'<br/>AND c.deleted_at IS NULL<br/>ORDER BY c.created_at DESC
        
        DB-->>-Internal: Result set rows[]
        
        Internal->>Internal: Serialisasi ke JSON array
        Internal-->>-API: 200 OK<br/>{ status: "success", data: [...] }
        
        API->>API: Transform / filter data<br/>Tambahkan metadata pagination
        API-->>-FE: 200 OK<br/>{ status: "success", data: [...], meta: {...} }
        
        FE->>FE: Render komponen / template
        FE-->>-User: Halaman dengan data
    end
```

### Alur 4: Pembuatan Kursus oleh Mentor

```mermaid
sequenceDiagram
    autonumber
    participant Mentor as Portal Mentor<br/>(CI4 MVC)
    participant API as e-learning-api
    participant Internal as e-learning-internal
    participant DB as MySQL

    Mentor->>+API: POST /api/v1/courses<br/>Authorization: Bearer {JWT_mentor}<br/>{ title, slug, price, level, ... }

    API->>API: Validasi JWT → role === "mentor"
    API->>API: Validasi input (title required, slug format, price >= 0)
    API->>API: Ekstrak instructor_id dari JWT payload (uid)

    API->>+Internal: GET /api/courses?slug={slug}
    Internal->>+DB: SELECT id FROM courses WHERE slug=?
    DB-->>-Internal: NULL (slug tersedia)
    Internal-->>-API: 404 (slug belum dipakai)

    API->>+Internal: POST /api/courses<br/>{ title, slug, price, level, status: "draft", instructor_id: uid }
    Internal->>Internal: beforeInsert → generate UUID v4
    Internal->>+DB: INSERT INTO courses (id, title, slug, ..., status="draft")
    DB-->>-Internal: OK, course_id
    Internal-->>-API: { id, title, slug, status: "draft" }

    API-->>-Mentor: 201 Created<br/>{ status: "success", data: { course } }
    Mentor->>Mentor: Redirect ke halaman<br/>Course Details (Curriculum Builder)
```

---

## MVVM Architecture (Next.js e-learning-public)

### Layer Diagram

```mermaid
graph TB
    subgraph "UI Layer (Presentation)"
        PAGE["page.tsx\n(Server Component / Client Component)"]
        COMP["UI Components\n(CourseCard, EnrollButton, ProgressBar)"]
    end

    subgraph "ViewModel Layer (State & Logic)"
        VM["CourseCatalogViewModel\n(React hooks: useState, useEffect)\nMenghubungkan UseCase → UI state\nHandles: loading, error, data"]
    end

    subgraph "UseCase Layer (Business Rules)"
        UC1["GetPublicCoursesUseCase\nFilter katalog, sorting, pagination"]
        UC2["GetCourseDetailUseCase\nDetail kursus + enrollment check"]
        UC3["GetUserEnrollmentsUseCase\nDaftar kursus milik user"]
        UC4["EnrollCourseUseCase\nBusiness rule: cek duplikat, trigger order"]
    end

    subgraph "Repository Layer (Data Contract)"
        IREPO["ICourseRepository (interface)\ngetAll(), getBySlug(), getPublished()"]
        IENROLL["IEnrollmentRepository (interface)\ngetByUserId(), checkEnrollment()"]
    end

    subgraph "Repository Implementation"
        REPO["CourseRepositoryImpl\n(HTTP client ke e-learning-api)"]
        ENROLLREPO["EnrollmentRepositoryImpl\n(HTTP client ke e-learning-api)"]
    end

    subgraph "Entity Layer (Domain Model)"
        ENTITY["Course (TypeScript interface)\nEnrollment\nCategory\nUser"]
    end

    subgraph "Infrastructure"
        APIGATEWAY["e-learning-api\n(HTTP /api/v1/...)"]
    end

    PAGE --> VM
    COMP --> VM
    VM --> UC1 & UC2 & UC3 & UC4
    UC1 & UC2 --> IREPO
    UC3 & UC4 --> IENROLL
    IREPO --> REPO
    IENROLL --> ENROLLREPO
    REPO --> APIGATEWAY
    ENROLLREPO --> APIGATEWAY
    REPO --> ENTITY
    ENROLLREPO --> ENTITY

    classDef ui fill:#dbeafe,stroke:#3b82f6
    classDef vm fill:#dcfce7,stroke:#22c55e
    classDef usecase fill:#fef9c3,stroke:#eab308
    classDef repo fill:#fce7f3,stroke:#ec4899
    classDef entity fill:#f3e8ff,stroke:#a855f7
    classDef infra fill:#f1f5f9,stroke:#94a3b8

    class PAGE,COMP ui
    class VM vm
    class UC1,UC2,UC3,UC4 usecase
    class IREPO,IENROLL repo
    class REPO,ENROLLREPO repo
    class ENTITY entity
    class APIGATEWAY infra
```

### Implementasi Konkret — Course Entity (TypeScript)

```typescript
// ============================================================
// LAYER 1: Entity (core/entities/Course.ts)
// Domain model murni — tidak bergantung pada framework apapun
// ============================================================
export interface Course {
  id: string;            // UUID v4
  title: string;
  slug: string;
  description: string | null;
  full_description: string | null;
  thumbnail: string | null;
  price: number;
  level: 'beginner' | 'intermediate' | 'advanced' | 'all';
  status: 'draft' | 'published' | 'archived';
  instructor_id: string; // FK ke mentors.id
  category_id: string | null;
  created_at: string;
  updated_at: string;
  // Relasi yang di-join dari API
  instructor_name?: string;
  category_name?: string;
}

export interface Enrollment {
  id: string;
  user_id: string;
  course_id: string;
  order_id: string | null;
  enrolled_at: string;
}

// ============================================================
// LAYER 2: Repository Interface (core/repositories/ICourseRepository.ts)
// Kontrak data — tidak peduli bagaimana data didapat
// ============================================================
export interface ICourseRepository {
  getAll(params?: { kategori?: string; search?: string }): Promise<Course[]>;
  getPublished(): Promise<Course[]>;
  getBySlug(slug: string): Promise<Course | null>;
  getById(id: string): Promise<Course | null>;
}

export interface IEnrollmentRepository {
  getByUserId(userId: string): Promise<Enrollment[]>;
  checkEnrollment(userId: string, courseId: string): Promise<boolean>;
  create(userId: string, courseId: string, orderId: string): Promise<Enrollment>;
}

// ============================================================
// LAYER 3: UseCase (core/usecases/GetPublicCoursesUseCase.ts)
// Business rules — filter katalog, hanya tampilkan yang published
// ============================================================
export class GetPublicCoursesUseCase {
  constructor(private courseRepo: ICourseRepository) {}

  async execute(params?: { kategori?: string; search?: string }): Promise<Course[]> {
    const courses = await this.courseRepo.getPublished();

    let filtered = courses;

    if (params?.kategori) {
      filtered = filtered.filter(c => c.category_id === params.kategori);
    }

    if (params?.search) {
      const query = params.search.toLowerCase();
      filtered = filtered.filter(c =>
        c.title.toLowerCase().includes(query) ||
        c.description?.toLowerCase().includes(query)
      );
    }

    return filtered;
  }
}

// ============================================================
// LAYER 4: Repository Implementation (infrastructure/repositories/CourseRepositoryImpl.ts)
// Implementasi konkret — memanggil e-learning-api
// ============================================================
export class CourseRepositoryImpl implements ICourseRepository {
  private baseUrl = process.env.API_INTERNAL_URL ?? '';

  async getPublished(): Promise<Course[]> {
    const res = await fetch(`${this.baseUrl}/api/v1/courses?status=published`, {
      next: { revalidate: 60 }, // ISR: cache 60 detik
    });
    if (!res.ok) throw new Error('Failed to fetch courses');
    const json = await res.json();
    return json.data as Course[];
  }

  async getBySlug(slug: string): Promise<Course | null> {
    const res = await fetch(`${this.baseUrl}/api/v1/courses/${slug}`, {
      next: { revalidate: 300 },
    });
    if (res.status === 404) return null;
    const json = await res.json();
    return json.data as Course;
  }

  // ... getAll, getById
}

// ============================================================
// LAYER 5: ViewModel (viewmodels/CourseCatalogViewModel.ts)
// State management — menghubungkan UseCase ke komponen React
// ============================================================
export function useCourseCatalogViewModel(params?: { kategori?: string }) {
  const [courses, setCourses] = useState<Course[]>([]);
  const [enrollments, setEnrollments] = useState<Enrollment[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const courseRepo = new CourseRepositoryImpl();
  const enrollRepo = new EnrollmentRepositoryImpl();
  const getCourses = new GetPublicCoursesUseCase(courseRepo);
  const getEnrollments = new GetUserEnrollmentsUseCase(enrollRepo);

  useEffect(() => {
    const load = async () => {
      setIsLoading(true);
      try {
        const [courseData, enrollData] = await Promise.all([
          getCourses.execute(params),
          getEnrollments.execute(),
        ]);
        setCourses(courseData);
        setEnrollments(enrollData);
      } catch (e) {
        setError('Gagal memuat data kursus');
      } finally {
        setIsLoading(false);
      }
    };
    load();
  }, [params?.kategori]);

  const isEnrolled = (courseId: string): boolean =>
    enrollments.some(e => e.course_id === courseId);

  return { courses, enrollments, isLoading, error, isEnrolled };
}
```

---

## Security Architecture

### JWT Token Structure & Validation Flow

```mermaid
flowchart TD
    subgraph "JWT Token Structure"
        HEADER["Header\n{ alg: 'HS256', typ: 'JWT' }"]
        PAYLOAD["Payload\n{\n  iat: 1700000000,\n  exp: 1700086400,\n  uid: 'uuid-v4',\n  email: 'user@...',\n  name: 'Nama User',\n  role: 'student',\n  photo: '/uploads/...'\n}"]
        SIG["Signature\nHMAC-SHA256(\n  base64url(header) + '.' + base64url(payload),\n  JWT_SECRET\n)"]
    end

    subgraph "Token Lifecycle"
        LOGIN["User Login Berhasil"]
        ISSUE["API Issues JWT\nexp = now() + 86400 detik\n(24 jam)"]
        STORE["Frontend Simpan di\nHTTP-Only Secure Cookie\n(token)"]
        REQUEST["User Buat Request\nAuthorization: Bearer JWT"]
        VALIDATE["API Gateway Validasi:\n1. Signature valid?\n2. exp > now()?\n3. Role sesuai endpoint?"]
        SUCCESS["Request Diproses"]
        FAIL["401 Unauthorized\nUser diarahkan ke /masuk"]
        EXPIRE["Token Expired\n(after 24 hours)\nUser harus login ulang"]
    end

    LOGIN --> ISSUE --> STORE
    STORE --> REQUEST --> VALIDATE
    VALIDATE -->|"Valid"| SUCCESS
    VALIDATE -->|"Invalid/Tampered"| FAIL
    VALIDATE -->|"Expired"| EXPIRE
    EXPIRE --> LOGIN

    classDef token fill:#fef9c3,stroke:#eab308
    classDef lifecycle fill:#dbeafe,stroke:#3b82f6
    classDef fail fill:#fee2e2,stroke:#ef4444
    classDef success fill:#dcfce7,stroke:#22c55e

    class HEADER,PAYLOAD,SIG token
    class LOGIN,ISSUE,STORE,REQUEST lifecycle
    class FAIL,EXPIRE fail
    class SUCCESS,VALIDATE success
```

**Aturan Keamanan JWT:**

| Aspek | Implementasi |
|---|---|
| Algoritma | `HS256` (HMAC-SHA256) |
| Secret Key | Minimum 32 karakter, dari environment variable `JWT_SECRET` |
| Masa Berlaku | 86400 detik (24 jam) sejak `iat` |
| Penyimpanan di client | HTTP-Only Secure Cookie (terlindungi dari XSS) |
| Payload fields | `iat`, `exp`, `uid`, `email`, `name`, `role`, `photo` |
| **DILARANG** | Hardcode secret di source code, simpan di localStorage, commit `.env` ke git |

### Password Hashing Strategy

```mermaid
flowchart LR
    subgraph "Registration Flow"
        P1["Password Plaintext\n'mysecretpassword'"]
        H1["password_hash()\nALGORITHM: PASSWORD_DEFAULT\n= bcrypt, cost: 10+"]
        DB1["Stored in DB\n'$2y$10$Ye4oKoEa3Ro9ll...'"]
        P1 -->|"API Gateway"| H1 --> DB1
    end

    subgraph "Login Verification Flow"
        P2["Input Password\n'mysecretpassword'"]
        H2["password_verify(\n  input_password,\n  stored_hash\n)"]
        R2{"Match?"}
        OK["Login Berhasil\n→ Issue JWT"]
        FAIL["401 Unauthorized\n(pesan identik)"]
        P2 --> H2 --> R2
        R2 -->|"true"| OK
        R2 -->|"false"| FAIL
    end

    subgraph "Anti-Enumeration"
        A1["Email tidak ditemukan"]
        A2["Password salah"]
        SAME["Respons IDENTIK:\n'Alamat email atau password\nyang Anda masukkan\ntidak sesuai.'"]
        A1 --> SAME
        A2 --> SAME
    end
```

### CORS Configuration Strategy

```php
// app/Config/Cors.php — e-learning-api
// Hanya origin yang terdaftar yang diizinkan
$allowedOrigins = [
    'https://edunusa.edu.id',          // Production public
    'https://edunusa.console.edu.id',  // Production admin
    'https://teacher.edunusa.edu.id',  // Production mentor
    'http://localhost:3000',           // Development public
    'http://localhost:8080',           // Development admin
    'http://localhost:8081',           // Development mentor
];

// Headers yang diizinkan
$allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'];

// Methods yang diizinkan
$allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
```

### Service-to-Service Communication Security

```mermaid
graph LR
    subgraph "Komunikasi Internal (Docker Network)"
        API["e-learning-api\n(port :80)"]
        INTERNAL["e-learning-internal\n(port :80, no external access)"]
        
        API -->|"Plain HTTP\n(Docker hostname: internal)\nJaringan private, terenkripsi oleh Docker network"| INTERNAL
        
        note1["⚠️ Service ke service menggunakan\nplain HTTP karena sudah dalam\nisolated Docker bridge network\nTidak ada enkripsi TLS tambahan\nyang dibutuhkan di layer ini"]
    end

    subgraph "Keamanan e-learning-internal"
        RULE1["✅ Port 80 TIDAK di-expose ke host"]
        RULE2["✅ Hanya dapat diakses dari edunusa-net"]
        RULE3["✅ Tidak ada public DNS / reverse proxy"]
        RULE4["✅ Firewall: DROP semua koneksi dari luar Docker network"]
    end

    INTERNAL --- RULE1
    INTERNAL --- RULE2
    INTERNAL --- RULE3
    INTERNAL --- RULE4
```

---

## UUID v4 Auto-Generation Flow

### Alur beforeInsert Hook

```mermaid
flowchart TD
    A["Client mengirim INSERT request\nke e-learning-internal\nPOST /api/users\n{ name, email, password }"] 
    --> B{"`id` sudah ada\ndalam payload?`"}

    B -->|"Ya (idempotent)"| C["Gunakan id yang diberikan\n(validasi panjang = 36 char)"]
    B -->|"Tidak"| D["generateUUID() dipanggil\noleh beforeInsert hook"]

    D --> E["Algoritma UUID v4:\nformat: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx\nbit 12-15 = 0100 (versi 4)\nbit 6-7 = 10 (variant RFC 4122)"]

    E --> F["PHP Implementation:\nsprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',\n  mt_rand(0, 0xffff), mt_rand(0, 0xffff),\n  mt_rand(0, 0xffff),\n  mt_rand(0, 0x0fff) | 0x4000,\n  mt_rand(0, 0x3fff) | 0x8000,\n  mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)\n)"]

    F --> G["Validasi Hasil:\nstrlen(id) === 36?\npreg_match UUID v4 pattern?"]

    G -->|"Valid"| C
    G -->|"Invalid"| H["Lempar Exception:\nInvalidUUIDException\nRollback INSERT"]

    C --> I["INSERT statement dieksekusi ke MySQL\nINSERT INTO users (id, ...) VALUES (uuid, ...)"]
    I --> J["Response dikembalikan:\n{ id: 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx', ... }"]
```

### Format Validasi UUID v4

```mermaid
graph LR
    subgraph "UUID v4 Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
        P1["xxxxxxxx\n(8 hex)\nRandom"]
        D1["-"]
        P2["xxxx\n(4 hex)\nRandom"]
        D2["-"]
        P3["4xxx\n(4 hex)\nVersi = 4"]
        D3["-"]
        P4["yxxx\n(4 hex)\ny = 8,9,a,b\n(RFC 4122 variant)"]
        D4["-"]
        P5["xxxxxxxxxxxx\n(12 hex)\nRandom"]

        P1 --- D1 --- P2 --- D2 --- P3 --- D3 --- P4 --- D4 --- P5
    end

    subgraph "Contoh Valid"
        EX1["550e8400-e29b-41d4-a716-446655440000"]
        EX2["6ba7b810-9dad-41d4-80b4-00c04fd430c8"]
    end

    subgraph "Validasi PHP"
        REGEX["/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i"]
        STRLEN["strlen(uuid) === 36"]
    end
```

**Implementasi beforeInsert di semua Model CI4:**

```php
// app/Models/BaseInternalModel.php (shared trait)
<?php

namespace App\Models;

use CodeIgniter\Model;

abstract class BaseInternalModel extends Model
{
    protected $useAutoIncrement = false; // WAJIB false untuk UUID
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $beforeInsert     = ['generateUUID'];

    /**
     * Auto-generate UUID v4 sebelum INSERT.
     * Idempotent: jika id sudah ada di payload, tidak di-overwrite.
     */
    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $uuid = $this->createUUIDv4();

            // Validasi panjang dan format
            if (strlen($uuid) !== 36) {
                throw new \RuntimeException('UUID generation failed: invalid length');
            }

            $data['data']['id'] = $uuid;
        }

        return $data;
    }

    private function createUUIDv4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), // time_low
            mt_rand(0, 0xffff),                      // time_mid
            mt_rand(0, 0x0fff) | 0x4000,             // time_hi_and_version (version 4)
            mt_rand(0, 0x3fff) | 0x8000,             // clock_seq (variant RFC 4122)
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff) // node
        );
    }
}
```

---

## RBAC Permission Matrix

### Tabel Permission Lengkap per Role per Resource

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#f8fafc'}}}%%
graph TB
    subgraph "RBAC Overview"
        direction TB
        
        subgraph "Portal Admin (edunusa.console.edu.id)"
            SA["superadmin\n───────────────\n✅ User Management\n✅ Admin Management\n✅ Mentor Approval\n✅ Category CRUD\n✅ Course Moderation\n✅ Financial Reports\n✅ Audit Logs\n✅ System Config"]
            FIN["finance\n───────────────\n✅ View Transactions\n✅ Verify Bank Transfer\n✅ Approve Payout\n✅ Financial Reports\n❌ User Management\n❌ Course Management\n❌ System Config"]
            ACA["academic\n───────────────\n✅ Review Courses\n✅ Moderate Content\n✅ Category Management\n✅ View Enrollments\n❌ Financial Data\n❌ User Management\n❌ System Config"]
        end

        subgraph "Portal Mentor (teacher.edunusa.edu.id)"
            MEN["mentor\n───────────────\n✅ Create/Edit Own Courses\n✅ Manage Curriculum\n✅ View Own Students\n✅ Create Quizzes\n✅ View Own Earnings\n❌ Other Mentor Courses\n❌ Admin Functions\n❌ Financial Config"]
        end

        subgraph "Platform Publik (edunusa.edu.id)"
            STU["student\n───────────────\n✅ Browse Catalog\n✅ Purchase Courses\n✅ Access Enrolled Courses\n✅ Take Quizzes\n✅ Track Progress\n✅ View Certificates\n❌ Course Management\n❌ Admin Access"]
            PAR["parent\n───────────────\n✅ Browse Catalog\n✅ Purchase Courses\n✅ Monitor Child Progress\n✅ View Child Scores\n❌ Take Quizzes directly\n❌ Admin Access"]
            GEN["general\n───────────────\n✅ Browse Catalog\n✅ Purchase Courses\n✅ Access Enrolled Courses\n✅ Take Quizzes\n❌ Sertifikat Formal\n❌ Admin Access"]
            PEND["pending\n───────────────\n✅ Access /onboarding\n❌ All other endpoints\n(Redirected by Middleware)"]
        end
    end
```

**Matriks Detail per API Endpoint:**

| Endpoint | Public | `pending` | `student` | `parent` | `general` | `mentor` | `academic` | `finance` | `superadmin` |
|---|---|---|---|---|---|---|---|---|---|
| `GET /api/v1/categories` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `POST /api/v1/categories` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| `GET /api/v1/courses` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `POST /api/v1/courses` | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (own) | ❌ | ❌ | ✅ |
| `PUT /api/v1/courses/{id}` | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (own) | ❌ | ❌ | ✅ |
| `POST /api/v1/auth/onboarding` | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `POST /api/v1/transactions` | ❌ | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| `GET /api/v1/enrollments` | ❌ | ❌ | ✅ (own) | ✅ (own) | ✅ (own) | ❌ | ✅ | ✅ | ✅ |
| `GET /api/v1/admin/transactions` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| `POST /api/v1/admin/payout` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| `DELETE /api/v1/admin/users/{id}` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

### Middleware Validation Flow

```mermaid
flowchart TD
    START["Incoming Request\nke Protected Endpoint"]

    START --> T1{"JWT Header\nada?"}

    T1 -->|"Tidak"| E401A["401 Unauthorized\n{ status: 'error',\n  message: 'Token tidak ditemukan' }"]

    T1 -->|"Ya"| T2{"JWT Signature\nvalid?"}

    T2 -->|"Tidak / Tampered"| E401B["401 Unauthorized\n{ status: 'error',\n  message: 'Token tidak valid' }"]

    T2 -->|"Valid"| T3{"Token\nexpired?"}

    T3 -->|"Ya (exp < now)"| E401C["401 Unauthorized\n{ status: 'error',\n  message: 'Sesi telah berakhir,\n  silakan masuk kembali' }"]

    T3 -->|"Belum expired"| T4["Decode JWT Payload\nEkstrak: uid, role, email"]

    T4 --> T5{"Endpoint butuh\nrole spesifik?"}

    T5 -->|"Tidak (publik auth)"| ALLOW["Request dilanjutkan\nke Controller"]

    T5 -->|"Ya"| T6{"role dari JWT\ntermasuk allowed roles?"}

    T6 -->|"Ya"| T7{"role === 'pending'\ndan endpoint bukan\n/onboarding?"}

    T7 -->|"Ya"| E403B["403 Forbidden\n{ status: 'error',\n  message: 'Selesaikan onboarding\n  terlebih dahulu' }"]

    T7 -->|"Tidak"| ALLOW

    T6 -->|"Tidak"| E403A["403 Forbidden\n{ status: 'error',\n  message: 'Anda tidak memiliki\n  akses ke resource ini' }"]

    ALLOW --> CTRL["Controller Proses Request\n→ Teruskan ke e-learning-internal\n→ Kembalikan response"]

    classDef success fill:#dcfce7,stroke:#22c55e
    classDef error fill:#fee2e2,stroke:#ef4444
    classDef process fill:#dbeafe,stroke:#3b82f6
    classDef decision fill:#fef9c3,stroke:#eab308

    class ALLOW,CTRL success
    class E401A,E401B,E401C,E403A,E403B error
    class START,T4 process
    class T1,T2,T3,T5,T6,T7 decision
```

**Implementasi AuthFilter di CI4 (e-learning-api):**

```php
// app/Filters/JwtFilter.php — e-learning-api
<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Token tidak ditemukan']);
        }

        $token = substr($header, 7);

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $request->decoded_token = $decoded;

            // Cek role jika ada argument (e.g., ['superadmin', 'finance'])
            if ($arguments !== null) {
                $userRole = $decoded->role ?? '';
                if (!in_array($userRole, $arguments)) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke resource ini']);
                }
            }

            // Blokir role pending dari semua endpoint kecuali onboarding
            if (($decoded->role ?? '') === 'pending') {
                $path = $request->getPath();
                if (!str_contains($path, 'onboarding')) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON(['status' => 'error', 'message' => 'Selesaikan onboarding terlebih dahulu']);
                }
            }

        } catch (\Firebase\JWT\ExpiredException $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Sesi telah berakhir, silakan masuk kembali']);
        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Token tidak valid']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}
```

---

## Error Handling

### Standard Response Format

Seluruh endpoint di `e-learning-api` dan `e-learning-internal` WAJIB menggunakan format respons JSON berikut:

**Response Sukses:**
```json
{
  "status": "success",
  "message": "Pesan deskriptif aksi yang berhasil",
  "data": { },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

**Response Error:**
```json
{
  "status": "error",
  "message": "Pesan deskriptif kesalahan yang ramah pengguna",
  "errors": {
    "email": "Format email tidak valid",
    "password": "Password minimal 8 karakter"
  }
}
```

### HTTP Status Code Matrix

| Situasi | Status Code | Contoh |
|---|---|---|
| Request berhasil (read/update) | `200 OK` | Login sukses, data ditemukan |
| Resource baru berhasil dibuat | `201 Created` | Registrasi berhasil, kursus baru |
| Token tidak ada / invalid | `401 Unauthorized` | JWT expired, signature salah |
| Role tidak cukup | `403 Forbidden` | Student akses endpoint admin |
| Resource tidak ditemukan | `404 Not Found` | Email tidak terdaftar |
| Validasi input gagal | `422 Unprocessable Entity` | Format email salah, field kosong |
| MySQL tidak bisa dijangkau | `503 Service Unavailable` | Internal DB connection failed |
| Unhandled server error | `500 Internal Server Error` | Exception tidak tertangkap |

### Service Failure Cascade

```mermaid
flowchart LR
    FE["Frontend Request"] --> API["e-learning-api"]
    API --> INTERNAL["e-learning-internal"]
    INTERNAL --> DB[("MySQL")]

    DB -->|"Connection failed"| INTERNAL
    INTERNAL -->|"503 Service Unavailable\n+ log error ke writable/logs"| API
    API -->|"Propagate 503\n{ status: error, message:\n'Layanan sementara tidak tersedia' }"| FE

    note["⚠️ Stack trace PHP TIDAK PERNAH\ndikirim ke client di production.\nCI_ENVIRONMENT = production\nmemastikan ini secara otomatis."]
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Fase 1 melibatkan beberapa komponen logika yang dapat diverifikasi secara properti: UUID generation hook, soft-delete filter, strict entity separation, RBAC enforcement, dan JWT generation. Komponen-komponen ini memiliki input yang bervariasi dan perilaku universal yang dapat diuji.

### Property 1: UUID v4 Auto-Generation Always Valid

*For any* insert operation to any table in e-learning-internal that does not include an `id` field, the `beforeInsert` hook SHALL always generate an `id` value that is exactly 36 characters long and matches the UUID v4 format `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`.

**Validates: Requirements 2.1, 6.1, 6.2**

### Property 2: UUID Generation is Idempotent When ID Provided

*For any* valid UUID v4 string provided in an INSERT payload, the stored record's `id` SHALL equal the provided value exactly — the `beforeInsert` hook shall not override an explicitly supplied `id`.

**Validates: Requirements 6.3**

### Property 3: Strict Entity Separation (Cross-Table Email Independence)

*For any* email address string, if that email is inserted into the `users` table, it SHALL still be possible to insert the same email into the `admins` or `mentors` table. The UNIQUE constraint on `email` is scoped per-table, not cross-table.

**Validates: Requirements 3.1, 3.6, 3.7**

### Property 4: Soft Delete Excludes Records From All Reads

*For any* record in any table that has `deleted_at` set to a non-NULL value, the Internal Service's default GET endpoints SHALL never return that record in any response body.

**Validates: Requirements 4.6**

### Property 5: JWT Expiry is Always 86400 Seconds

*For any* successful login or onboarding completion, the issued JWT token's `exp` field SHALL always equal `iat + 86400` (exactly 24 hours), regardless of when the request is made.

**Validates: Requirements 5.3**

### Property 6: Invalid Credentials Produce Identical Error Messages

*For any* login attempt — whether the email does not exist or the email exists but the password is wrong — the API Gateway SHALL return HTTP 401 with an identical response body. The response MUST NOT reveal which part of the credentials was incorrect.

**Validates: Requirements 5.7**

### Property 7: Role Isolation — Non-Admin Roles Cannot Access Admin Endpoints

*For any* JWT token with role `student`, `parent`, or `general`, any request to an endpoint that requires role `superadmin`, `finance`, or `academic` SHALL return HTTP 403 Forbidden, regardless of which specific endpoint is targeted.

**Validates: Requirements 7.3**

### Property 8: Pending Role Blocked From All Non-Onboarding Endpoints

*For any* JWT token with `role: "pending"`, any request to any endpoint other than `/api/v1/auth/onboarding` SHALL return HTTP 403 Forbidden.

**Validates: Requirements 7.4**

---

## Testing Strategy

Fase 1 merupakan fondasi infrastruktur: Docker orchestration, database schema, dan microservice wiring. Tidak ada pure function yang berdiri sendiri dengan input/output yang murni — semua operasi melalui network I/O dan database. Oleh karena itu, **Property-Based Testing (PBT) tidak applicable** untuk fase ini.

Strategi pengujian yang tepat untuk Fase 1 adalah kombinasi **Smoke Tests**, **Integration Tests**, dan **Contract Tests**.

### Smoke Tests (Infrastruktur)

Memverifikasi bahwa seluruh kontainer berjalan dan dapat saling berkomunikasi setelah `docker compose up`:

```bash
# 1. Verifikasi semua container berjalan
docker compose ps | grep -E "(healthy|running)"

# 2. MySQL dapat dijangkau dari container internal
docker exec e-learning-internal curl -s http://localhost/health | jq .status

# 3. API Gateway dapat menjangkau Internal
docker exec e-learning-api curl -s http://internal/api/users | jq .status

# 4. Internal TIDAK dapat dijangkau dari luar network
curl -f http://localhost/api/users && echo "FAIL: Internal is exposed!" || echo "OK: Internal is not exposed"

# 5. Verifikasi seed data ada
docker exec e-learning-docker-mysql-1 mysql -u root -p elearning -e \
  "SELECT COUNT(*) FROM admins WHERE email='admin@edunusa.edu.id';"
```

### Integration Tests (API Contract)

Memverifikasi setiap alur API end-to-end dari API Gateway hingga database:

```bash
# Test: Registrasi → Login → Onboarding
BASE="http://localhost:8000/api/v1"

# 1. Registrasi
RESPONSE=$(curl -s -X POST "$BASE/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@test.com","password":"password123"}')
TOKEN=$(echo $RESPONSE | jq -r '.token')
echo "Register token role: $(echo $TOKEN | jwt decode - | jq -r '.role')"
# Expected: "pending"

# 2. Onboarding
USER_ID=$(echo $RESPONSE | jq -r '.user.id')
RESPONSE2=$(curl -s -X POST "$BASE/auth/onboarding" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"role\":\"student\"}")
TOKEN2=$(echo $RESPONSE2 | jq -r '.token')
echo "Onboarding token role: $(echo $TOKEN2 | jwt decode - | jq -r '.role')"
# Expected: "student"

# 3. Login
curl -s -X POST "$BASE/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123"}' | jq .
# Expected: 200 OK dengan token

# 4. Test endpoint pending role diblokir
curl -s -X GET "$BASE/courses" \
  -H "Authorization: Bearer $TOKEN" | jq .status
# Expected: 403 (pending role tidak bisa akses)
```

### Database Schema Tests

```sql
-- Test 1: UUID v4 auto-generation
INSERT INTO users (name, email, password) VALUES ('Test', 'test@example.com', 'hash');
SELECT id, LENGTH(id) as len FROM users WHERE email = 'test@example.com';
-- Expected: len = 36, id matches UUID v4 pattern

-- Test 2: Soft delete tidak muncul di query biasa
UPDATE users SET deleted_at = NOW() WHERE email = 'test@example.com';
SELECT COUNT(*) FROM users WHERE email = 'test@example.com' AND deleted_at IS NULL;
-- Expected: 0

-- Test 3: Unique constraint email per tabel (bukan lintas tabel)
INSERT INTO admins (id, name, email, password) 
VALUES (UUID(), 'Admin', 'test@example.com', 'hash');
-- Expected: Berhasil (email boleh sama di tabel berbeda = strict separation)

-- Test 4: Enrollment unique constraint
INSERT INTO enrollments (id, user_id, course_id) VALUES (UUID(), ?, ?);
INSERT INTO enrollments (id, user_id, course_id) VALUES (UUID(), ?, ?);
-- Expected: Kedua query sama → Error 1062 Duplicate entry (UNIQUE KEY)

-- Test 5: Cascade delete
DELETE FROM courses WHERE id = ?;
SELECT COUNT(*) FROM course_sections WHERE course_id = ?;
-- Expected: 0 (cascade bekerja)
```

### Non-Functional Requirements Checklist

| NFR | Target | Cara Verifikasi |
|---|---|---|
| Response time GET single record | < 200ms | `time curl http://internal/api/users/{id}` |
| Password tidak pernah plaintext di DB | N/A | `SELECT password FROM users` → harus dimulai dengan `$2y$` |
| JWT Secret tidak di source code | N/A | `grep -r "JWT_SECRET" --include="*.php"` → tidak ada value hardcode |
| Port MySQL tidak exposed | N/A | `docker compose ps` → port 3306 tidak di-bind ke 0.0.0.0 |
| CI_ENVIRONMENT production tidak leak stacktrace | N/A | Trigger 500 error → verifikasi response hanya pesan generik |
