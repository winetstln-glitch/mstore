# Daftar Role dan Template Default di Aplikasi MStore

Berikut adalah daftar semua role yang tersedia beserta template izin defaultnya:

---

## 1. Administrator
- **Name**: `administrator` (atau `admin`)
- **Izin**: Semua izin (full access)
- **Kegunaan**: Super admin, bisa mengakses semua fitur

---

## 2. Network Operations Center (NOC)
- **Name**: `network-operations-center` (atau `noc`)
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

## 3. Teknisi
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

## 4. Leader
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

## 5. Koordinator
- **Name**: `koordinator` (atau `coordinator`)
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

## 6. Reseller
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

## 7. Staf Keuangan
- **Name**: `staf-keuangan` (atau `finance`)
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

## 8. Kasir ATK
- **Name**: `kasir-atk`
- **Izin**:
  - atk.view, atk.pos, atk.report
  - attendance.view, attendance.create, attendance.edit
  - profile.view, profile.update
- **Kegunaan**: Kasir toko ATK

---

## 9. Kasir Wash
- **Name**: `kasir-wash`
- **Izin**: Semua izin Teknisi + Semua izin Wash
- **Kegunaan**: Kasir layanan cuci

---

## 10. Karyawan Wash
- **Name**: `karyawan-wash`
- **Izin**: Semua izin Teknisi + Semua izin Wash
- **Kegunaan**: Operator layanan cuci

---

## 11. Manager HRD
- **Name**: `manager-hrd` (atau `hrd-manager`)
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

## 12. Customer
- **Name**: `customer`
- **Izin**: Tidak ada izin (hanya akses portal customer)
- **Kegunaan**: Pelanggan
