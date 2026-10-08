@extends('layouts.admin')

@section('title', 'Master Produk')

@section('content')

<h2 class="mb-4">
    Master Produk
</h2>

<a href="{{ route('products.create') }}"
    class="btn btn-primary mb-3">
    <i class="bi bi-plus-circle"></i>
    Tambah Produk
</a>
@if(session('success'))

<div class="alert alert-success">
    {{ session('success') }}
</div>

@endif

<table class="table table-bordered">

    <thead>

        <tr>

            <th>No</th>

            <th>SKU</th>

            <th>Nama</th>

            <th>Harga Beli</th>

            <th>Harga Jual</th>

            <th>Stok</th>

            <th>Aksi</th>

        </tr>

    </thead>

    <tbody>

        @forelse($products as $product)

            <tr>

                <td>{{ $loop->iteration }}</td>

                <td>{{ $product->sku }}</td>

                <td>{{ $product->name }}</td>

                <td>Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>

                <td>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>

                <td>{{ $product->stock }}</td>

                <td>
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">
                        Edit
                    </a>
                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>

        @empty

            <tr>

                <td colspan="7" class="text-center">

                    Belum ada produk.

                </td>
            </tr>
        @endforelse

    </tbody>

</table>

@endsection