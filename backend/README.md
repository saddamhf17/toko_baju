# Backend (Laravel API) - Struktur Siap Pakai

Folder ini sudah disiapkan untuk struktur Laravel API:

- `app/Models` model inti POS
- `app/Http/Controllers/Api` controller Auth, Dashboard, Product, POS
- `database/migrations` skema database lengkap
- `routes/api.php` route API

## Cara install dependency (ketika network sudah terbuka)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Catatan

Di environment ini, akses ke `packagist.org` diblokir (`403`), jadi instalasi paket Laravel otomatis belum bisa dijalankan.
