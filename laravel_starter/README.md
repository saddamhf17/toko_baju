# Laravel Starter Snippets (Toko Baju)

Folder ini berisi **kode siap pakai** untuk mempercepat implementasi:

- Struktur database lengkap (migration).
- Model relasi inti (product, variant, transaction, payment).
- CRUD dasar produk.
- Flow POS: `scan/add item -> update cart -> remove item -> checkout`.

## Cara pakai cepat

1. Copy isi folder `database/migrations` ke project Laravel kamu.
2. Copy `app/Models/*` dan `app/Http/Controllers/Api/*`.
3. Merge route dari `routes/api.php`.
4. Jalankan:

```bash
php artisan migrate
```

## Catatan

- Endpoint POS memakai transaksi database (`DB::transaction`) saat checkout supaya update stok tetap atomic.
- Semua route diproteksi `auth:sanctum`.
- Implementasi void transaksi bisa ditambahkan sebagai endpoint admin (`POST /transactions/{id}/void`).
