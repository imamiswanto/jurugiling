@extends('layouts.admin')

@section('title', 'Stock Movements')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Stock Movements</h2>
    </div>
    <form method="GET" action="{{ route('stock-movements.index') }}" class="row g-3 mb-4">

        <div class="col-md-5">
            <label class="form-label">Produk</label>

            <select name="product_id" class="form-select">
                <option value="">-- Semua Produk --</option>

                @foreach($products as $product)
                    <option
                        value="{{ $product->id }}"
                        {{ request('product_id') == $product->id ? 'selected' : '' }}
                    >
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Tipe</label>

            <select name="type" class="form-select">
                <option value="">-- Semua Tipe --</option>

                <option
                    value="purchase"
                    {{ request('type') === 'purchase' ? 'selected' : '' }}
                >
                    Purchase
                </option>

                <option
                    value="sale"
                    {{ request('type') === 'sale' ? 'selected' : '' }}
                >
                    Sale
                </option>

                <option
                    value="adjustment"
                    {{ request('type') === 'adjustment' ? 'selected' : '' }}
                >
                    Adjustment
                </option>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary">
                Filter
            </button>

            <a
                href="{{ route('stock-movements.index') }}"
                class="btn btn-secondary"
            >
                Reset
            </a>
        </div>

    </form>
    <div class="card">
        <div class="card-header">
            <strong>Riwayat Stock</strong>
        </div>

        <div class="card-body p-0">

            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Tipe</th>
                        <th>Quantity</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($movements as $index => $movement)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                {{ $movement->created_at->format('d-m-Y H:i') }}
                            </td>

                            <td>
                                {{ $movement->product->name }}
                            </td>

                            <td>
                                @if($movement->type === 'purchase')
                                    <span class="badge bg-success">
                                        Purchase
                                    </span>
                                @elseif($movement->type === 'sale')
                                    <span class="badge bg-danger">
                                        Sale
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        Adjustment
                                    </span>
                                @endif
                            </td>

                            <td class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                <strong>
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </strong>
                            </td>

                            <td>
                                {{ $movement->note ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada stock movement.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
            <div class="mt-3 px-3 pb-3">
                {{ $movements->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

@endsection