# Aplikasi M-Store Audit dan Dokumentasi Modul dan Menu
Dibuat: 2026-07-16

## Temuan Penting
### Duplikasi Menu/Fitur
| Menu | Status | Lokasi Asli | Lokasi Duplikat (Dihapus) |
|------|--------|--------------|-----------------------------|
| Server GenieACS | Sudah diperbaiki | Network Operations > Monitoring | System Administration > Integrations & API |

---

## Struktur Aplikasi
Aplikasi ini adalah **Sistem Informasi Manajemen Bisnis Multi-Modul** untuk mengelola berbagai unit bisnis termasuk ISP, Car Wash, Wedding & Event, CCTV Installation, dan ATK.

---

## Daftar Modul dan Fitur Utama

### 1. Dashboard Center
| Menu | Fitur | Izin Diperlukan |
|------|-------|------------------|
| Dashboard Utama | Dashboard utama aplikasi | `dashboard.view` |
| Dashboard NOC | Dashboard untuk Network Operations Center | `noc.dashboard.view` |
| Dashboard Finance | Dashboard untuk keuangan | `finance.view` |
| Dashboard Konsolidasi | Laporan konsolidasi perusahaan | `consolidation.view` |
| Dashboard HRD | Dashboard untuk Human Resource Department | `admin.dashboard.view` |
| Reporting Center > NOC Report | Laporan NOC | `report.noc.export` |
| Reporting Center > WhatsApp Report | Laporan WhatsApp | `report.whatsapp.export` |
| Reporting Center > SLA Report | Laporan Service Level Agreement | `report.sla.export` |
| Reporting Center > Wedding Report | Laporan Wedding & Event | `report.wedding.export` |
| Reporting Center > CCTV Report | Laporan CCTV Installation | `report.cctv.export` |

---

### 2. Customer Center
| Menu | Fitur | Izin Diperlukan |
|------|-------|------------------|
| Data Pelanggan | Manajemen data pelanggan ISP | `customer.view` |
| Instalasi Baru | Manajemen permintaan instalasi baru | `installation.view` |
| Paket Internet | Daftar paket layanan internet | `package.view` |
| PPPoE | Manajemen pengguna PPPoE | `pppoe.view` |
| Hotspot Aktif | Monitor pengguna hotspot aktif | `hotspot.view`, `router.view` |
| Voucher Hotspot | Manajemen voucher hotspot | `voucher.view` |

---

### 3. Network Operations
| Grup | Menu | Fitur | Izin Diperlukan |
|------|------|-------|------------------|
| Monitoring | Monitor Jaringan | Monitoring jaringan via GenieACS | `genieacs.view` |
| Monitoring | Server GenieACS | Konfigurasi server GenieACS | `genieacs_server.view` |
| Monitoring | OLT Monitoring | Monitor status OLT | `noc.olt_monitoring.view` |
| Monitoring | Monitor Peta Jaringan | Peta jaringan fiber optic | `map.view` |
| Monitoring | Analisis Jaringan | Analisis performa jaringan | `router.view` |
| Monitoring | Kalkulator PON | Kalkulator untuk perhitungan PON | `calculator.view` |
| Operasional | Area Outage | Laporan area jaringan yang mengalami gangguan | `noc.operational.view` |
| Operasional | Network Incident | Catatan insiden jaringan | `noc.operational.view` |
| Operasional | Network Diagnostic | Alat diagnostik jaringan | `noc.operational.view` |
| Operasional | Diagnostic Logs | Log hasil diagnostik jaringan | `noc.diagnostic_logs.view` |
| Infrastruktur | OLT | Manajemen perangkat OLT | `olt.view` |
| Infrastruktur | ODC | Manajemen Optical Distribution Cabinet | `odc.view` |
| Infrastruktur | ODP | Manajemen Optical Distribution Point | `odp.view` |
| Infrastruktur | Closure | Manajemen closure jaringan | `closure.view` |
| Infrastruktur | HTB | Manajemen HTB (Home Terminal Box) | `htb.view` |
| Akses | Router | Manajemen router/NAS | `router.view` |
| Akses | VPN Bridge | Konfigurasi VPN bridge | `router.view` |
| Akses | VPN Guide | Panduan penggunaan VPN | `router.view` |

---

### 4. Ticketing
| Menu | Fitur | Izin Diperlukan |
|------|-------|------------------|
| Tiket Gangguan | Manajemen tiket gangguan teknis | `ticket.view` |
| Pendataan Modem | Data modem pelanggan | `modem-data.view` |
| SLA Monitoring | Monitor kepatuhan terhadap SLA | `sla.monitoring.view` |
| Escalation Queue | Antrian tiket yang di-escalate | `sla.escalation.view` |

---

