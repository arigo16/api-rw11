# API Admin - Users

Dokumentasi API untuk pengelolaan user admin panel.

**Base URL:** `{base_url}/api`

**Authentication:** Semua endpoint memerlukan token Bearer (Sanctum)

```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Get All Users

Mengambil daftar semua user dengan filter dan pagination.

**Endpoint:** `GET /users`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Search by name atau email |
| all | string | Set `true` untuk get semua data tanpa pagination |
| per_page | integer | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data user berhasil diambil",
  "data": [
    {
      "id": 1,
      "name": "Arigo",
      "email": "arigo1602@gmail.com",
      "email_verified_at": null,
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

### 2. Get Single User

Mengambil detail user.

**Endpoint:** `GET /users/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Detail user berhasil diambil",
  "data": {
    "id": 1,
    "name": "Arigo",
    "email": "arigo1602@gmail.com",
    "email_verified_at": null,
    "created_at": "2026-04-26T08:00:00.000000Z",
    "updated_at": "2026-04-26T08:00:00.000000Z"
  }
}
```

**Response Not Found (404):**
```json
{
  "success": false,
  "message": "User dengan ID {id} tidak ditemukan"
}
```

---

### 3. Create User

Menambahkan user baru.

**Endpoint:** `POST /users`

**Request Body (JSON):**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| email | required, email, unique |
| password | required, string, min:6 |

**Response Success (201):**
```json
{
  "success": true,
  "message": "User berhasil ditambahkan",
  "data": {
    "id": 2,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-04-26T10:00:00.000000Z",
    "updated_at": "2026-04-26T10:00:00.000000Z"
  }
}
```

**Response Validation Error (422):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### 4. Update User

Mengubah data user.

**Endpoint:** `PUT /users/{id}`

**Request Body (JSON):**
```json
{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "password": "newpassword123"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| name | optional, string, max:255 |
| email | optional, email, unique (except current user) |
| password | optional, string, min:6 |

**Note:** Semua field optional, hanya kirim field yang ingin diubah.

**Response Success (200):**
```json
{
  "success": true,
  "message": "User berhasil diperbarui",
  "data": {
    "id": 2,
    "name": "John Updated",
    "email": "john.updated@example.com",
    "created_at": "2026-04-26T10:00:00.000000Z",
    "updated_at": "2026-04-26T10:30:00.000000Z"
  }
}
```

---

### 5. Delete User

Menghapus user.

**Endpoint:** `DELETE /users/{id}`

**Note:** User tidak dapat menghapus akun sendiri.

**Response Success (200):**
```json
{
  "success": true,
  "message": "User berhasil dihapus",
  "data": null
}
```

**Response Error - Self Delete (422):**
```json
{
  "success": false,
  "message": "Tidak dapat menghapus akun sendiri"
}
```

**Response Not Found (404):**
```json
{
  "success": false,
  "message": "User dengan ID {id} tidak ditemukan"
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

---

## Contoh Implementasi Frontend

### List Users
```javascript
const response = await fetch('/api/users', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

### Create User
```javascript
const response = await fetch('/api/users', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com',
    password: 'password123'
  })
});
const data = await response.json();
```

### Update User
```javascript
const response = await fetch(`/api/users/${userId}`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'Updated Name'
  })
});
const data = await response.json();
```

### Delete User
```javascript
const response = await fetch(`/api/users/${userId}`, {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```
