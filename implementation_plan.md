# Fitur Check-In Peserta via QR Code

Sistem absensi digital hari acara dengan tampilan **full-screen berwarna** yang intuitif dan mudah dibaca dari jarak dekat — putih saat input, merah jika gagal, hijau jika berhasil.

---

## Alur Lengkap

```
Admin cetak QR dari admin.php → tempel di pintu
       ↓
Peserta scan QR → checkin.php terbuka (layar PUTIH)
       ↓
Peserta ketik nomor WhatsApp → Submit
       ↓
  ✅ Valid & belum hadir  → Layar HIJAU  "Selamat Datang!"
  ✅ Sudah hadir sebelum  → Layar HIJAU  "Sudah Check-In pukul XX:XX"
  ❌ Tidak terdaftar      → Layar MERAH  "Nomor tidak ditemukan"
  ❌ Belum dikonfirmasi   → Layar MERAH  "Pendaftaran belum confirmed"
```

---

## Desain Layar checkin.php

### 🔲 Layar PUTIH — Input
```
┌──────────────────────────────────┐
│  background: #FFFFFF             │
│                                  │
│    🕌  SYIRKAH BISNIS MASTERY    │
│       CHECK-IN PESERTA           │
│                                  │
│  ┌────────────────────────────┐  │
│  │  081234567890              │  │
│  └────────────────────────────┘  │
│                                  │
│  [ ✓ CHECK IN SEKARANG ]         │
└──────────────────────────────────┘
```

### 🟢 Layar HIJAU — Sukses
```
┌──────────────────────────────────┐
│  background: #16A34A (green-600) │
│                                  │
│           ✅                     │
│    SELAMAT DATANG!               │
│                                  │
│    Ahmad Fauzi                   │
│    Usaha: Kuliner                │
│                                  │
│    Check-in: 08:32 WIB           │
│    Sabtu, 03 Mei 2026            │
│                                  │
│  [ ← Check-In Lain ]            │
└──────────────────────────────────┘
```

### 🔴 Layar MERAH — Gagal
```
┌──────────────────────────────────┐
│  background: #DC2626 (red-600)   │
│                                  │
│           ✖                      │
│    AKSES DITOLAK                 │
│                                  │
│    "Nomor tidak terdaftar"       │
│    atau                          │
│    "Pendaftaran belum confirmed" │
│                                  │
│  [ ← Coba Lagi ]                │
└──────────────────────────────────┘
```

> **Transisi:** Perpindahan antar layar menggunakan animasi fade smooth (CSS transition 0.4s) agar terasa premium dan tidak kaget.

---

## Proposed Changes

### Database

#### [MODIFY] [init.sql](file:///d:/BISNIS%20MASTERY/Remake/Remake%201/init.sql)
Dijalankan **1x** via phpMyAdmin cPanel:
```sql
ALTER TABLE registrations
  ADD COLUMN checked_in_at TIMESTAMP NULL DEFAULT NULL;
```

---

### Backend

#### [NEW] checkin_api.php
- `POST { whatsapp_number }` → validasi & update `checked_in_at`
- Response JSON: `{ success, state: "success"|"already"|"not_found"|"not_confirmed", data }`

#### [MODIFY] [api.php](file:///d:/BISNIS%20MASTERY/Remake/Remake%201/api.php)
- Sertakan field `checked_in_at` pada response `GET`

---

### Frontend

#### [NEW] checkin.php
- Satu halaman dengan **3 state layar** (putih / hijau / merah) yang berganti via JavaScript
- Full-screen, mobile-first, font besar agar mudah dibaca
- Animasi fade transisi antar layar
- Tombol "Coba Lagi" / "Check-In Lain" untuk kembali ke layar input

#### [MODIFY] [admin.php](file:///d:/BISNIS%20MASTERY/Remake/Remake%201/admin.php)
- Tambah **summary card ke-4**: `Hadir`
- Tambah **kolom "Check-In"** di tabel: waktu hadir atau badge "Belum Hadir"
- Tambah **tombol "🖨️ Cetak QR"** di header → QR via `api.qrserver.com`

---

## Verification Plan

### Manual
- Scan QR → pastikan `checkin.php` terbuka, layar putih muncul
- Input nomor valid confirmed → layar **hijau** muncul, cek DB
- Input nomor sama lagi → layar **hijau** dengan info "sudah hadir pukul..."
- Input nomor pending/tidak ada → layar **merah** muncul
- Klik "Coba Lagi" → kembali ke layar putih
- Cek `admin.php` → kolom check-in & card Hadir terupdate
