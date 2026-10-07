<div class="list-group rounded-0">

    <a href="{{ route('dashboard') }}"
        class="list-group-item list-group-item-action">
        <i class="nav-icon bi bi-speedometer2"></i>
        Dashboard
    </a>

    <a href="{{ route('categories.index') }}"
        class="list-group-item list-group-item-action">
        <i class="nav-icon bi bi-tags"></i>
        Kategori
    </a>

    <a href="{{ route('products.index') }}"
        class="list-group-item list-group-item-action">
        <i class="nav-icon bi bi-box-seam"></i>
        Produk
    </a>

    <a href="{{ route('suppliers.index') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
        <i class="nav-icon bi bi-truck"></i>
        Supplier
    </a>

    <a href="{{ route('purchases.index') }}"
        class="list-group-item list-group-item-action">
        <i class="nav-icon bi-cart-check"></i>
        Pesanan
    </a>

    <a href="{{ route('sales.index') }}"
    class="list-group-item list-group-item-action">
        <i class="nav-icon bi bi-receipt"></i>
        Sales
    </a>

    <a href="{{ route('stock-movements.index') }}"
    class="list-group-item list-group-item-action">
        <i class="bi bi-arrow-left-right"></i>
        Stock Movements
    </a>

    <a href="#"
        class="list-group-item list-group-item-action">
        <i class="nav-icon bi bi-people"></i>
        Pelanggan
    </a>

</div>