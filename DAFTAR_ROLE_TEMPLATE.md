# Daftar Role dan Template Default di Aplikasi MStore

Berikut adalah daftar semua role yang tersedia beserta template izin defaultnya (sudah disederhanakan dan konsisten):

---

## 1. Admin
- **Name**: `admin`
- **Izin**: Semua izin (full access)
- **Kegunaan**: Super admin, bisa mengakses semua fitur

---

## 2. Direktur
- **Name**: `direktur`
- **Izin**:
  - dashboard.view
  - customer.view, customer.create, customer.edit, customer.delete, customer.export
  - ticket.view, ticket.create, ticket.edit, ticket.delete, ticket.complete
  - installation.view, installation.create, installation.edit, installation.delete
  - attendance.view, attendance.create, attendance.edit, attendance.delete, attendance.report
  - schedule.view, schedule.create, schedule.edit, schedule.delete
  - leave.view, leave.create, leave.edit, leave.delete
  - map.view
  - profile.view, profile.update
  - notification.view, notification.manage
  - technician.view, technician.create, technician.edit, technician.delete
  - user.view, user.create, user.edit, user.delete
  - role.view, role.create, role.edit, role.delete
  - inventory.view, inventory.manage, inventory.pickup
  - finance.view, finance.create, finance.edit, finance.delete, finance.report
  - atk.view, atk.pos, atk.manage, atk.report
  - wash.view, wash.pos, wash.manage, wash.report
  - router.view, router.create, router.edit, router.delete
  - hotspot.view, hotspot.create, hotspot.edit, hotspot.delete
  - pppoe.view, pppoe.create, pppoe.edit, pppoe.delete
  - olt.view, olt.create, olt.edit, olt.delete
  - ont.view, ont.create, ont.edit, ont.delete
  - odp.view, odp.create, odp.edit, odp.delete
  - odc.view, odc.create, odc.edit, odc.delete
  - closure.view, closure.create, closure.edit, closure.delete
  - region.view, region.create, region.edit, region.delete
  - package.view, package.create, package.edit, package.delete
  - setting.view, setting.create, setting.edit, setting.delete
- **Kegunaan**: Direktur perusahaan, akses hampir semua fitur

---

## 3. NOC
- **Name**: `noc`
- **Izin Group**: 
  - Dashboard
  - Customer Management
  - Ticket Management
  - Installation Management
  - Router Management
  - OLT Management
  - ODC Management
  - ODP Management
  - Closure Management
  - HTB Management
  - PPPoE Management
  - Hotspot Management
  - Radius
  - Map
  - Network Monitor
  - Profile
  - Notification
  - Region Management
  - Package Management
- **Kegunaan**: Pengelola jaringan

---

## 4. Teknisi
- **Name**: `teknisi`
- **Izin**:
  - dashboard.view
  - ticket.view, ticket.complete
  - installation.view, installation.edit
  - attendance.view, attendance.create, attendance.edit, attendance.report
  - map.view
  - odp.view, odp.edit
  - odc.view, odc.edit
  - leave.view, leave.create
  - schedule.view
  - profile.view, profile.update
  - notification.view, notification.manage
  - inventory.view, inventory.pickup
  - modem-data.view, modem-data.create
  - olt.view, ont.view
  - customer.view
- **Kegunaan**: Teknisi lapangan

---

## 5. Leader
- **Name**: `leader`
- **Izin**:
  - dashboard.view
  - ticket.view, ticket.create, ticket.edit, ticket.delete, ticket.complete
  - attendance.view, attendance.create, attendance.edit, attendance.report
  - schedule.view, schedule.create, schedule.edit, schedule.delete
  - leave.view, leave.create, leave.edit
  - map.view
  - profile.view, profile.update
  - notification.view, notification.manage
  - technician.view, technician.create, technician.edit, technician.delete
- **Kegunaan**: Team leader teknisi

---

## 6. Koordinator
- **Name**: `koordinator`
- **Izin**:
  - dashboard.view
  - inventory.view, inventory.pickup, inventory.manage
  - map.view
  - profile.view, profile.update
  - notification.view, notification.manage
  - finance.view, finance.report
  - customer.view
  - odc.view
  - odp.view
- **Kegunaan**: Koordinator wilayah

---

## 7. Reseller
- **Name**: `reseller`
- **Izin**:
  - dashboard.view
  - customer.view, customer.create, customer.edit, customer.export
  - ticket.view, ticket.create, ticket.edit, ticket.complete
  - installation.view, installation.create, installation.edit
  - router.view, hotspot.view, pppoe.view
  - map.view
  - finance.view
  - profile.view, profile.update
  - notification.view, notification.manage
  - package.view, region.view
- **Kegunaan**: Reseller/jaringan

---

## 8. Staf Keuangan
- **Name**: `staf-keuangan`
- **Izin**:
  - dashboard.view
  - finance.view, finance.create, finance.edit, finance.delete, finance.report
  - inventory.view, inventory.manage
  - customer.view, customer.create, customer.edit
  - profile.view, profile.update
  - notification.view, notification.manage
  - attendance.view, attendance.report
- **Kegunaan**: Staf keuangan

---

## 9. Kasir ATK
- **Name**: `kasir-atk`
- **Izin**:
  - atk.view, atk.pos, atk.report
  - attendance.view, attendance.create, attendance.edit
  - profile.view, profile.update
- **Kegunaan**: Kasir toko ATK

---

## 10. Kasir Wash
- **Name**: `kasir-wash`
- **Izin**: Semua izin Teknisi + Semua izin Wash
- **Kegunaan**: Kasir layanan cuci

---

## 11. Karyawan Wash
- **Name**: `karyawan-wash`
- **Izin**: Semua izin Teknisi + Semua izin Wash
- **Kegunaan**: Operator layanan cuci

---

## 12. Manager HRD
- **Name**: `manager-hrd`
- **Izin**:
  - dashboard.view
  - employee.view, employee.create, employee.edit, employee.delete
  - user.view, user.create, user.edit, user.delete
  - role.view, role.create, role.edit, role.delete
  - inventory.view, inventory.manage, inventory.pickup
  - attendance.view, attendance.create, attendance.edit, attendance.report
  - leave.view, leave.create, leave.edit
  - schedule.view, schedule.create, schedule.edit
  - profile.view, profile.update
  - notification.view, notification.manage
- **Kegunaan**: Manager HRD

---

## 13. Customer
- **Name**: `customer`
- **Izin**: Tidak ada izin (hanya akses portal customer)
- **Kegunaan**: Pelanggan

---

## Cara Normalisasi Role di Database
Jalankan perintah ini untuk menormalisasi role di database:
```bash
php artisan roles:normalize
```
Perintah ini akan:
- Menggabungkan role duplikat (misal: Administrator ke Admin, Coordinator ke Koordinator)
- Memindahkan user dari role lama ke role baru
- Menghapus role lama yang tidak dibutuhkan
