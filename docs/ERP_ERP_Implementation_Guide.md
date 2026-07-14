# MStore ERP Core Implementation Guide

## Overview
Transformasi MStore dari multi-module menjadi **ERP Enterprise Core System dengan Single Source of Truth**

## 1. Architecture
```
┌───────────────────────────────────────────────────────────────────┐
│                        Business Modules                           │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐ ┌─────────┐ │
│  │   ISP   │  │   ATK   │  │  Wash   │  │  CCTV   │ │ Wedding │ │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘ └────┬────┘ │
└───────┼────────────┼────────────┼────────────┼───────────┼────────┘
        │            │            │            │           │
        └────────────┴────────────┴────────────┴───────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────────┐
│              General Transaction (Single Source of Truth)         │
│  - Semua transaksi masuk ke sini                                 │
│  - Polymorphic reference ke model asli                            │
│  - Business Unit, Profit/Cost Center, Branch                      │
└───────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────────┐
│                    Event Driven System                            │
│  - TransactionCreated → AccountingEventListener                  │
│  - InvoicePaid → AccountingEventListener                         │
│  - ExpenseApproved → AccountingEventListener                      │
└───────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────────┐
│                      Journal Engine                               │
│  - AccountingPoster Service                                      │
│  - Double Entry Bookkeeping                                      │
└───────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────────┐
│                      General Ledger                               │
│  - COA (Account)                                                 │
│  - Journals                                                      │
│  - Journal Entries                                               │
└───────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────────┐
│                      Financial Reports                            │
│  - Trial Balance                                                 │
│  - Profit & Loss                                                 │
│  - Balance Sheet                                                 │
│  - Cash Flow                                                     │
└───────────────────────────────────────────────────────────────────┘
```

## 2. Setup Instructions

### Step 1: Run Migrations
```bash
php artisan migrate
```

### Step 2: Seed Initial Data
```bash
php artisan db:seed --class=BusinessUnitsSeeder
php artisan db:seed --class=ExpenseCategoriesSeeder
```

### Step 3: Backfill Old Data
```bash
php artisan erp:backfill-transactions
```

## 3. Components

### 3.1 Models
- **BusinessUnit**: Menyimpan data unit bisnis (ISP, ATK, Wash, dll)
- **Branch**: Cabang per unit bisnis
- **GeneralTransaction**: Single Source of Truth untuk semua transaksi
- **Expense, ExpenseCategory, ExpenseItem, ExpenseApproval**: Expense Engine
- **Traits\PostsToGeneralLedger**: Trait untuk otomatis posting transaksi ke GeneralTransaction

### 3.2 Events
- **GeneralTransactionCreated**: Event ketika transaksi dibuat
- **InvoicePaidEvent**: Ketika invoice ISP dibayar
- **WashTransactionCreated**: Ketika transaksi wash dibuat
- **AtkTransactionCreated**: Ketika transaksi ATK dibuat
- **ExpenseApproved**: Ketika expense disetujui

### 3.3 Listeners
- **AccountingEventListener**: Mendengarkan semua event dan otomatis posting ke Ledger

## 4. Business Rules

### Strict Rules
1. **Semua transaksi harus masuk ke GeneralTransaction**
2. **Tidak boleh ada manual accounting di controller**
3. **Hanya event-driven yang boleh posting ke Ledger**
4. **Setiap transaksi wajib punya BusinessUnit**

## 5. KPI Success
- ✅ 100% transaksi masuk GeneralTransaction
- ✅ 100% transaksi otomatis generate journal
- ✅ Semua laporan P&L dari Ledger
- ✅ Multi Business Unit report terpisah

## 6. Next Steps
1. Tambahkan Approval Workflow lengkap
2. Buat Dashboard Konsolidasi
3. Tambahkan Summary Tables untuk fast query
4. Multi-Company Support
5. Intercompany Transactions
