# Panduan Modularisasi MStore

Berikut adalah panduan langkah demi langkah untuk modularisasi aplikasi MStore menggunakan `nwidart/laravel-modules`:

---

## 1. Struktur Modul yang Sudah Dibuat
Kita memiliki 3 modul:
- `Modules/Network/` → Semua fitur jaringan (OLT, ONU, Router, Hotspot, dll.)
- `Modules/Core/` → Semua fitur non-jaringan (Customer, Ticket, Teknisi, Keuangan, dll.)
- `Modules/Shared/` → Fitur shared (Auth, Notifikasi, Pengaturan, dll.)

---

## 2. Cara Mengaktifkan Modul
Untuk mengaktifkan modul, edit file `modules_statuses.json`:
```json
{
    "Network": true,
    "Core": false,
    "Shared": false
}
```
Atau gunakan perintah Artisan:
```bash
php artisan module:enable Network
php artisan module:disable Network
```

---

## 3. Cara Memindahkan File ke Modul (Contoh)
### Contoh 1: Memindahkan Controller OLT ke Modules/Network
1. Buat controller di modul Network:
   ```bash
   php artisan module:make-controller OLTController Network
   ```
2. Salin kode dari `app/Http/Controllers/OLTController.php` ke `Modules/Network/app/Http/Controllers/OLTController.php`
3. Ubah namespace di file baru menjadi:
   ```php
   namespace Modules\Network\App\Http\Controllers;
   ```
4. Update route di `Modules/Network/routes/web.php` untuk menggunakan controller baru!

---

## 4. Perintah Artisan Penting untuk Modul
- `php artisan module:list` → Lihat daftar modul
- `php artisan module:make-controller NamaController NamaModul` → Buat controller di modul
- `php artisan module:make-model NamaModel NamaModul` → Buat model di modul
- `php artisan module:make-migration NamaMigration NamaModul` → Buat migration di modul
- `php artisan module:make-seeder NamaSeeder NamaModul` → Buat seeder di modul
- `php artisan module:make-request NamaRequest NamaModul` → Buat request di modul
- `php artisan module:make-job NamaJob NamaModul` → Buat job di modul
- `php artisan module:make-command NamaCommand NamaModul` → Buat command di modul

---

## 5. Cara Mengakses View dari Modul
Untuk menampilkan view dari modul, gunakan sintaks:
```php
return view('network::nama_view');
return view('core::nama_view');
return view('shared::nama_view');
```

---

## 6. Cara Mengakses Asset dari Modul
Untuk mengakses asset dari modul, gunakan sintaks:
```php
asset('modules/network/js/app.js')
asset('modules/core/css/app.css')
```

---

## 7. Langkah Selanjutnya (Phase 1)
Rekomendasi untuk memindahkan file secara bertahap:
1. **Phase 1**: Pindahkan 1-2 controller sederhana terlebih dahulu (misal: `RouterController` ke Network)
2. **Phase 2**: Pindahkan model-model terkait
3. **Phase 3**: Pindahkan view dan route
4. **Phase 4**: Aktifkan modul dan tes!

---

## 8. Catatan Penting
- Biarkan model shared (misal: `User`, `Customer`) di `app/Models/` utama agar bisa diakses oleh semua modul!
- Selalu test setiap perubahan sebelum melanjutkan ke langkah berikutnya!
- Gunakan git untuk menyimpan setiap langkah agar mudah rollback jika terjadi kesalahan!
