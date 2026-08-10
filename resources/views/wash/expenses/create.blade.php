@extends('layouts.app')
@section('title', isset($expense) ? 'Edit Pengeluaran Wash' : 'Tambah Pengeluaran Wash')
@section('content')
@php
    $stockMovement = $stockMovement ?? null;
    $expense = $expense ?? null;

    $stockCategoryLabels = [
        'shampoo' => 'Sampo Wash',
        'snack' => 'Snack',
        'kopi' => 'Caffe',
        'caffe' => 'Caffe',
        'uang_makan' => 'Uang Makan',
        'insentif' => 'Insentif',
        'lembur' => 'Lembur',
        'lainnya' => 'Lainnya',
    ];
    
    $categories = [
        'shampoo' => ['label' => 'Sampo Wash', 'is_stock' => true],
        'snack' => ['label' => 'Snack', 'is_stock' => true],
        'caffe' => ['label' => 'Caffe', 'is_stock' => true],
        'uang_makan' => ['label' => 'Uang Makan', 'is_stock' => false],
        'insentif' => ['label' => 'Insentif', 'is_stock' => false],
        'lembur' => ['label' => 'Lembur', 'is_stock' => false],
        'lainnya' => ['label' => 'Lainnya', 'is_stock' => false],
    ];
    
    // Determine initial category
    $selectedCategory = old('expense_group', $expense?->expense_group ?? 'lainnya');
    if (!$selectedCategory && $expense) {
        $cat = strtolower($expense->category ?? '');
        if (str_contains($cat, 'sampo')) $selectedCategory = 'shampoo';
        elseif (str_contains($cat, 'snack')) $selectedCategory = 'snack';
        elseif (str_contains($cat, 'kopi') || str_contains($cat, 'caffe')) $selectedCategory = 'caffe';
        elseif (str_contains($cat, 'uang makan')) $selectedCategory = 'uang_makan';
        elseif (str_contains($cat, 'insentif')) $selectedCategory = 'insentif';
        elseif (str_contains($cat, 'lembur')) $selectedCategory = 'lembur';
    }
    $isStockCategory = $categories[$selectedCategory]['is_stock'] ?? false;
