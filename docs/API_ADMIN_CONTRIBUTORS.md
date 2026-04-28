# API Admin - Contributors & Tagihan Iuran Rutin

Dokumentasi API untuk pengelolaan contributor (warga/unit yang membayar iuran rutin) dan tagihan bulanan.

**Base URL:** `{base_url}/api`

**Authentication:** Semua endpoint memerlukan token Bearer (Sanctum)

```
Authorization: Bearer {token}
```

---

## Contributors (Iuran Rutin)

### 1. Get All Contributors

Mengambil daftar semua contributor dengan filter dan pagination.

**Endpoint:** `GET /contributors`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| type | string | Filter by type: `RT`, `RUKO`, `LAINNYA` |
| is_active | boolean | Filter by active status: `true` atau `false` |
| search | string | Search by code or name |
| all | boolean | Set `true` untuk get semua data tanpa pagination |
| per_page | integer | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data contributor berhasil diambil",
  "data": [
    {
      "id": 1,
      "code": "RT-02",
      "name": "RT 02",
      "type": "RT",
      "amount": "150000.00",
      "is_active": true,
      "start_month": 1,
      "start_year": 2026,
      "notes": null,
      "created_at": "2026-04-26T08:00:00.000000Z",
      "updated_at": "2026-04-26T08:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 2. Get Single Contributor

Mengambil detail contributor beserta daftar tagihan.

**Endpoint:** `GET /contributors/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Detail contributor berhasil diambil",
  "data": {
    "id": 1,
    "code": "RT-02",
    "name": "RT 02",
    "type": "RT",
    "amount": "150000.00",
    "is_active": true,
    "start_month": 1,
    "start_year": 2026,
    "notes": null,
    "created_at": "2026-04-26T08:00:00.000000Z",
    "updated_at": "2026-04-26T08:00:00.000000Z",
    "bills": [
      {
        "id": 1,
        "contributor_id": 1,
        "year_bill": 2026,
        "month_bill": 4,
        "amount": "150000.00",
        "status": "UNPAID",
        "transaction_id": null,
        "paid_at": null,
        "paid_by": null,
        "notes": null
      }
    ]
  }
}
```

**Response Not Found (404):**
```json
{
  "success": false,
  "message": "Contributor dengan ID {id} tidak ditemukan"
}
```

---

### 3. Create Contributor

Menambahkan contributor baru.

**Endpoint:** `POST /contributors`

**Request Body (JSON):**
```json
{
  "code": "RT-02",
  "name": "RT 02",
  "type": "RT",
  "amount": 150000,
  "is_active": true,
  "start_month": 1,
  "start_year": 2026,
  "notes": "Iuran bulanan RT 02"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| code | required, string, max:50, unique |
| name | required, string, max:100 |
| type | required, enum: `RT`, `RUKO`, `LAINNYA` |
| amount | required, numeric, min:0 |
| is_active | optional, boolean (default: true) |
| start_month | optional, integer, 1-12 |
| start_year | optional, integer, 2020-2100 |
| notes | optional, string, max:500 |

**Response Success (201):**
```json
{
  "success": true,
  "message": "Contributor berhasil ditambahkan",
  "data": {
    "id": 1,
    "code": "RT-02",
    "name": "RT 02",
    "type": "RT",
    "amount": "150000.00",
    "is_active": true,
    "start_month": 1,
    "start_year": 2026,
    "notes": "Iuran bulanan RT 02",
    "created_at": "2026-04-26T08:00:00.000000Z",
    "updated_at": "2026-04-26T08:00:00.000000Z"
  }
}
```

---

### 4. Update Contributor

Mengubah data contributor.

**Endpoint:** `PUT /contributors/{id}`

**Request Body (JSON):**
```json
{
  "name": "RT 02 - Blok A",
  "amount": 175000
}
```

**Validation Rules:** Sama seperti create, tapi semua field optional (`sometimes`).

**Response Success (200):**
```json
{
  "success": true,
  "message": "Contributor berhasil diperbarui",
  "data": {
    "id": 1,
    "code": "RT-02",
    "name": "RT 02 - Blok A",
    "type": "RT",
    "amount": "175000.00",
    ...
  }
}
```

---

### 5. Delete Contributor

Menghapus contributor.

**Endpoint:** `DELETE /contributors/{id}`

**Note:** Contributor tidak dapat dihapus jika memiliki tagihan yang sudah dibayar (PAID). Tagihan yang belum dibayar (UNPAID) akan ikut terhapus.

**Response Success (200):**
```json
{
  "success": true,
  "message": "Contributor berhasil dihapus",
  "data": null
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Contributor tidak dapat dihapus karena memiliki tagihan yang sudah dibayar"
}
```

---

## Contributor Bills (Tagihan Iuran)

### 1. Get All Bills

Mengambil daftar tagihan dengan filter.

**Endpoint:** `GET /contributor-bills`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| contributor_id | integer | Filter by contributor |
| status | string | Filter by status: `PAID` atau `UNPAID` |
| year | integer | Filter by tahun tagihan |
| month | integer | Filter by bulan tagihan (1-12) |
| type | string | Filter by contributor type: `RT`, `RUKO`, `LAINNYA` |
| per_page | integer | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data tagihan berhasil diambil",
  "data": [
    {
      "id": 1,
      "contributor_id": 1,
      "year_bill": 2026,
      "month_bill": 4,
      "amount": "150000.00",
      "status": "UNPAID",
      "transaction_id": null,
      "paid_at": null,
      "paid_by": null,
      "notes": null,
      "created_at": "2026-04-26T08:00:00.000000Z",
      "updated_at": "2026-04-26T08:00:00.000000Z",
      "contributor": {
        "id": 1,
        "code": "RT-02",
        "name": "RT 02",
        "type": "RT",
        ...
      },
      "paid_by_user": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 2. Get Single Bill

Mengambil detail tagihan.

**Endpoint:** `GET /contributor-bills/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Detail tagihan berhasil diambil",
  "data": {
    "id": 1,
    "contributor_id": 1,
    "year_bill": 2026,
    "month_bill": 4,
    "amount": "150000.00",
    "status": "PAID",
    "transaction_id": 5,
    "paid_at": "2026-04-26T10:30:00.000000Z",
    "paid_by": 1,
    "notes": "Dibayar tunai",
    "contributor": {...},
    "transaction": {
      "id": 5,
      "id_settlement": "TRX-202604-0005",
      "mutation": "IN",
      "amount": "150000.00",
      ...
    },
    "paid_by_user": {
      "id": 1,
      "name": "Admin"
    }
  }
}
```

---

### 3. Generate Bills

Generate tagihan untuk periode tertentu. Tagihan akan dibuat untuk semua contributor aktif yang memenuhi syarat (start_month dan start_year sudah berlaku).

**Endpoint:** `POST /contributor-bills/generate`

**Request Body (JSON):**
```json
{
  "year": 2026,
  "month": 4,
  "contributor_ids": [1, 2, 3]
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| year | required, integer, 2020-2100 |
| month | required, integer, 1-12 |
| contributor_ids | optional, array of contributor IDs |

**Note:**
- Jika `contributor_ids` tidak diisi, akan generate untuk semua contributor aktif yang eligible.
- Tagihan yang sudah ada untuk periode tersebut akan di-skip.

**Response Success (200):**
```json
{
  "success": true,
  "message": "Berhasil generate 5 tagihan untuk April 2026",
  "data": {
    "period": "April 2026",
    "created": 5,
    "skipped": 2
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Tidak ada contributor yang eligible untuk periode ini"
}
```

---

### 4. Pay Bill

Mencatat pembayaran tagihan. Otomatis membuat transaksi dan update saldo.

**Endpoint:** `POST /contributor-bills/{id}/pay`

**Content-Type:** `multipart/form-data`

**Request Body (form-data):**
| Field | Type | Description |
|-------|------|-------------|
| paid_at | string | Tanggal pembayaran (format: YYYY-MM-DD), default: now |
| notes | string | Catatan pembayaran |
| attachment | file | Bukti transfer (jpg, jpeg, png, pdf, max 5MB) |

**Validation Rules:**
| Field | Rules |
|-------|-------|
| paid_at | optional, date (default: now) |
| notes | optional, string, max:500 |
| attachment | optional, file, mimes:jpg,jpeg,png,pdf, max:5120KB |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Pembayaran berhasil dicatat",
  "data": {
    "id": 1,
    "contributor_id": 1,
    "year_bill": 2026,
    "month_bill": 4,
    "amount": "150000.00",
    "status": "PAID",
    "transaction_id": 5,
    "paid_at": "2026-04-26T00:00:00.000000Z",
    "paid_by": 1,
    "notes": "Dibayar tunai",
    "contributor": {...},
    "transaction": {
      "id": 5,
      "id_settlement": "TRX-202604-0005",
      "mutation": "IN",
      "type_id": 1,
      "amount": "150000.00",
      "balance_before": "1000000.00",
      "balance_after": "1150000.00",
      "description": "Iuran RT 02 - April 2026",
      "attachment": "transactions/TRX-202604-0005_1714123456.jpg",
      ...
    },
    "paid_by_user": {
      "id": 1,
      "name": "Admin"
    }
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Tagihan sudah dibayar"
}
```

---

### 5. Unpay Bill

Membatalkan pembayaran tagihan. Menghapus transaksi terkait dan mengembalikan saldo.

**Endpoint:** `POST /contributor-bills/{id}/unpay`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Status pembayaran berhasil dibatalkan",
  "data": {
    "id": 1,
    "contributor_id": 1,
    "year_bill": 2026,
    "month_bill": 4,
    "amount": "150000.00",
    "status": "UNPAID",
    "transaction_id": null,
    "paid_at": null,
    "paid_by": null,
    "contributor": {...}
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Tagihan belum dibayar"
}
```

---

### 6. Delete Bill

Menghapus tagihan (hanya tagihan UNPAID yang bisa dihapus).

**Endpoint:** `DELETE /contributor-bills/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Tagihan berhasil dihapus",
  "data": null
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Tagihan yang sudah dibayar tidak dapat dihapus"
}
```

---

### 7. Bills Summary

Mengambil ringkasan tagihan per bulan dalam satu tahun.

**Endpoint:** `GET /contributor-bills/summary`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| year | integer | Tahun (default: tahun sekarang) |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Ringkasan tagihan berhasil diambil",
  "data": {
    "year": 2026,
    "months": [
      {
        "month": 1,
        "month_name": "Januari",
        "total_bills": 10,
        "paid_count": 8,
        "unpaid_count": 2,
        "total_amount": 1500000,
        "paid_amount": 1200000,
        "unpaid_amount": 300000
      },
      {
        "month": 2,
        "month_name": "Februari",
        "total_bills": 10,
        "paid_count": 10,
        "unpaid_count": 0,
        "total_amount": 1500000,
        "paid_amount": 1500000,
        "unpaid_amount": 0
      }
    ],
    "totals": {
      "total_bills": 20,
      "paid_count": 18,
      "unpaid_count": 2,
      "total_amount": 3000000,
      "paid_amount": 2700000,
      "unpaid_amount": 300000
    }
  }
}
```

---

## Workflow Penggunaan

### Setup Awal

1. **Tambah Contributor**
   ```
   POST /contributors
   {
     "code": "RT-02",
     "name": "RT 02",
     "type": "RT",
     "amount": 150000,
     "start_month": 1,
     "start_year": 2026
   }
   ```

2. **Generate Tagihan Bulanan**
   ```
   POST /contributor-bills/generate
   {
     "year": 2026,
     "month": 4
   }
   ```

### Pencatatan Pembayaran

1. **Lihat Tagihan Belum Bayar**
   ```
   GET /contributor-bills?status=UNPAID&year=2026&month=4
   ```

2. **Bayar Tagihan**
   ```
   POST /contributor-bills/{id}/pay
   {
     "paid_at": "2026-04-26",
     "notes": "Dibayar tunai"
   }
   ```

   Sistem akan otomatis:
   - Membuat transaksi dengan tipe "Iuran Warga" (type_id: 1)
   - Update saldo kas
   - Update status tagihan menjadi PAID

### Pembatalan Pembayaran

```
POST /contributor-bills/{id}/unpay
```

Sistem akan otomatis:
- Menghapus transaksi terkait (soft delete)
- Mengembalikan saldo kas
- Update status tagihan menjadi UNPAID

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "code": ["The code field is required."],
    "type": ["The selected type is invalid."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Contributor dengan ID {id} tidak ditemukan"
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Gagal memproses pembayaran: [error message]"
}
```
