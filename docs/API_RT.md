# API Documentation - RT Database

## Overview

API untuk mengakses data RT1 - RT15. Semua endpoint memerlukan Bearer Token.

**Base URL:** `/api/rt/{rt}/`

**Contoh:** `/api/rt/9/dashboard` untuk akses RT9

---

## Endpoints

### 1. Dashboard

```http
GET /api/rt/{rt}/dashboard
```

**Response:**
```json
{
    "success": true,
    "message": "Dashboard RT9",
    "data": {
        "rt": 9,
        "houses": {
            "total": 150,
            "occupied": 120,
            "empty": 30
        },
        "balance": {
            "main": 15000000,
            "pkk": 5000000,
            "total": 20000000
        },
        "unpaid_bills": {
            "ipl": 25,
            "cash": 10,
            "pkk": 5,
            "total": 40
        },
        "pending": {
            "complaints": 3,
            "suggestions": 2
        },
        "active_votes": 1
    }
}
```

---

### 2. Houses (Rumah)

#### List Rumah

```http
GET /api/rt/{rt}/houses
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `block` | string | Filter blok (A, B, C) |
| `occupied` | 0/1 | Filter terisi/kosong |
| `search` | string | Cari nama pemilik/penghuni |
| `per_page` | int | Default: 15 |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "block": "A",
            "no": 1,
            "occupied": true,
            "occupied_by_owner": true,
            "pay_ipl": true,
            "ipl_amount": 150000,
            "pay_cash": true,
            "cash_amount": 50000,
            "pay_pkk": true,
            "pkk_amount": 25000,
            "owner": {
                "id": 1,
                "name": "Budi Santoso",
                "handphone": "08123456789",
                "kk": "3201234567890001",
                "sum_family": "K2",
                "religion": "ISLAM"
            },
            "occupant": null
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 15,
        "per_page": 10,
        "total": 150
    }
}
```

#### Detail Rumah

```http
GET /api/rt/{rt}/houses/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "block": "A",
        "no": 1,
        "full_address": "Blok A No. 1",
        "occupied": true,
        "owner": {
            "id": 1,
            "name": "Budi Santoso",
            "handphone": "08123456789"
        },
        "occupant": null,
        "bills_ipl": [
            {
                "id": 120,
                "year_bill": 2026,
                "month_bill": 3,
                "amount": 150000,
                "status": "UNPAID"
            }
        ],
        "bills_cash": [...],
        "bills_pkk": [...]
    }
}
```

#### Ringkasan Tagihan per Rumah

```http
GET /api/rt/{rt}/houses/{id}/bills
```

**Response:**
```json
{
    "success": true,
    "data": {
        "house": {
            "id": 1,
            "address": "Blok A No. 1"
        },
        "ipl": {
            "unpaid_count": 2,
            "unpaid_total": 300000,
            "bills": [...]
        },
        "cash": {
            "unpaid_count": 1,
            "unpaid_total": 50000,
            "bills": [...]
        },
        "pkk": {
            "unpaid_count": 0,
            "unpaid_total": 0,
            "bills": [...]
        }
    }
}
```

---

### 3. Bills (Tagihan)

#### Tagihan IPL

```http
GET /api/rt/{rt}/bills/ipl
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `year` | int | Tahun tagihan |
| `month` | int | Bulan (1-12) |
| `status` | string | `unpaid`, `pending`, `paid` |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 120,
            "house_id": 1,
            "year_bill": 2026,
            "month_bill": 3,
            "amount": 150000,
            "status": "UNPAID",
            "house": {
                "id": 1,
                "block": "A",
                "no": 1
            }
        }
    ],
    "meta": {...}
}
```

#### Tagihan Kas & PKK

```http
GET /api/rt/{rt}/bills/cash
GET /api/rt/{rt}/bills/pkk
```

*(Parameter & response sama dengan IPL)*

---

### 4. Transactions

```http
GET /api/rt/{rt}/transactions
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `category` | string | `utama` / `pkk` |
| `mutation` | string | `debit` / `kredit` |
| `start_date` | date | YYYY-MM-DD |
| `end_date` | date | YYYY-MM-DD |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1050,
            "category": "UTAMA",
            "type": "IPL",
            "id_settlement": "TRX-20260303-001",
            "notes": "Pembayaran IPL Blok A No. 1",
            "amount": 150000,
            "mutation": "DEBIT",
            "image": null,
            "created_by": "admin",
            "created_at": "2026-03-03T10:30:00.000000Z"
        }
    ],
    "meta": {...}
}
```

---

### 5. Balance (Saldo)

```http
GET /api/rt/{rt}/balance
```

**Response:**
```json
{
    "success": true,
    "data": {
        "balance": 15000000,
        "balance_pkk": 5000000,
        "total": 20000000,
        "last_updated": "2026-03-03T10:30:00.000000Z"
    }
}
```

---

### 6. Votes (Voting)

#### List Voting