@endphp
<div class="container-fluid py-3 wash-expenses-create-page">
    <div class="d-flex justify-content-between align-items-center mb-4 create-header">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-wallet me-2"></i>
            {{ isset($expense) ? 'Edit Pengeluaran Wash' : 'Tambah Pengeluaran Wash' }}
        </h5>
        <a href="{{ route('wash.expenses.index') }}" class="btn btn-outline-secondary btn-sm d-none d-md-inline-flex">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card create-panel shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="{{ isset($expense) ? route('wash.expenses.update', $expense->id) : route('wash.expenses.store') }}" id="createExpenseForm">
                @csrf
                @if(isset($expense))
                    @method('PUT')
                @endif

                <div class="row g-4">
                    <!-- Tanggal & Kategori -->
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" class="form-control form-control-lg" 
                               value="{{ old('transaction_date', isset($expense) ? optional($expense->transaction_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" 
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium">Kategori Pengeluaran</label>
                        <select name="expense_group" id="expenseGroupSelect" class="form-select form-select-lg" required>
                            @foreach($categories as $key => $cat)
                                <option value="{{ $key }}" 
                                        data-is-stock="{{ $cat['is_stock'] ? '1' : '0' }}"
                                        {{ $selectedCategory === $key ? 'selected' : '' }}>
                                    {{ $cat['label'] }}
                                    {{ $cat['is_stock'] ? '(Stok)' : '(Operasional)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bagian Stok (Sembunyikan jika kategori operasional) -->
                    <div class="col-12 stock-section" style="{{ !$isStockCategory ? 'display: none;' : '' }}">
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3 text-primary">
                                    <i class="fas fa-box me-2"></i>Data Stok
                                </h6>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Pilih Stok Yang Ada</label>
                                        <select name="stock_item_id" id="stockItemSelect" class="form-select">
                                            <option value="">-- Buat item baru --</option>
                                            @foreach(($stockItems ?? []) as $item)
                                                <option value="{{ $item->id }}" 
                                                        data-name="{{ e($item->name) }}"
                                                        data-unit="{{ e($item->unit) }}"
                                                        data-price="{{ e((float) $item->last_buy_price) }}"
                                                        data-stock="{{ e((float) $item->current_stock) }}"
                                                        data-category="{{ e(strtolower((string) $item->category)) }}"
                                                        {{ (string) old('stock_item_id', optional($stockMovement)->wash_stock_item_id ?? '') === (string) $item->id ? 'selected' : '' }}>
                                                    {{ $stockCategoryLabels[strtolower((string) $item->category)] ?? ucfirst((string) $item->category) }} - {{ $item->name }} (Stok: {{ (float)$item->current_stock }} {{ $item->unit }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Item Pembelian</label>
                                        <input type="text" name="item_name" id="itemNameInput" 
                                               class="form-control" 
                                               placeholder="Contoh: Sampo Snow Wash 5L" 
                                               value="{{ old('item_name', optional(optional($stockMovement)->stockItem)->name ?? '') }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Satuan</label>
                                        <input type="text" name="unit" id="unitInput" 
                                               class="form-control" 
                                               placeholder="pcs/liter/pack" 
                                               value="{{ old('unit', optional(optional($stockMovement)->stockItem)->unit ?? 'pcs') }}"
                                               {{ $isStockCategory ? 'required' : '' }}>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Qty Pembelian</label>
                                        <input type="number" step="0.01" min="0.01" 
                                               name="quantity" id="quantityInput" 
                                               class="form-control" 
                                               value="{{ old('quantity', optional($stockMovement)->quantity ?? '1') }}"
                                               {{ $isStockCategory ? 'required' : '' }}>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Harga Satuan</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" step="0.01" min="0" 
                                                   name="unit_price" id="unitPriceInput" 
                                                   class="form-control" 
                                                   value="{{ old('unit_price', optional($stockMovement)->unit_price ?? '') }}"
                                                   {{ $isStockCategory ? 'required' : '' }}>
                                        </div>
                                    </div>

                                    <!-- Stock Preview -->
                                    <div class="col-md-5">
                                        <label class="form-label d-block">Preview Stok</label>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <div class="p-2 bg-white border rounded text-center">
                                                    <div class="text-muted small">Stok Saat Ini</div>
                                                    <div class="fw-semibold" id="currentStockPreview">
                                                        {{ optional(optional($stockMovement)->stockItem)->current_stock ?? 0 }}
                                                        {{ optional(optional($stockMovement)->stockItem)->unit ?? '' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-2 bg-white border rounded text-center">
                                                    <div class="text-muted small">Pembelian</div>
                                                    <div class="fw-semibold" id="purchaseQtyPreview">0 {{ optional(optional($stockMovement)->stockItem)->unit ?? '' }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-2 bg-white border rounded text-center">
                                                    <div class="text-muted small">Stok Setelah</div>
                                                    <div class="fw-semibold text-success" id="newStockPreview">
                                                        {{ optional(optional($stockMovement)->stockItem)->current_stock ?? 0 }}
                                                        {{ optional(optional($stockMovement)->stockItem)->unit ?? '' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nominal -->
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Nominal</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-primary text-white">Rp</span>
                            <input type="text" id="amountDisplay" class="form-control" 
                                   placeholder="0" 
                                   {{ $isStockCategory ? 'readonly' : '' }}
                                   style="background-color: {{ $isStockCategory ? '#f8fafc' : '' }};">
                            <input type="hidden" name="amount" id="amountInput" 
                                   value="{{ old('amount', $expense->amount ?? '') }}">
                        </div>
                        <div class="form-text mt-1">
                            <i class="fas fa-info-circle me-1"></i>
                            <span class="amount-hint-stock" style="{{ !$isStockCategory ? 'display:none;' : '' }}">
                                Nominal otomatis dihitung dari Qty × Harga Satuan
                            </span>
                            <span class="amount-hint-manual" style="{{ $isStockCategory ? 'display:none;' : '' }}">
                                Masukkan nominal pengeluaran
                            </span>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Deskripsi</label>
                        <input type="text" name="description" class="form-control" 
                               placeholder="Contoh: Belanja stok operasional wash" 
                               value="{{ old('description', $expense->description ?? '') }}" 
                               required>
                    </div>
                </div>

                <!-- Footer Card -->
                <div class="border-top pt-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <a href="{{ route('wash.expenses.index') }}" class="btn btn-light flex-shrink-0">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        <button type="submit" id="submitBtn" class="btn btn-primary flex-grow-1 flex-md-grow-0">
                            <span class="btn-text">
                                <i class="fas fa-save me-1"></i> {{ isset($expense) ? 'Update' : 'Simpan' }}
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sticky Footer Mobile -->
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow-lg d-md-none" style="z-index: 1030;">
    <div class="container py-3">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.expenses.index') }}" class="btn btn-outline-secondary w-50">Batal</a>
            <button type="submit" class="btn btn-primary w-50" form="createExpenseForm">
                {{ isset($expense) ? 'Update' : 'Simpan' }}
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .wash-expenses-create-page .create-panel {
        border-radius: 1rem;
    }

    .wash-expenses-create-page .form-control,
    .wash-expenses-create-page .form-select {
        min-height: 44px;
    }

    @media (max-width: 767.98px) {
        .wash-expenses-create-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 6rem !important;
        }

        .wash-expenses-create-page .create-header {
            margin-bottom: 0.8rem;
        }

        .wash-expenses-create-page .card-body {
            padding: 1rem;
        }

        .wash-expenses-create-page .border-top {
            display: none !important;
        }
    }

    [data-bs-theme="dark"] .wash-expenses-create-page .create-panel {
        background-color: #1e293b;
        border-color: #334155;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const expenseGroupSelect = document.getElementById('expenseGroupSelect');
        const stockSection = document.querySelector('.stock-section');
        const stockItemSelect = document.getElementById('stockItemSelect');
        const itemNameInput = document.getElementById('itemNameInput');
        const unitInput = document.getElementById('unitInput');
        const unitPriceInput = document.getElementById('unitPriceInput');
        const quantityInput = document.getElementById('quantityInput');
        const amountDisplay = document.getElementById('amountDisplay');
        const amountInput = document.getElementById('amountInput');
        const currentStockPreview = document.getElementById('currentStockPreview');
        const purchaseQtyPreview = document.getElementById('purchaseQtyPreview');
        const newStockPreview = document.getElementById('newStockPreview');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('createExpenseForm');

        const rupiahFormatter = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });

        // 1. Initialize amount display
        function updateAmountDisplay() {
            const value = parseFloat(amountInput.value || '0');
            amountDisplay.value = isFinite(value) ? rupiahFormatter.format(value) : '';
        }

        // 1b. Handle manual amount input (for non-stock categories)
        function handleManualAmountInput() {
            const raw = amountDisplay.value.replace(/[^0-9]/g, '');
            const num = raw === '' ? 0 : parseInt(raw, 10);
            amountInput.value = isFinite(num) ? num : '';
            amountDisplay.value = isFinite(num) && num > 0 ? rupiahFormatter.format(num) : '';
        }

        // 2. Calculate amount for stock category
        function calculateStockAmount() {
            const qty = parseFloat(quantityInput.value || '0');
            const price = parseFloat(unitPriceInput.value || '0');
            const total = qty * price;
            amountInput.value = Number.isFinite(total) ? total.toFixed(2) : '';
            updateAmountDisplay();
            updateStockPreview();
        }

        // 3. Update stock preview
        function updateStockPreview() {
            const selectedOption = stockItemSelect.options[stockItemSelect.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                const currentStock = parseFloat(selectedOption.dataset.stock || '0');
                const qty = parseFloat(quantityInput.value || '0');
                const newStock = currentStock + qty;
                const unit = selectedOption.dataset.unit || '';
                
                currentStockPreview.textContent = `${currentStock} ${unit}`;
                purchaseQtyPreview.textContent = `${qty} ${unit}`;
                newStockPreview.textContent = `${newStock} ${unit}`;
            }
        }

        // 4. Handle category change
        function handleCategoryChange() {
            const selectedOption = expenseGroupSelect.options[expenseGroupSelect.selectedIndex];
            const isStock = selectedOption.dataset.isStock === '1';
            
            stockSection.style.display = isStock ? 'block' : 'none';

            // Toggle required fields
            if (quantityInput) quantityInput.required = isStock;
            if (unitInput) unitInput.required = isStock;
            if (unitPriceInput) unitPriceInput.required = isStock;

            // Toggle amount input readonly state
            amountDisplay.readOnly = isStock;
            amountDisplay.style.backgroundColor = isStock ? '#f8fafc' : '';

            // Toggle hints
            document.querySelector('.amount-hint-stock').style.display = isStock ? '' : 'none';
            document.querySelector('.amount-hint-manual').style.display = isStock ? 'none' : '';

            // Filter stock items by category
            if (isStock) {
                const category = selectedOption.value;
                Array.from(stockItemSelect.options).forEach(option => {
                    if (option.value === '') {
                        option.style.display = 'block';
                        return;
                    }
                    const optionCat = option.dataset.category;
                    const show = optionCat === category || (category === 'caffe' && optionCat === 'kopi');
                    option.style.display = show ? 'block' : 'none';
                });
                calculateStockAmount();
            } else {
                // For non-stock, don't auto-zero (keep existing value if editing)
                updateAmountDisplay();
            }
        }

        // 5. Handle stock item select
        function handleStockItemChange() {
            const selectedOption = stockItemSelect.options[stockItemSelect.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                itemNameInput.value = selectedOption.dataset.name || '';
                unitInput.value = selectedOption.dataset.unit || 'pcs';
                unitPriceInput.value = selectedOption.dataset.price || '';
                quantityInput.value = '1';
                calculateStockAmount();
            }
        }

        // 6. Prevent double submit
        form.addEventListener('submit', function(e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }
            submitBtn.disabled = true;
            submitBtn.querySelector('.btn-text').classList.add('d-none');
            submitBtn.querySelector('.btn-loading').classList.remove('d-none');
        });

        // Event listeners
        expenseGroupSelect.addEventListener('change', handleCategoryChange);
        stockItemSelect.addEventListener('change', handleStockItemChange);
        quantityInput.addEventListener('input', calculateStockAmount);
        unitPriceInput.addEventListener('input', calculateStockAmount);
        amountDisplay.addEventListener('input', handleManualAmountInput);
        amountInput.addEventListener('input', updateAmountDisplay);

        // Initialize
        updateAmountDisplay();
        if (isStockCategory) {
            updateStockPreview();
        }
    });
</script>
@endpush
@endsection