### 5. Messenger & AI
| Grup | Menu | Fitur | Izin Diperlukan |
|------|------|-------|------------------|
| - | Messenger Internal | Chat internal karyawan | `chat.view` |
| AI Assistant | AI Center | Pusat layanan AI | `ai.view` |
| AI Assistant | AI Knowledge Base | Knowledge Base untuk AI WhatsApp | `whatsapp.kb.manage` |

---

### 6. Finance Center
| Grup | Menu | Fitur | Izin Diperlukan |
|------|------|-------|------------------|
| - | Manajemen Perusahaan | Data perusahaan multi-cabang | `company.view` |
| Billing | Dasbor Keuangan | Ringkasan keuangan | `finance.view` |
| Billing | Laporan Laba Rugi | Laporan laba rugi | `finance.view` |
| Billing | Laporan Material | Laporan penggunaan material | `finance.view` |
| Billing | Manager Report | Laporan untuk manajer | `finance.view` |
| Accounting | Trial Balance | Neraca saldo | `accounting.view` |
| Accounting | Income Statement | Laporan laba rugi akuntansi | `accounting.view` |
| Accounting | Balance Sheet | Neraca | `accounting.view` |
| Accounting | Ledger | Buku besar | `accounting.view` |
| Accounting | Cash Flow | Laporan arus kas | `accounting.view` |
| Investor | Investor Report | Laporan untuk investor | `finance.view`, `investor.view` |
| Investor | Data Investor | Data investor | `investor.view` |

---

### 7. HR & Asset
| Grup | Menu | Fitur | Izin/Role Diperlukan |
|------|------|-------|----------------------|
| HR | Karyawan | Manajemen data karyawan | `employee.view` |
| HR | Absensi | Manajemen absensi karyawan | `attendance.view` |
| HR | Jadwal | Manajemen jadwal karyawan | `schedule.view` |
| HR | Pengajuan Cuti/Izin Saya | Pengajuan cuti/izin pribadi | `leave.view` |
| HR | Kelola Cuti/Izin | Manajemen cuti/izin karyawan | `leave.manage` |
| HR | Kasbon | Manajemen pinjaman karyawan (kasbon) | Role: ADMIN, FINANCE, HRD_MANAGER, DIREKTUR |
| HR | Slip Gaji | Cetak slip gaji | `attendance.view` |
| Asset | Inventory | Manajemen inventaris barang | `inventory.view` |
| Asset | Aset Saya | Daftar aset yang dimiliki karyawan | `inventory.view` |
| Asset | Pengambilan Barang | Catatan pengambilan barang dari inventory | `inventory.pickup`, `inventory.manage` |

---

### 8. Business Units
#### a. ATK (Alat Tulis Kantor)
| Grup | Menu | Fitur | Izin Diperlukan |
|------|------|-------|------------------|
| - | Dashboard ATK | Dashboard bisnis ATK | `atk.view` |
| - | POS ATK | Point of Sale untuk ATK | `atk.pos` |
| - | Kasir Shift | Manajemen shift kasir ATK | `atk.manage` |
| Transaksi | Riwayat Transaksi | Riwayat transaksi ATK | `atk.view` |
| Keuangan | Akun Float | Manajemen akun float | `atk.manage` |
| Keuangan | Dana Talangan | Manajemen dana talangan | `atk.manage` |
| Keuangan | Pengeluaran ATK | Catatan pengeluaran ATK | `atk.manage` |
| Keuangan | Manajemen Biaya | Manajemen biaya operasional | `atk.manage` |
| Keuangan | Mutasi Kas Utama | Riwayat mutasi kas utama | `atk.manage` |
| Master Data | Manajemen ATK | Manajemen produk ATK | `atk.manage` |
| Laporan | Laporan Penjualan | Laporan penjualan ATK | `atk.report` |
| Laporan | Laporan Kas Harian | Laporan kas harian ATK | `atk.report` |
| Laporan | Laporan Float | Laporan akun float ATK | `atk.report` |
| Laporan | Laporan Dana Talangan | Laporan dana talangan ATK | `atk.report` |

