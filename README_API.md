# AdminTopi RESTful API Service (Production Standard)

Layanan backend RESTful API skala produksi berbasis **Laravel 12** yang berfokus pada pengolahan data persisten (**Data Management**), dilengkapi sistem otentikasi token (**Laravel Sanctum**), validasi input yang terisolasi (**Isolated Form Requests**), pengamanan mutasi data (**Database Transactions, Concurrency Locking, & Policy Authorization**), fitur pencarian, penyaringan mendalam, serta pengunggahan dan manajemen berkas aman.

---

## 1. Konfigurasi Lingkungan & Database
- **DBMS**: MySQL
- **Database Name**: `AdminTopi`
- **Host / Port**: `127.0.0.1:3306`
- **User**: `root`
- **Password**: *(kosong)*

File konfigurasi `.env` telah disetel:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=AdminTopi
DB_USERNAME=root
DB_PASSWORD=
```

---

## 2. Arsitektur Backend & Fitur Utama

### A. Otentikasi Token (Laravel Sanctum)
- Otentikasi stateful/stateless token Bearer via header: `Authorization: Bearer <token>`.
- Token di-generate secara unik dan terhubung ke tabel `personal_access_tokens`.
- Fitur revocation mandiri (`logout`) dan penutupan seluruh sesi perangkat (`logout-all`).

### B. Validasi Input Terisolasi (Isolated Form Requests)
Setiap mutasi data divalidasi secara independen menggunakan dedicated Form Request di folder `app/Http/Requests`:
- `RegisterRequest`, `LoginRequest`
- `StoreCategoryRequest`, `UpdateCategoryRequest`
- `StoreProductRequest`, `UpdateProductRequest`
- `ProductFilterRequest`
- `UploadImageRequest`
- `AdjustStockRequest`
Semua request mewarisi `BaseFormRequest` yang meng-override `failedValidation` agar selalu mengembalikan respons format standar **422 Unprocessable Entity**:
```json
{
  "success": false,
  "message": "Validasi gagal. Periksa kembali input yang Anda kirimkan.",
  "errors": {
    "sku": ["SKU produk sudah digunakan. Gunakan kode SKU yang unik."]
  }
}
```

### C. Pengamanan Mutasi Data (Data Mutation Security)
1. **Database Transactions (`DB::transaction`)**:
   Setiap operasi simpan, ubah, mutasi stok, dan hapus dibungkus dalam transaksi database untuk menjamin integritas data (ACID).
2. **Pessimistic Concurrency Lock (`lockForUpdate`)**:
   Pada penyesuaian persediaan stok (`adjust-stock`), baris produk dikunci saat transaksi berlangsung untuk mencegah *race conditions* pada sistem dengan konkurensi tinggi.
3. **Audit Trail Mutasi Persediaan (`stock_movements`)**:
   Setiap perubahan stok dicatat secara permanen dan *immutable* ke tabel `stock_movements`, mencakup tipe mutasi (`in`, `out`, `adjustment`), kuantitas, stok sebelum & sesudah, pengguna yang melakukan, dan catatan audit.
4. **Proteksi Mass Assignment & Soft Deletes**:
   Semua model menggunakan array `$fillable` eksplisit dan `SoftDeletes` untuk mencegah kehilangan data krusial.
5. **Kebijakan Akses (Laravel Policies)**:
   - `ProductPolicy`: Hanya Admin atau pembuat produk yang dapat mengubah/menghapus produk.
   - `CategoryPolicy`: Hanya Admin yang dapat memutasi data kategori.
   - Pengecekan relasi: Kategori tidak dapat dihapus jika masih menaungi produk aktif.

### D. Pencarian, Penyaringan, & Pengurutan (Search, Filter, & Sort)
Endpoint `GET /api/v1/products` mendukung parameter query:
- `search`: Pencarian multi-kolom (*case-insensitive*) pada nama, SKU, deskripsi, dan material.
- `category_id`: Menyaring produk berdasarkan ID kategori topi.
- `status`: Filter status (`draft`, `published`, `archived`).
- `min_price` & `max_price`: Filter batas harga produk.
- `in_stock`: Filter ketersediaan stok (`true` = stok > 0, `false` = stok habis).
- `sort_by`: Pengurutan berdasarkan `id`, `name`, `price`, `stock`, `created_at`, atau `status`.
- `sort_order`: Arah pengurutan (`asc` atau `desc`).
- `per_page`: Jumlah data per halaman (default 15).

### E. Pengunggahan Berkas Aman (File Upload)
- Validasi ketat MIME type (`image/jpeg`, `image/png`, `image/webp`) dan ukuran berkas (maksimal 5MB untuk gambar, 10MB untuk dokumen/techpack).
- Berkas disimpan di disk `public` (`storage/app/public/products/...`) dan dapat diakses publik via symlink `public/storage`.
- Manajemen otomatis: Berkas lama otomatis dihapus dari penyimpanan fisik saat pengguna memperbarui gambar untuk mencegah file yatim (*orphaned files*).

---

## 3. Akun Pengujian Bawaan (Database Seeder)
Database telah di-seed dengan akun dan data realistis:

| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@admintopi.com` | `Password123!` |
| **Staff** | `staff@admintopi.com` | `Password123!` |