```http
GET /api/rt/{rt}/votes
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `active_only` | bool | Hanya yang aktif |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "title": "Pemilihan Ketua RT",
            "description": "Pilih calon ketua RT",
            "start_vote": "2026-03-01T00:00:00.000000Z",
            "end_vote": "2026-03-15T23:59:59.000000Z",
            "is_active": true,
            "is_multi_choice": false,
            "answers_count": 85,
            "options": [
                {"id": 10, "option_name": "Pak Budi", "option_desc": "Incumbent"},
                {"id": 11, "option_name": "Pak Ahmad", "option_desc": "Sekretaris"}
            ]
        }
    ]
}
```

#### Detail Voting + Hasil

```http
GET /api/rt/{rt}/votes/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "vote": {
            "id": 5,
            "title": "Pemilihan Ketua RT",
            "start_vote": "2026-03-01T00:00:00.000000Z",
            "end_vote": "2026-03-15T23:59:59.000000Z",
            "is_active": true
        },
        "total_votes": 85,
        "results": [
            {"id": 10, "option_name": "Pak Budi", "vote_count": 50, "percentage": 58.82},
            {"id": 11, "option_name": "Pak Ahmad", "vote_count": 35, "percentage": 41.18}
        ]
    }
}
```

---

### 7. Information (Pengumuman)

```http
GET /api/rt/{rt}/information
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "banner": "banners/gotong-royong.jpg",
            "title": "Gotong Royong Minggu Ini",
            "content": "Akan diadakan gotong royong...",
            "is_pin": true,
            "created_at": "2026-03-01T08:00:00.000000Z"
        }
    ]
}
```

---

### 8. Contributions (Iuran/Donasi)

```http
GET /api/rt/{rt}/contributions
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `active_only` | bool | Hanya yang aktif |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 3,
            "banner": "contributions/qurban.jpg",
            "name": "Iuran Qurban 2026",
            "description": "Pengumpulan dana qurban",
            "goals": 50000000,
            "min_amount": 100000,
            "start_date": "2026-03-01T00:00:00.000000Z",
            "end_date": "2026-06-01T23:59:59.000000Z",
            "is_closed": false,
            "total_collected": 15750000,
            "progress_percentage": 31.5,
            "transactions_count": 45
        }
    ]
}
```

---

### 9. Complaints (Keluhan)

```http
GET /api/rt/{rt}/complaints
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `is_read` | 0/1 | Sudah/belum dibaca |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 25,
            "complaint": "Lampu jalan mati sudah 3 hari",
            "image": "complaints/lampu-mati.jpg",
            "is_read": false,
            "created_at": "2026-03-02T20:15:00.000000Z",
            "house": {"id": 15, "block": "B", "no": 5}
        }
    ]
}
```

---

### 10. Suggestions (Saran)

```http
GET /api/rt/{rt}/suggestions
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `is_read` | 0/1 | Sudah/belum dibaca |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 12,
            "suggestion": "Adakan kegiatan olahraga bersama",
            "is_read": true,
            "created_at": "2026-02-25T09:00:00.000000Z",
            "house": {"id": 8, "block": "A", "no": 8}
        }
    ]
}
```

---

## Frontend - Sidebar Context

```javascript
// State context
const [context, setContext] = useState({ type: 'rw', rtId: null });

// Switch ke RT
const switchToRT = (rtId) => setContext({ type: 'rt', rtId });

// Switch ke RW
const switchToRW = () => setContext({ type: 'rw', rtId: null });

// Build API URL
const getApiUrl = (endpoint) => {
    if (context.type === 'rw') return `/api/${endpoint}`;
    return `/api/rt/${context.rtId}/${endpoint}`;
};

// Menu berdasarkan context
const menu = context.type === 'rw'
    ? ['Pengurus', 'Info RW', 'Gallery', 'Berita', 'Dokumen', 'Assets']
    : ['Dashboard', 'Rumah', 'Tagihan', 'Transaksi', 'Saldo', 'Voting', 'Informasi', 'Iuran', 'Keluhan', 'Saran'];
```

---

## Quick Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/rt/{rt}/dashboard` | GET | Dashboard summary |
| `/rt/{rt}/houses` | GET | List rumah |
| `/rt/{rt}/houses/{id}` | GET | Detail rumah |
| `/rt/{rt}/houses/{id}/bills` | GET | Tagihan per rumah |
| `/rt/{rt}/bills/ipl` | GET | Tagihan IPL |
| `/rt/{rt}/bills/cash` | GET | Tagihan Kas |
| `/rt/{rt}/bills/pkk` | GET | Tagihan PKK |
| `/rt/{rt}/transactions` | GET | Transaksi |
| `/rt/{rt}/balance` | GET | Saldo |
| `/rt/{rt}/votes` | GET | List voting |
| `/rt/{rt}/votes/{id}` | GET | Detail voting |
| `/rt/{rt}/information` | GET | Pengumuman |
| `/rt/{rt}/contributions` | GET | Iuran/donasi |
| `/rt/{rt}/complaints` | GET | Keluhan |
| `/rt/{rt}/suggestions` | GET | Saran |
