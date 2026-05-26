# Panduan Setting Notifikasi (WhatsApp & Telegram)

Panduan ini menjelaskan cara mendapatkan API Key dan mengatur konfigurasi untuk notifikasi WhatsApp dan Telegram di aplikasi MStore.

## 1. WhatsApp Notification

Aplikasi ini mendukung layanan WhatsApp Gateway. Secara default, kode telah disesuaikan untuk mendukung provider **Fonnte** (populer di Indonesia) atau API Generic lainnya.

### Cara Menggunakan Fonnte (Rekomendasi)

**Langkah 1: Daftar dan Dapatkan Token**
1. Buka website [Fonnte.com](https://fonnte.com).
2. Daftar akun (Register) dan Login.
3. Sambungkan WhatsApp Anda dengan scan QR Code di dashboard Fonnte.
4. Pada menu **API**, salin **Token** yang muncul (ini adalah API Key Anda).

**Langkah 2: Konfigurasi Aplikasi**
1. Buka file `.env` di folder project Anda.
2. Cari bagian `WHATSAPP_API_URL` dan `WHATSAPP_API_KEY`.
3. Isi dengan konfigurasi berikut:

```env
WHATSAPP_API_URL=https://api.fonnte.com
WHATSAPP_API_KEY=isi_token_fonnte_anda_disini
```

**Catatan:**
- Pastikan nomor HP teknisi/customer menggunakan format yang benar (contoh: `0812xxx` atau `62812xxx`). Fonnte akan otomatis menyesuaikan.
- Jika menggunakan provider lain (bukan Fonnte), pastikan URL dan parameter API disesuaikan kembali di `app/Services/WhatsAppService.php` jika formatnya berbeda.

---

## 2. Telegram Notification

Notifikasi Telegram digunakan untuk mengirim pesan ke Grup Teknisi atau user tertentu.

### Cara Mendapatkan Bot Token

**Langkah 1: Buat Bot Baru**
1. Buka aplikasi Telegram.
2. Cari akun **@BotFather** (pastikan yang terverifikasi/centang biru).
3. Ketik `/newbot` dan ikuti instruksi (beri nama bot dan username bot).
4. BotFather akan memberikan **HTTP API Token**. Salin token ini.

### Cara Mendapatkan Chat ID (Untuk Grup Teknisi)

**Langkah 1: Buat Grup**
1. Buat grup baru di Telegram (misal: "Teknisi MStore").
2. Undang Bot yang baru Anda buat ke dalam grup tersebut.
3. Jadikan Bot sebagai Admin (opsional, tapi disarankan agar bisa baca pesan).

**Langkah 2: Ambil Chat ID**
1. Kirim pesan apa saja di grup tersebut (misal: "Halo bot").
2. Buka browser dan akses URL berikut (ganti `<TOKEN>` dengan token bot Anda):
   `https://api.telegram.org/bot<TOKEN>/getUpdates`
3. Cari bagian `"chat":{"id":-100xxxxx...` di dalam respon JSON.
4. Angka yang dimulai dengan `-100` (atau `-`) adalah **Chat ID** grup Anda.

### Konfigurasi Aplikasi

Anda bisa mengatur Telegram melalui **Halaman Settings** di aplikasi atau melalui file `.env`.

**Opsi A: Melalui Halaman Settings (Disarankan)**
1. Login sebagai Admin.
2. Masuk ke menu **Settings > Telegram**.
3. Masukkan **Bot Token** dan **Group Chat ID** yang sudah didapat.
4. Klik Simpan dan coba tombol "Test Message".

**Opsi B: Melalui File .env**
Buka file `.env` dan isi:

```env
TELEGRAM_BOT_TOKEN=isi_token_bot_disini
```

(Chat ID grup biasanya diatur via database/halaman settings agar lebih fleksibel).