---

## 4. Daftar Rute API (`/api/v1`)

### Otentikasi (`/auth`)
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| `POST` | `/api/v1/auth/register` | Publik | Registrasi akun staff/admin baru |
| `POST` | `/api/v1/auth/login` | Publik | Otentikasi dan penerbitan token Bearer |
| `GET` | `/api/v1/auth/me` | Bearer Token | Mengambil profil user yang sedang login |
| `POST` | `/api/v1/auth/logout` | Bearer Token | Mencabut token sesi aktif |
| `POST` | `/api/v1/auth/logout-all` | Bearer Token | Mencabut seluruh sesi token user di semua perangkat |

### Kategori Topi (`/categories`)
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| `GET` | `/api/v1/categories` | Publik | Daftar kategori beserta jumlah produk |
| `GET` | `/api/v1/categories/{id}` | Publik | Detail kategori spesifik |
| `POST` | `/api/v1/categories` | Admin | Menambah kategori baru |
| `PUT/PATCH` | `/api/v1/categories/{id}` | Admin | Mengubah data kategori |
| `DELETE` | `/api/v1/categories/{id}` | Admin | Menghapus kategori (dilindungi relasi produk) |

### Produk Topi (`/products`)
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| `GET` | `/api/v1/products` | Publik | Pencarian, penyaringan, sorting, pagination |
| `GET` | `/api/v1/products/{id}` | Publik | Detail produk, kategori, creator, & URL gambar |
| `POST` | `/api/v1/products` | Admin / Staff | Mendaftarkan produk baru (support multipart upload) |
| `PUT/PATCH` | `/api/v1/products/{id}` | Owner / Admin | Memperbarui data produk & ganti berkas |
| `DELETE` | `/api/v1/products/{id}` | Owner / Admin | Soft delete produk |
| `POST` | `/api/v1/products/{id}/upload-image` | Owner / Admin | Unggah/ganti berkas gambar produk |
| `POST` | `/api/v1/products/{id}/adjust-stock` | Admin / Staff | Mutasi stok aman persediaan (`lockForUpdate`) |
| `GET` | `/api/v1/products/{id}/stock-movements` | Admin / Staff | Riwayat jejak audit (*audit trail*) stok produk |

---

## 5. Cara Menjalankan Layanan & Pengujian

### Menjalankan Server API Lokal:
```bash
php artisan serve
```
Layanan akan aktif di: `http://127.0.0.1:8000`

### Menjalankan Seluruh Automated Feature Tests:
```bash
php artisan test
```
*Hasil pengujian: 21 tests, 87 assertions passed 100%.*

### Menggunakan Koleksi Postman:
Import berkas [`AdminTopi_API_Postman_Collection.json`](file:///c:/RestfullAPI/AdminTopi_API_Postman_Collection.json) ke dalam aplikasi Postman / Insomnia untuk langsung mencoba semua endpoint yang tersedia.
