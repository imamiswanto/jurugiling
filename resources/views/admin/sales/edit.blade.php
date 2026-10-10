@extends('layouts.admin')

@section('title', 'Edit Sale')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Sale</h2>

        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
            ← Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sales.update', $sale) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Invoice Number</label>
                        <input
                            type="text"
                            name="invoice_number"
                            class="form-control"
                            value="{{ old('invoice_number', $sale->invoice_number) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal</label>
                        <input
                            type="date"
                            name="sale_date"
                            class="form-control"
                            value="{{ old('sale_date', $sale->sale_date->format('Y-m-d')) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer</label>
                        <input
                            type="text"
                            name="customer_name"
                            class="form-control"
                            value="{{ old('customer_name', $sale->customer_name) }}"
                        >
                    </div>

                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>Items</strong>
            </div>

            <div class="card-body">

                <div id="items-container">

                    @foreach($sale->items as $index => $item)
                        <div class="row item-row mb-3">

                            <div class="col-md-5">
                                <label class="form-label">Produk</label>

                                <select
                                    name="items[{{ $index }}][product_id]"
                                    class="form-select"
                                    required
                                >
                                    <option value="">-- Pilih Produk --</option>

                                    @foreach($products as $product)
                                        <option
                                            value="{{ $product->id }}"
                                            {{ old("items.$index.product_id", $item->product_id) == $product->id ? 'selected' : '' }}
                                        data-price="{{ $product->selling_price }}">
                                            {{ $product->name }} (Stock: {{ $product->stock }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Qty</label>

                                <input
                                    type="number"
                                    name="items[{{ $index }}][qty]"
                                    class="form-control"
                                    step="0.01"
                                    min="0.01"
                                    value="{{ old("items.$index.qty", $item->qty) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Harga</label>

                                <input type="number"
                                    name="items[0][price]"
                                    class="form-control price-input"
                                    min="0"
                                    step="0.01"
                                    readonly
                                    value="{{ $item->product->selling_price }}"
                                    required>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button
                                    type="button"
                                    class="btn btn-danger remove-item"
                                >
                                    Hapus
                                </button>
                            </div>

                        </div>
                    @endforeach

                </div>

                <button
                    type="button"
                    id="add-item"
                    class="btn btn-secondary"
                >
                    + Tambah Item
                </button>

            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                Update Sale
            </button>

            <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>

    </form>

    <script>
        let itemIndex = {{ $sale->items->count() }};

        document.getElementById('items-container').addEventListener('change', function (event) {
        if (!event.target.matches('select[name$="[product_id]"]')) {
            return;
            }

            const row = event.target.closest('.item-row');
            const selectedOption = event.target.selectedOptions[0];
            const priceInput = row.querySelector('.price-input');

            priceInput.value = selectedOption
                ? selectedOption.dataset.price || ''
                : '';
        });
        
        document.getElementById('add-item').addEventListener('click', function () {
            const container = document.getElementById('items-container');
            const firstRow = container.querySelector('.item-row');

            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('input, select').forEach(function (element) {
                const name = element.getAttribute('name');

                if (!name) return;

                element.setAttribute(
                    'name',
                    name.replace(/\[\d+\]/, '[' + itemIndex + ']')
                );

                if (element.tagName === 'SELECT') {
                    element.selectedIndex = 0;
                } else {
                    element.value = '';
                }
            });

            container.appendChild(newRow);
            itemIndex++;
        });

        document.getElementById('items-container').addEventListener('click', function (event) {
            if (!event.target.classList.contains('remove-item')) return;

            const rows = document.querySelectorAll('.item-row');

            if (rows.length === 1) return;

            event.target.closest('.item-row').remove();
        });
    </script>

@endsection