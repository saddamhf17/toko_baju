# Frontend (React + Vite) - Struktur Siap Pakai

Struktur React sudah dibuat manual agar bisa langsung kamu lanjutkan:

- `src/api.js` untuk koneksi ke Laravel API
- `src/App.jsx` contoh fetch produk
- `src/main.jsx` bootstrap React

## Cara install dependency (ketika network ke npm registry sudah terbuka)

```bash
cd frontend
npm install
npm run dev
```

## Catatan

Di environment ini, akses `registry.npmjs.org` diblokir (`403`), jadi generate project otomatis via `npm create vite@latest` tidak bisa dijalankan.
