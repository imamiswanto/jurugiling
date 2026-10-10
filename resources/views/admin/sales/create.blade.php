@extends('layouts.admin')

@section('title', 'Tambah Sale')

@section('content')

    <h2 class="mb-4">Tambah Sale</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="invoice_number" class="form-label">
                Invoice Number
            </label>

            <input type="text"
                   name="invoice_number"
                   id="invoice_number"
                   class="form-control"
                   value="{{ old('invoice_number') }}"
                   required>
        </div>

        <div class="mb-3">
            <label for="sale_date" class="form-label">
                Tanggal
            </label>

            <input type="date"
                   name="sale_date"
                   id="sale_date"
                   class="form-control"
                   value="{{ old('sale_date', now()->format('Y-m-d')) }}"
                   required>
        </div>

        <div class="mb-3">
            <label for="customer_name" class="form-label">
                Customer
            </label>

            <input type="text"
                   name="customer_name"
                   id="customer_name"
                   class="form-control"
                   value="{{ old('customer_name') }}">
        </div>

        <hr>

        <h5 class="mb-3">Items</h5>

        <div id="items-container">

            <div class="row item-row mb-3">

                <div class="col-md-5">
                    <label class="form-label">Product</label>

                    <select name="items[0][product_id]"
                            class="form-select"
                            required>

                        <option value="">-- Pilih Product --</option>

                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                    data-price="{{ $product->selling_price }}">
                                {{ $product->name }}
                                (Stock: {{ $product->stock }})
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Qty</label>

                    <input type="number"
                           name="items[0][qty]"
                           class="form-control"
                           min="0.01"
                           step="0.01"
                           required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Harga</label>

                    <input type="number"
                        name="items[0][price]"
                        class="form-control price-input"
                        min="0"
                        step="0.01"
                        readonly
                        required>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="button"
                            class="btn btn-danger remove-item">
                        Hapus
                    </button>
                </div>

            </div>

        </div>

        <button type="button"
                id="add-item"
                class="btn btn-secondary mb-4">
            + Tambah Item
        </button>

        <div>
            <button type="submit" class="btn btn-primary">
                Simpan
            </button>

            <a href="{{ route('sales.index') }}"
               class="btn btn-secondary">
                Batal
            </a>
        </div>

    </form>


    <script>
    let itemIndex = 1;

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

            if (!name) {
                return;
            }

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
        if (!event.target.classList.contains('remove-item')) {
            return;
        }

        const rows = document.querySelectorAll('.item-row');

        if (rows.length === 1) {
            return;
        }

        event.target.closest('.item-row').remove();
    });
</script>


@endsection