#### b. GT Wash (Car Wash)
| Menu | Fitur | Izin Diperlukan |
|------|-------|------------------|
| Dashboard | Dashboard bisnis GT Wash | `wash.view` |
| POS Wash | Point of Sale untuk cuci mobil | `wash.pos` |
| Transaksi | Riwayat transaksi GT Wash | `wash.report` |
| Pengeluaran | Catatan pengeluaran GT Wash | `wash.report` |
| Stok Wash | Manajemen stok barang GT Wash | `wash.view` |
| Manajemen Layanan | Manajemen paket layanan cuci | `wash.manage` |
| Supplier | Manajemen supplier GT Wash | `wash.manage` |
| Shift | Manajemen shift karyawan Wash | `wash.manage` |
| Sesi Shift | Sesi shift karyawan Wash | `wash.manage` |
| Kasir | Manajemen kasir GT Wash | `wash.manage` |
| Mutasi Kas | Riwayat mutasi kas GT Wash | `wash.manage` |
| Penutupan Harian | Penutupan kas harian GT Wash | `wash.manage` |
| Member | Manajemen member GT Wash | `wash.member.view` |
| Loyalty Program | Program loyalitas member | `wash.loyalty.view` |
| Reward Voucher | Voucher hadiah untuk member | `wash.reward.view` |
| Membership Level | Level keanggotaan member | `wash.member.view` |
| Riwayat Reward | Riwayat penukaran hadiah | `wash.reward.view` |
| Laporan Wash | Laporan bisnis GT Wash | `wash.report` |

#### c. Wedding & Event
| Menu | Fitur | Izin Diperlukan |
|------|-------|------------------|
| Dashboard | Dashboard Wedding & Event | `wedding.view` |
| Paket | Paket layanan wedding/event | `wedding.view` |
| Galeri Landing | Galeri untuk landing page | `wedding.view` |
| Booking | Manajemen booking acara | `wedding.booking` |
| Jadwal Acara | Jadwal acara yang dipesan | `wedding.view` |
| Pembayaran | Manajemen pembayaran booking | `wedding.payment` |
| Laporan | Laporan Wedding & Event | `report.wedding.export` |

#### d. CCTV Installation
| Menu | Fitur | Izin Diperlukan |
|------|-------|------------------|
| Dashboard | Dashboard CCTV Installation | `cctv.view` |
| Paket CCTV | Paket layanan instalasi CCTV | `cctv.view` |
| Booking Instalasi | Booking untuk instalasi CCTV | `cctv.booking` |
| Jadwal Teknisi | Jadwal teknisi instalasi | `cctv.view` |
| Pembayaran | Manajemen pembayaran CCTV | `cctv.payment` |
| Laporan | Laporan CCTV Installation | `report.cctv.export` |

---

### 9. System Administration
| Grup | Menu | Fitur | Izin Diperlukan |
|------|------|-------|------------------|
| General Settings | Pengaturan Toko | Pengaturan umum aplikasi | `setting.view` |
| General Settings | Pengaturan Absensi | Konfigurasi sistem absensi | `setting.view` |
| General Settings | Wilayah & Cabang | Manajemen wilayah dan cabang | `region.view` |
| General Settings | Pengurus | Manajemen pengurus perusahaan | `coordinator.view` |
| Payment Gateway | Dashboard Payment | Dashboard payment gateway | `payment.view` |
| Payment Gateway | Duitku Config | Konfigurasi Duitku | `payment.view` |
| Payment Gateway | Midtrans Config | Konfigurasi Midtrans | `payment.view` |
| WhatsApp Gateway | Status & Koneksi | Status koneksi WhatsApp gateway | `chat.view` |
| WhatsApp Gateway | AI Bot Builder | Builder untuk bot WhatsApp | `chat.manage` |
| WhatsApp Gateway | Pesan Terkirim | Log pesan WhatsApp yang terkirim | `chat.view` |
| WhatsApp Gateway | Statistik WA | Statistik penggunaan WhatsApp | `whatsapp.analytics.view` |
| Telegram Bot | Pengaturan Bot | Konfigurasi bot Telegram | `telegram.view` |
| Integrations & API | API Keys | Manajemen API key untuk integrasi | `apikey.view` |
| Integrations & API | MixRADIUS Config | Konfigurasi MixRADIUS | `setting.view` |
| Access Control | User Management | Manajemen pengguna sistem | `user.view` |
| Access Control | Role & Permission | Manajemen role dan izin | `role.view` |
| Security & Logs | Audit Trail | Log aktivitas sistem | `audit.view` |
| Security & Logs | Security Monitoring | Monitoring keamanan sistem | `security.monitoring.view` |

---

### 10. Portal Pelanggan (Hanya untuk Role CUSTOMER)
| Menu | Fitur |
|------|-------|
| Dashboard | Dashboard untuk pelanggan |
| Tagihan | Lihat tagihan bulanan |
| Kredensial | Lihat dan ubah kredensial internet |
| Profil | Ubah profil pribadi |
| Status Jaringan | Monitor status koneksi internet |

---

## Catatan Teknis
- Aplikasi dibangun dengan Laravel
- Menu sidebar didefinisikan di `app/Support/Sidebar/SidebarMenu.php`
- Izin (permission) dan role disimpan di database
- Sidebar di-cache selamanya (forever) di cache key `sidebar.menu.tree.v12`
