<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchases = Purchase::with('supplier')
            ->latest('purchase_date')
            ->paginate(10);
        return view('admin.purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();
        return view('admin.purchases.create', compact(
            'suppliers',
            'products'
            ));
    }



    /**
     * Store a newly created resource in storage.
     */

    public function store(StorePurchaseRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            // Kunci semua produk dalam urutan ID yang konsisten.
            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak ditemukan.',
                ]);
            }

            $purchase = Purchase::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'total' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                $subtotal = $item['qty'] * $item['price'];

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                $product->increment('stock', $item['qty']);

                $product->stockMovements()->create([
                    'type' => 'purchase',
                    'quantity' => $item['qty'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Stock masuk dari purchase '
                        . $purchase->invoice_number,
                ]);

                $total += $subtotal;
            }

            $purchase->update([
                'total' => $total,
            ]);

            // Sinkronkan sekali per produk setelah semua item tersimpan.
            foreach ($productIds as $productId) {
                $this->syncProductPurchasePrice(
                    $products->get($productId)
                );
            }
        });

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase berhasil dibuat.');
    }




    private function generateInvoiceNumber(): string
    {
        $date = now()->toDateString();
        $dateForInvoice = now()->format('Ymd');
        $prefix = 'PO-' . $dateForInvoice . '-';

        DB::table('purchase_invoice_sequences')->insertOrIgnore([
            'invoice_date' => $date,
            'last_number' => 0,
        ]);

        // Kunci sequence agar request bersamaan tidak mengambil nomor sama.
        $sequence = DB::table('purchase_invoice_sequences')
            ->where('invoice_date', $date)
            ->lockForUpdate()
            ->first();

        // Cari nomor terbesar yang sudah dipakai pada tanggal ini.
        $maxExistingNumber = Purchase::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->selectRaw(
                'MAX(CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED)) as max_number'
            )
            ->value('max_number');

        // Gunakan angka terbesar dari sequence dan invoice yang sudah ada.
        $lastNumber = max(
            (int) $sequence->last_number,
            (int) ($maxExistingNumber ?? 0)
        );

        $nextNumber = $lastNumber + 1;

        DB::table('purchase_invoice_sequences')
            ->where('invoice_date', $date)
            ->update(['last_number' => $nextNumber]);

        return $prefix . str_pad(
            (string) $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }




    private function syncProductPurchasePrice(Product $product): void
    {
        $latestPurchaseItem = PurchaseItem::query()
            ->where('product_id', $product->id)
            ->whereHas('purchase')
            ->orderByDesc(
                Purchase::query()
                    ->select('purchase_date')
                    ->whereColumn(
                        'purchases.id',
                        'purchase_items.purchase_id'
                    )
            )
            ->orderByDesc('id')
            ->first();

        $product->update([
            'purchase_price' => $latestPurchaseItem?->price ?? 0,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
        ]);
        return view('admin.purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
        ]);

        $suppliers = Supplier::orderBy('name')
            ->get();

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.purchases.edit', compact(
            'purchase',
            'suppliers',
            'products'
        ));
    }

    /**
     * Update the specified resource in storage.
     */

public function update(Request $request, Purchase $purchase)
{
    $data = $request->validate([
        'supplier_id' => ['required', 'exists:suppliers,id'],
        'purchase_date' => ['required', 'date'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'exists:products,id'],
        'items.*.qty' => ['required', 'numeric', 'gt:0'],
        'items.*.price' => ['required', 'numeric', 'gte:0'],
    ]);

    DB::transaction(function () use ($data, $purchase) {
        // Kunci transaksi pembelian agar tidak diedit bersamaan.
        $purchase = Purchase::whereKey($purchase->id)
            ->lockForUpdate()
            ->firstOrFail();

        $purchase->load('items');

        // Jumlahkan qty lama per produk.
        $oldItems = $purchase->items
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'qty' => $items->sum('qty'),
            ]);

        // Jumlahkan qty baru per produk untuk perhitungan stok.
        $newItems = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'qty' => $items->sum('qty'),
            ]);

        $productIds = $oldItems->keys()
            ->merge($newItems->keys())
            ->unique()
            ->sort()
            ->values();

        // Kunci produk dengan urutan ID yang konsisten.
        $products = Product::whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Salah satu produk tidak ditemukan.',
            ]);
        }

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            $oldQty = $oldItems->get($productId)['qty'] ?? 0;
            $newQty = $newItems->get($productId)['qty'] ?? 0;

            $delta = $newQty - $oldQty;

            if ($delta === 0 || (float) $delta == 0.0) {
                continue;
            }

            // Pengurangan jumlah pembelian mengurangi stok.
            if ($delta < 0) {
                if ($product->stock < abs($delta)) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi untuk perubahan purchase ini.",
                    ]);
                }

                $product->decrement('stock', abs($delta));
            } else {
                $product->increment('stock', $delta);
            }

            $product->stockMovements()->create([
                'type' => 'adjustment',
                'quantity' => $delta,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'note' => 'Penyesuaian stok dari update purchase '
                    . $purchase->invoice_number,
            ]);
        }

        $purchase->update([
            'supplier_id' => $data['supplier_id'],
            'purchase_date' => $data['purchase_date'],
        ]);

        // Ganti detail pembelian setelah perhitungan stok selesai.
        $purchase->items()->delete();

        $total = 0;

        foreach ($data['items'] as $item) {
            $subtotal = $item['qty'] * $item['price'];

            $purchase->items()->create([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        $purchase->update([
            'total' => $total,
        ]);

        // Sinkronkan harga modal setelah detail terbaru tersimpan.
        foreach ($productIds as $productId) {
            $this->syncProductPurchasePrice(
                $products->get($productId)
            );
        }
    });

    return redirect()
        ->route('purchases.show', $purchase)
        ->with('success', 'Purchase berhasil diperbarui.');
    }


    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Purchase $purchase)
    {
        $result = DB::transaction(function () use ($purchase) {
            // Kunci purchase agar tidak diubah bersamaan.
            $purchase = Purchase::whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            $items = $purchase->items()->get();

            $productIds = $items->pluck('product_id')
                ->unique()
                ->sort()
                ->values();

            // Kunci semua produk dalam urutan ID yang konsisten.
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak ditemukan.',
                ]);
            }

            // Hitung total qty yang akan dikurangi per produk.
            $requiredQty = $items
                ->groupBy('product_id')
                ->map(fn ($productItems) => $productItems->sum('qty'));

            // Periksa seluruh stok sebelum melakukan perubahan.
            foreach ($requiredQty as $productId => $qty) {
                $product = $products->get($productId);

                if ($product->stock < $qty) {
                    return back()->with(
                        'error',
                        "Purchase tidak dapat dihapus karena stok {$product->name} tidak mencukupi."
                    );
                }
            }

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                $product->decrement('stock', $item->qty);

                $product->stockMovements()->create([
                    'type' => 'adjustment',
                    'quantity' => -$item->qty,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Penyesuaian stok dari penghapusan purchase '
                        . $purchase->invoice_number,
                ]);
            }

            // Hapus detail dan soft-delete purchase dalam transaksi yang sama.
            $purchase->items()->delete();
            $purchase->delete();

            // Hitung ulang harga modal berdasarkan purchase aktif terbaru.
            foreach ($productIds as $productId) {
                $this->syncProductPurchasePrice(
                    $products->get($productId)
                );
            }

            return null;
        });

        if ($result !== null) {
            return $result;
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase berhasil dihapus.');
    }

}
