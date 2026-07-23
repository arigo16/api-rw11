# API Public Assets - Dokumentasi Frontend

API untuk mengakses daftar aset RW yang dapat digunakan di landing page tanpa autentikasi.

## Base URL

```
https://your-domain.com/api/public
```

---

## Endpoints

### 1. Mendapatkan Daftar Aset

Mendapatkan daftar semua aset dengan dukungan filtering, pencarian, dan pagination.

**Endpoint:**
```
GET /assets
```

**Query Parameters:**

| Parameter | Type | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `page` | integer | No | 1 | Nomor halaman untuk pagination |
| `per_page` | integer | No | 15 | Jumlah data per halaman |
| `kategori` | string | No | - | Filter berdasarkan kategori aset |
| `kondisi` | string | No | - | Filter berdasarkan kondisi: `baik`, `rusak_ringan`, `rusak_berat` |
| `search` | string | No | - | Pencarian berdasarkan nama aset |

**Response Format:**

```json
{
  "success": true,
  "message": "Data assets retrieved successfully",
  "data": [
    {
      "id": 1,
      "nama": "Kursi Lipat",
      "kategori": "Peralatan",
      "kondisi": "baik",
      "deskripsi": "Kursi lipat untuk kegiatan warga",
      "foto": "assets/1234567890_kursi.jpg",
      "jumlah": 50,
      "tanggal_perolehan": "2024-01-15",
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 45
  }
}
```

---

### 2. Mendapatkan Detail Aset

**Endpoint:**
```
GET /assets/{id}
```

**Response Format:**

```json
{
  "success": true,
  "message": "Data asset retrieved successfully",
  "data": {
    "id": 1,
    "nama": "Kursi Lipat",
    "kategori": "Peralatan",
    "kondisi": "baik",
    "deskripsi": "Kursi lipat untuk kegiatan warga",
    "foto": "assets/1234567890_kursi.jpg",
    "jumlah": 50,
    "tanggal_perolehan": "2024-01-15"
  }
}
```

---

## Contoh Implementasi Frontend

### Vanilla JavaScript (Fetch API)

```javascript
// Mendapatkan daftar aset
async function getAssets(params = {}) {
  try {
    const queryString = new URLSearchParams(params).toString();
    const url = `https://your-domain.com/api/public/assets${queryString ? '?' + queryString : ''}`;

    const response = await fetch(url);
    const result = await response.json();

    if (result.success) {
      return result.data;
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Contoh penggunaan
const assets = await getAssets({ kategori: 'Peralatan', per_page: 10 });
```

### Axios

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://your-domain.com/api/public'
});

async function getAssets(params = {}) {
  const response = await api.get('/assets', { params });
  return response.data.data;
}

async function getAssetDetail(id) {
  const response = await api.get(`/assets/${id}`);
  return response.data.data;
}
```

### React Hook

```javascript
import { useState, useEffect } from 'react';

export function useAssets(params = {}) {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`https://your-domain.com/api/public/assets?${new URLSearchParams(params)}`)
      .then(res => res.json())
      .then(result => {
        setData(result.data);
        setLoading(false);
      });
  }, [JSON.stringify(params)]);

  return { data, loading };
}
```

---

## Tips Implementasi

### Menampilkan Foto Aset

```javascript
const imageUrl = asset.foto
  ? `https://your-domain.com/storage/${asset.foto}`
  : '/images/placeholder.jpg';
```

### Format Tanggal

```javascript
const formattedDate = new Date(asset.tanggal_perolehan)
  .toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
```

### Badge Kondisi

```javascript
const kondisiStyle = {
  'baik': 'badge-success',
  'rusak_ringan': 'badge-warning',
  'rusak_berat': 'badge-danger'
};
```

---

## Testing dengan cURL

```bash
# Get all assets
curl "http://localhost:8001/api/public/assets"

# With filters
curl "http://localhost:8001/api/public/assets?kategori=Peralatan&kondisi=baik"

# Get detail
curl "http://localhost:8001/api/public/assets/1"
```

---

**Last Updated:** 2024
