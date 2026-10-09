
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th width="80">No</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Telepon</th>
            <th>Email</th>
            <th width="150">Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($suppliers as $supplier)
            <tr>
                <td>{{ $suppliers->firstItem() + $loop->index }}</td>
                <td>{{ $supplier->code }}</td>
                <td>{{ $supplier->name }}</td>
                <td>{{ $supplier->phone ?? '-' }}</td>
                <td>{{ $supplier->email ?? '-' }}</td>

                <td>
                    @if (!$isTrash)
                        <a href="{{ route('suppliers.edit', $supplier) }}"
                           class="btn btn-warning btn-sm">
                            Edit
                        </a>

                        <form action="{{ route('suppliers.destroy', $supplier) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger btn-sm">
                                Hapus
                            </button>
                        </form>
                    @else
                        <form action="{{ route('suppliers.restore', $supplier->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-success btn-sm">
                                Restore
                            </button>
                        </form>

                        <form action="{{ route('suppliers.force-delete', $supplier->id) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Data akan dihapus permanen. Lanjutkan?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger btn-sm">
                                Delete
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">
                    Belum ada data supplier.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-3">
    {{ $suppliers->links() }}
</div>
