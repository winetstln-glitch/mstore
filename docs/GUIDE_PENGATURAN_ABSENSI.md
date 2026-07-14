# Panduan Pengaturan Absensi

Panduan ini untuk admin agar pengaturan absensi berjalan konsisten, termasuk status **Terlambat**.

## 1. Lokasi Menu

1. Login sebagai admin.
2. Buka menu `Pengaturan`.
3. Masuk ke bagian `Pengaturan Absensi`.

## 2. Pengaturan Wajib

- `attendance_clock_in_start`  
  Jam mulai absensi masuk global (contoh: `08:00`).
- `attendance_clock_in_end`  
  Jam akhir absensi masuk global (contoh: `13:00`).
- `attendance_clock_in_early_minutes`  
  Batas menit boleh masuk lebih awal dari jam shift (contoh: `60`).
- `attendance_late_tolerance`  
  Toleransi keterlambatan dalam menit (contoh: `15`).
- `attendance_clock_out_start` dan `attendance_clock_out_end`  
  Rentang jam absensi pulang.
- `attendance_radius`  
  Radius maksimal dari titik kantor (meter), jika GPS dipakai.

## 3. Pengaturan Shift Jadwal

Untuk teknisi/wash yang memakai jadwal shift:

- Pastikan jadwal mingguan/harian sudah diisi (`Piket`, `Backup`, atau `Off`).
- `Piket` akan memakai `Shift 1`.
- `Backup` akan memakai `Shift 2`.
- Jika data harian kosong, sistem memakai fallback data mingguan.

## 4. Cara Kerja Status Terlambat

Status absensi masuk dihitung dengan aturan:

- Jika jam masuk **kurang dari atau sama dengan** `jam_mulai_shift + tolerance`, status = `present`.
- Jika jam masuk **lebih dari** batas tersebut, status = `late`.

Contoh:

- Jam mulai shift `08:00`, toleransi `15`.
- Masuk `07:59` -> `present`.
- Masuk `08:10` -> `present`.
- Masuk `08:16` -> `late`.

## 5. Checklist Jika Status Terlambat Tidak Sesuai

Lakukan pengecekan ini:

1. Pastikan `schedule_teknisi_shift_1_start` atau `schedule_wash_shift_1_start` benar (misal `08:00`).
2. Pastikan `attendance_late_tolerance` sesuai kebutuhan.
3. Pastikan user pada hari itu status jadwalnya benar (`Piket/Backup/Off`).
4. Pastikan timezone aplikasi di server sesuai (`Asia/Jakarta` untuk WIB).
5. Cek jam `clock_in` pada data absensi yang tersimpan.

## 6. Rekomendasi Nilai Awal

- `attendance_clock_in_start`: `08:00`
- `attendance_clock_in_end`: `13:00`
- `attendance_clock_in_early_minutes`: `60`
- `attendance_late_tolerance`: `15`
- `attendance_clock_out_start`: `20:00`
- `attendance_clock_out_end`: `01:00`

## 7. Catatan Operasional

- Ubah pengaturan di luar jam sibuk agar tidak membingungkan user.
- Setelah ubah pengaturan, lakukan 1 kali uji coba clock in oleh akun teknisi.
- Simpan bukti screenshot jika ada kasus status tidak sesuai untuk audit.
