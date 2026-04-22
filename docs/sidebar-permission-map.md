# Sidebar Permission Map

Panduan ini memetakan setiap menu sidebar ke permission yang mengaturnya.

## Menu Utama

| Menu | Permission |
|---|---|
| Dasbor | `dashboard.view` |
| Pusat AI | `ai.view` |

## Portal Pelanggan (Khusus)

Bagian ini saat ini masih khusus role `customer` (bukan permission-based):

- Portal Pelanggan (group)
- Beranda Portal
- Info Koneksi
- Tagihan Saya
- Kredensial Internet
- Portal MixRADIUS

## Pelanggan & Layanan

Wrapper group tampil jika minimal punya salah satu:
`customer.view`, `installation.view`, `hotspot.view`, `router.view`, `pppoe.view`, `package.view`.

| Menu | Permission |
|---|---|
| Data Pelanggan | `customer.view` |
| Pemasangan Baru | `installation.view` |
| Hotspot Aktif | `hotspot.view` |
| PPPoE Aktif | `router.view` atau `pppoe.view` |
| Paket Internet | `package.view` |
| Voucher Hotspot | `hotspot.view` |

## Jaringan

Wrapper group tampil jika minimal punya salah satu:
`map.view`, `genieacs.view`, `genieacs_server.view`, `calculator.view`, `router.view`, `olt.view`, `odc.view`, `odp.view`, `closure.view`, `htb.view`.

| Menu | Permission |
|---|---|
| Peta Jaringan | `map.view` |
| Monitor Jaringan | `genieacs.view` atau `genieacs_server.view` |
| Server GenieACS | `genieacs_server.view` |
| Kalkulator PON | `calculator.view` |
| Analisis Jaringan | `router.view` |
| Router / NAS | `router.view` (via wrapper Perangkat & Akses) |
| VPN Bridge | `router.view` (via wrapper Perangkat & Akses) |
| Panduan VPN | `router.view` (via wrapper Perangkat & Akses) |
| OLT | `olt.view` |
| ODC | `odc.view` |
| ODP | `odp.view` |
| Closure | `closure.view` |
| HTB | `htb.view` |

## Keuangan

Wrapper group tampil jika minimal punya salah satu:
`finance.view`, `investor.view`.

| Menu | Permission |
|---|---|
| Dasbor Keuangan | `finance.view` |
| Laba Rugi | `finance.view` |
| Laporan Material | `finance.view` |
| Laporan Manajer | `finance.view` |
| Neraca Saldo | `finance.view` |
| Laba Rugi (Akuntansi) | `finance.view` |
| Neraca | `finance.view` |
| Buku Besar | `finance.view` |
| Arus Kas | `finance.view` |
| Periode Akuntansi | `finance.view` |
| Laporan Investor | `investor.view` |
| Data Investor | `investor.view` |

## Toko ATK

Wrapper group tampil jika minimal punya salah satu:
`atk.view`, `atk.pos`.

| Menu | Permission |
|---|---|
| Dasbor | `atk.view` |
| Produk & Stok | `atk.manage` |
| Kasir (POS) | `atk.pos` |
| Riwayat Transaksi | `atk.report` |
| Pengeluaran | `atk.manage` |
| Laporan | `atk.report` |

## Cuci Kendaraan

Wrapper group tampil jika minimal punya salah satu:
`wash.view`, `wash.pos`, `wash.manage`, `wash.report`.

| Menu | Permission |
|---|---|
| Dasbor | `wash.view` |
| Layanan & Harga | `wash.manage` |
| Kasir (POS) | `wash.pos` |
| Riwayat Transaksi | `wash.report` |
| Pengeluaran | `wash.manage` |
| Laporan | `wash.report` |

## Operasional

Wrapper group tampil jika minimal punya salah satu:
`ticket.view`, `inventory.view`, `employee.view`, `attendance.view`, `attendance.report`, `schedule.view`, `leave.view`.

| Menu | Permission |
|---|---|
| Tiket & Gangguan | `ticket.view` |
| Data Karyawan | `employee.view` |
| Absensi Saya | `attendance.view` |
| Rekap Absensi | `attendance.report` |
| Jadwal Teknisi | `schedule.view` |
| Pengaturan Absensi | `setting.view` |
| Cuti / Izin | `leave.view` |
| Inventaris / Peralatan | `inventory.view` (via wrapper Aset & Peralatan) |
| Aset Saya | `inventory.view` (via wrapper Aset & Peralatan) |
| Pengambilan Barang | `inventory.view` (via wrapper Aset & Peralatan) |

## Sistem

Wrapper group tampil jika minimal punya salah satu:
`setting.view`, `user.view`.

| Menu | Permission |
|---|---|
| Pengaturan Umum | `setting.view` |
| Pengaturan ATK | `setting.view` |
| Pengaturan Wash | `setting.view` |
| Wilayah | `region.view` |
| Data Pengurus | `coordinator.view` |
| Manajemen User | `user.view` |
| Manajemen Peran | `role.view` |
| API WhatsApp | `chat.view` |
| Telegram | `telegram.view` |
| API Google Maps | `apikey.view` |

