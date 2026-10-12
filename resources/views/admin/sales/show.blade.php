@extends('layouts.admin')

@section('title', 'Detail Sale')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Detail Sale</h2>

        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
            ← Kembali
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">

            <div class="row">
                <div class="col-md-4">
                    <strong>Invoice</strong>
                    <div>{{ $sale->invoice_number }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Tanggal</strong>
                    <div>{{ $sale->sale_date->format('d-m-Y') }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Customer</strong>
                    <div>{{ $sale->customer_name ?: '-' }}</div>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Items</strong>
        </div>

        <div class="card-body p-0">

            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($sale->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product?->name ?? 'Produk tidak ditemukan' }}</td>
                            <td>{{ $item->qty }}</td>
                            <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                Tidak ada item penjualan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">TOTAL</th>
                        <th>
                            Rp {{ number_format($sale->total, 0, ',', '.') }}
                        </th>
                    </tr>
                </tfoot>

            </table>

        </div>
    </div>

@endsection