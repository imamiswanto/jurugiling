<div class="mb-3">

        <label class="form-label">
            Kategori
        </label>
        <div class="mb-3">
    <select
        name="category_id"
        class="form-select @error('category_id') is-invalid @enderror"
    >
        <option value="">-- Pilih Kategori --</option>
        @foreach($categories as $category)
            <option
                value="{{ $category->id }}"
                {{ old('category_id', $product->category_id??'') == $category->id ? 'selected' : '' }}
            >
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id')
        <div class="invalid-feedback">{{ 'The category field is required.' }}</div>
    @enderror

    <div class="mb-3">
    <label class="form-label">SKU</label>

    <input
        type="text"
        name="sku"
        class="form-control @error('sku') is-invalid @enderror"
        value="{{ old('sku', $product->sku??'')  }}"
    >
    @error('sku')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    </div>

    <div class="mb-3">
    <label class="form-label">Nama Produk</label>

    <input
        type="text"
        name="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $product->name??'') }}"
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    </div>
    
    @if (!isset($product))
    <div class="mb-3">
        <label class="form-label">Harga Modal</label>

        <input
            type="number"
            name="purchase_price"
            class="form-control @error('purchase_price') is-invalid @enderror"
            value="{{ old('purchase_price') }}"
        >

        @error('purchase_price')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>
    @endif

    <div class="mb-3">
    <label class="form-label">Harga Jual</label>

    <input
        type="number"
        name="selling_price"
        class="form-control @error('selling_price') is-invalid @enderror"
        value="{{ old('selling_price', $product->selling_price??'') }}"
    >
    @error('selling_price')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    </div>

    <div class="mb-3">
    <label class="form-label">Minimum Stok</label>

    <input
        type="number"
        name="minimum_stock"
        class="form-control @error('minimum_stock') is-invalid @enderror"
        value="{{ old('minimum_stock', $product->minimum_stock??'') }}"
    >
    @error('minimum_stock')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    </div>

    <div class="mb-3">

    <label class="form-label">
        Deskripsi
    </label>

    <textarea
        name="description"
        rows="4"
        class="form-control"
    >
    {{ old('description', $product->description??'') }}
    </textarea>
    </div>
    <div class="mb-3">
    <label class="form-label">Gambar Produk</label>

    @if (!empty($product?->image))
    <div class="mb-3">
        <label class="form-label">Gambar Saat Ini</label>

        <div>
            <img
                src="{{ asset('storage/' . $product->image) }}"
                alt="{{ $product->name }}"
                class="img-thumbnail"
                style="max-width: 200px"
            >
        </div>
    </div>
    @endif
    <input
        type="file"
        name="image"
        class="form-control @error('image') is-invalid @enderror"
    >

    @error('image')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    </div>

    <div class="form-check mb-3">

    <input
        class="form-check-input"
        type="checkbox"
        name="is_active"
        value="1"
        checked
    >

    <label class="form-check-label">

        Produk Aktif

    </label>
    </div>