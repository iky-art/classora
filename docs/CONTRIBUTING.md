# Berkontribusi

Terima kasih sudah ingin membantu Classora.

## Sebelum mulai

Beberapa hal tidak boleh berubah tanpa diskusi dan persetujuan pemilik proyek terlebih dahulu: struktur monorepo, arsitektur database, peran dan permission, logika bisnis, dan pilihan framework. Buka issue yang menjelaskan alasan dan dampaknya.

## Menjalankan proyek

Lihat [README](../README.md) dan [DEPLOYMENT](DEPLOYMENT.md).

```bash
# Backend (dari apps/api)
php artisan test

# Frontend (dari root)
pnpm test:web && pnpm lint && pnpm build:web
```
Jangan menyatakan "sudah lulus" tanpa menjalankannya. Sebutkan perintah dan hasilnya di deskripsi PR.

## Aturan kode

**Backend**
- Setiap endpoint baru: Form Request, Policy, permission, dan test, termasuk test isolasi tenant.
- Model sekolah memakai `BelongsToSchool`. Query mentah wajib menyebut `school_id` pada setiap tabel.
- Jangan menerima `school_id` dari request.
- Operasi yang mengubah beberapa tabel dibungkus transaksi.
- Perubahan database lewat migrasi baru; jangan mengubah migrasi lama yang sudah dipakai orang. Beri nama eksplisit pada indeks panjang (batas 64 karakter).
- Perubahan data penting masuk `Audit::log`.

**Frontend**
- Tombol harus berfungsi. Jangan memakai data palsu; tandai jelas bila terpaksa memakai mock.
- Tangani loading, kosong, galat, dan sukses.
- Mobile-first, bisa dipakai dengan keyboard, kontras warna memadai, mode terang dan gelap.
- Warna dan tipografi diambil dari `packages/design-tokens`.
- Pengecekan izin di UI hanya kosmetik; backend yang memutuskan.

**Keamanan**
- Jangan commit `.env`, kredensial, atau data sekolah asli.
- Lapor kerentanan secara privat.

## Alur kerja

1. Fork dan buat cabang dari `main`: `fitur/nama-singkat` atau `perbaikan/nama-singkat`.
2. Commit kecil dengan pesan jelas.
3. Pastikan test dan build lulus.
4. Buka pull request: apa yang berubah, kenapa, dan cara mengujinya.

## Gaya

Bahasa antarmuka: Indonesia, kalimat biasa, kata kerja aktif. Pesan galat menjelaskan apa yang salah dan cara memperbaikinya, tanpa permintaan maaf berlebihan.
