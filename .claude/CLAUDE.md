# Project Memory

Instructions here apply to this project and are shared with team members.

## Context

Simple MP adalah aplikasi marketplace warga berbasis **Laravel 12 + Filament 5**.
Baca [README.md](../README.md) untuk gambaran fitur lengkap dan [AGENTS.md](../AGENTS.md) untuk quick-start commands dan architecture map.

---

## Stack & Key Dependencies

- PHP 8.2, Laravel 12, Filament 5, Vite + Tailwind CSS 4
- **Spatie MediaLibrary** — gambar produk disimpan via media collection `products`, bukan kolom terpisah
- **Spatie ActivityLog** — model `Product` dan `LapakProfile` mencatat perubahan otomatis
- **Filament Breezy** — profile management + 2FA (paksa untuk admin di production)
- **Pest** — semua test ditulis dengan Pest, bukan PHPUnit langsung
- **Laravel Telescope + Debugbar** — tersedia di local, jangan aktifkan di production

---

## Struktur Aplikasi

### Models & Relasi Penting

| Model | Keterangan |
|---|---|
| `User` | Punya `lapak` (hasOne LapakProfile), field `is_admin`, `push_tokens` |
| `LapakProfile` | `belongsTo User`, `hasMany Product`; slug digenerate dari name |
| `Product` | `belongsTo LapakProfile (lapak_id)`, route binding via `slug` |
| `Category` | `hasMany Product`; punya flag `supportsCondition()` |
| `TokenPurchase` | Pembelian token oleh user; dikonfirmasi admin |
| `Report` | Polymorphic terhadap `Product` dan `LapakProfile` |
| `ProductModeration` | Deactivation/Reactivation request produk |
| `Setting` | Key-value config app; akses via `Setting::getValue('key', 'default')` |

### Services

- `ProductScheduleService` — mengelola visibilitas produk berbasis cache. **Jangan ubah logika scheduling-nya tanpa memahami dampak bisnis.** Cache key: `products.schedule.{lapakId}` dan `products.schedule.eligible_ids`.
- `ProductModerationService` — logika moderasi produk (deactivation/reactivation).

### Filament Panel

- Path: `/admin`, provider: `app/Providers/Filament/AdminPanelProvider.php`
- Resources di-discover otomatis dari `app/Filament/Resources`
- Pages member: `BuyTokensPage`, `LapakProfile`, `SiteSettingsPage`
- Plugin `FilamentDeveloperLoginsPlugin` hanya aktif di `local` environment
- Middleware `EnsureLapakProfileExists` dijalankan di setiap request panel

---

## Konvensi Project

- **Models pakai `$guarded = []`** — ikuti pola ini, jangan ganti ke `$fillable` kecuali ada alasan keamanan spesifik
- **Route model binding produk pakai slug** — `Product::getRouteKeyName()` return `'slug'`
- **Route model binding lapak pakai slug** — `LapakProfile::getRouteKeyName()` return `'slug'`
- **Label/teks user-facing dalam Bahasa Indonesia** — jangan ganti ke English kecuali diminta
- **Warna brand** — gunakan CSS token `--color-primary`, `--color-secondary`, `--color-accent`, `--color-other` (lihat README untuk nilai hex)
- **Observer ada di** `app/Observers/` — `ProductObserver` dan `LapakProfileObserver` menginvalidasi cache schedule

---

## Development Commands

```bash
composer run setup   # install + migrate + build (first time)
composer run dev     # server + queue + pail + vite (semua sekaligus)
composer test        # clear config + run pest
npm run build        # build frontend assets
```

---

## Coding Guidelines (Skills)

Panduan coding tersedia di `.github/skills/`:

- **Laravel best practices** → `.github/skills/laravel-best-practices/SKILL.md` (dan subfolder `rules/`)
- **Pest testing** → `.github/skills/pest-testing/SKILL.md`
- **Tailwind CSS v4** → `.github/skills/tailwindcss-development/SKILL.md`

Gunakan skill ini setiap kali membuat atau memodifikasi kode yang relevan.

---

## Checklist Sebelum Selesai

- [ ] `composer test` — wajib untuk perubahan yang menyentuh backend
- [ ] `npm run build` — wajib jika menyentuh frontend, Tailwind, atau Vite config
- [ ] Perubahan model/migration: pastikan relasi dan observer tetap konsisten
- [ ] Perubahan `ProductScheduleService`: verifikasi cache invalidation masih bekerja
- [ ] Perubahan token/scheduler: **jangan ubah jadwal refill** (`tokens:refill-daily` / `tokens:refill-weekly`) tanpa konfirmasi

---

## Area Berisiko Tinggi

1. **Token refill scheduler** — `routes/console.php` mendefinisikan jadwal bisnis kritis; ubah hanya jika diminta eksplisit
2. **ProductScheduleService cache** — produk tidak akan muncul/hilang dengan benar jika cache key berubah
3. **Email verification flow** — `APP_URL` harus benar agar link verifikasi valid
4. **2FA admin** — di production, admin dipaksa pakai 2FA oleh Breezy; jangan ubah kondisi ini
5. **Integrasi opsional** (Turnstile, Telegram logging, SMTP) — gagal diam-diam jika env var tidak ada; test di local dengan `MAIL_MAILER=log`

---

## Referensi Cepat File Kunci

| Kebutuhan | File |
|---|---|
| Public routes | `routes/web.php` |
| Scheduler | `routes/console.php` |
| Panel config | `app/Providers/Filament/AdminPanelProvider.php` |
| Cache schedule logic | `app/Services/ProductScheduleService.php` |
| Konten peraturan | `peraturan-pengguna.md` |
| CSS tokens warna | `resources/css/app.css` |
| Filament theme | `resources/css/filament/admin/theme.css` |
