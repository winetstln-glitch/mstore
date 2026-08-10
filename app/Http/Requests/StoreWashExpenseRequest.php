<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWashExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isStock = in_array($this->input('expense_group'), ['shampoo', 'snack', 'caffe']);

        return [
            'transaction_date' => 'required|date',
            'expense_group' => 'required|in:shampoo,snack,caffe,uang_makan,insentif,lembur,lainnya',
            'stock_item_id' => 'nullable|exists:wash_stock_items,id',
            'item_name' => 'required_without:stock_item_id|nullable|string|max:100',
            'unit' => 'required_if:expense_group,shampoo,snack,caffe|nullable|string|max:20',
            'quantity' => 'required_if:expense_group,shampoo,snack,caffe|nullable|numeric|min:0.01',
            'unit_price' => 'required_if:expense_group,shampoo,snack,caffe|nullable|numeric|min:0',
            'amount' => $isStock ? 'nullable|numeric|min:0' : 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal transaksi wajib diisi',
            'expense_group.required' => 'Kategori pengeluaran wajib diisi',
            'item_name.required_without' => 'Nama item wajib diisi jika tidak memilih stok yang ada',
            'unit.required_if' => 'Satuan wajib diisi untuk kategori stok',
            'quantity.required_if' => 'Qty wajib diisi untuk kategori stok',
            'unit_price.required_if' => 'Harga satuan wajib diisi untuk kategori stok',
            'amount.required' => 'Nominal pengeluaran wajib diisi',
            'amount.min' => 'Nominal pengeluaran harus lebih dari 0',
            'description.required' => 'Deskripsi wajib diisi',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize expense group
        if ($this->has('expense_group')) {
            $group = strtolower(trim($this->input('expense_group')));
            $this->merge([
                'expense_group' => $group === 'kopi' ? 'caffe' : $group,
            ]);
        }
    }

    public function isStockCategory(): bool
    {
        return in_array($this->input('expense_group'), ['shampoo', 'snack', 'caffe']);
    }

    public function getCalculatedAmount(): float
    {
        if ($this->isStockCategory()) {
            return (float) $this->input('quantity', 0) * (float) $this->input('unit_price', 0);
        }
        return (float) $this->input('amount', 0);
    }
}
