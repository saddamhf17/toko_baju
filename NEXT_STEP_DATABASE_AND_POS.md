# Next Step: Database Lengkap + POS Flow (Codex-Ready)

Ini lanjutan paling aman setelah setup awal: **desain database dulu**, lalu implement **POS flow**.

## 1) Urutan eksekusi (recommended)

1. Buat migration untuk master data (`categories`, `products`, `product_variants`, `customers`).
2. Buat migration transaksi (`transactions`, `transaction_items`, `payments`).
3. Tambahkan index + foreign key.
4. Seed data dummy.
5. Implement endpoint POS (`scan/add-to-cart`, `checkout`, `void`).

---

## 2) Skema database (MVP)

### `categories`
- `id` (PK)
- `name` (string, unique)
- timestamps

### `products`
- `id` (PK)
- `category_id` (FK -> categories)
- `name` (string)
- `slug` (string, unique)
- `description` (text, nullable)
- `is_active` (boolean, default true)
- timestamps

### `product_variants`
- `id` (PK)
- `product_id` (FK -> products)
- `sku` (string, unique)
- `barcode` (string, nullable, unique)
- `size` (string, nullable)
- `color` (string, nullable)
- `price` (unsignedBigInteger)
- `stock` (integer, default 0)
- `min_stock_alert` (integer, default 3)
- timestamps

### `customers`
- `id` (PK)
- `name` (string)
- `phone` (string, nullable)
- `email` (string, nullable)
- `member_code` (string, unique, nullable)
- timestamps

### `transactions`
- `id` (PK)
- `invoice_no` (string, unique)
- `customer_id` (FK nullable -> customers)
- `user_id` (FK -> users)
- `subtotal` (unsignedBigInteger)
- `discount_amount` (unsignedBigInteger, default 0)
- `tax_amount` (unsignedBigInteger, default 0)
- `grand_total` (unsignedBigInteger)
- `status` (enum: `draft`, `paid`, `void`)
- `paid_at` (timestamp nullable)
- timestamps

### `transaction_items`
- `id` (PK)
- `transaction_id` (FK -> transactions)
- `product_variant_id` (FK -> product_variants)
- `qty` (integer)
- `price` (unsignedBigInteger)
- `discount_amount` (unsignedBigInteger, default 0)
- `line_total` (unsignedBigInteger)
- timestamps

### `payments`
- `id` (PK)
- `transaction_id` (FK -> transactions)
- `method` (enum: `cash`, `transfer`, `qris`, `midtrans`, `xendit`)
- `amount` (unsignedBigInteger)
- `reference_no` (string, nullable)
- `meta` (json, nullable)
- `paid_at` (timestamp)
- timestamps

---

## 3) Command generator (langsung jalan)

```bash
php artisan make:model Category -mcr
php artisan make:model Product -mcr
php artisan make:model ProductVariant -mcr
php artisan make:model Customer -mcr
php artisan make:model Transaction -mcr
php artisan make:model TransactionItem -mcr
php artisan make:model Payment -mcr
php artisan make:seeder CategorySeeder
php artisan make:seeder ProductSeeder
php artisan make:seeder CustomerSeeder
```

Jalankan migration:

```bash
php artisan migrate
php artisan db:seed
```

---

## 4) POS flow (scan -> cart -> checkout)

### A. Scan / tambah item
- Input: `barcode` atau `sku`.
- Validasi: variant ditemukan + stok > 0.
- Aksi: tambah ke cart draft transaction.

### B. Update cart
- Ubah qty.
- Cek stok realtime.
- Hitung subtotal + discount + tax + grand total.

### C. Checkout
- Simpan payment.
- Ubah status `draft -> paid`.
- **Kurangi stok** di `product_variants.stock` dalam DB transaction (atomic).

### D. Void transaksi
- Hanya role admin.
- Kembalikan stok sesuai item.
- Status jadi `void`.

---

## 5) Endpoint API minimal

```txt
POST   /api/pos/cart/items        (scan/add item)
PATCH  /api/pos/cart/items/{id}   (update qty)
DELETE /api/pos/cart/items/{id}   (remove item)
POST   /api/pos/checkout          (pay + finalize)
POST   /api/transactions/{id}/void
GET    /api/transactions
GET    /api/products?search=&low_stock=1
```

---

## 6) Acceptance criteria MVP

- Kasir bisa scan SKU/barcode dan checkout transaksi.
- Stok otomatis berkurang saat status `paid`.
- Void mengembalikan stok.
- Laporan penjualan harian menampilkan `gross`, `discount`, `net`.

---

## 7) Rekomendasi next action (hari ini)

1. Implement migration + model relation dulu.
2. Lanjut endpoint POS `scan/add item` dan `checkout`.
3. Baru sambungkan React cart page.

Kalau mau, next commit bisa langsung saya isi:
- template migration Laravel siap copy-paste, atau
- draft controller `PosController` lengkap validasi + DB transaction.
