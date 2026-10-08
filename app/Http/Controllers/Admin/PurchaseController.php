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
            $total = 0;
            $purchase = Purchase::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'total' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $subtotal = $item['qty'] * $item['price'];
                
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);
                
                $product = Product::findOrFail($item['product_id']);
                $product->increment('stock', $item['qty']);
                $this->syncProductPurchasePrice($product);
                $product->stockMovements()->create([
                    'type' => 'purchase',
                    'quantity' => $item['qty'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Stock masuk dari purchase ' . $purchase->invoice_number,
                ]);
                
                $total += $subtotal;
                
                $purchase->update([
                    'total' => $total,
                ]);
            }
        });
        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase berhasil dibuat.');
    }

    private function generateInvoiceNumber(): string 
    {
        $date = now()->format('Ymd');
        $lastPurchase = Purchase::whereDate('created_at', now()->toDateString())
            ->latest('id')
            ->first();
        $sequence = $lastPurchase ? ((int) substr($lastPurchase->invoice_number, -4)) + 1 : 1;
        
        return 'PO-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function syncProductPurchasePrice(Product $product): void
    {
        $latestPurchaseItem = PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase')
            ->latest('id')
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

            $purchase->load('items');

            $oldItems = $purchase->items->keyBy('product_id');

            $newItems = collect($data['items'])
                ->keyBy('product_id');

            $allProductIds = $oldItems->keys()
                ->merge($newItems->keys())
                ->unique();

            $removedProductIds = $oldItems->keys()
                ->diff($newItems->keys());

            foreach ($allProductIds as $productId) {

                $oldQty = $oldItems->get($productId)?->qty ?? 0;
                $newQty = $newItems->get($productId)['qty'] ?? 0;

                $delta = $newQty - $oldQty;

                if ($delta == 0) {
                    continue;
                }

                $product = Product::findOrFail($productId);

                if ($delta < 0 && $product->stock < abs($delta)) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi untuk perubahan purchase ini.",
                    ]);
                }

                $product->increment('stock', $delta);

                $product->stockMovements()->create([
                    'type' => 'adjustment',
                    'quantity' => $delta,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Penyesuaian stok dari update purchase ' . $purchase->invoice_number,
                ]);
            }

            foreach ($removedProductIds as $productId) {
                $product = Product::findOrFail($productId);

                $this->syncProductPurchasePrice($product);
            }

            // Update purchase
            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
            ]);

            // Hapus item lama
            $purchase->items()->delete();

            // Buat item baru
            $total = 0;

            foreach ($data['items'] as $item) {

                $subtotal = $item['qty'] * $item['price'];

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                $product = Product::findOrFail($item['product_id']);
                
                $this->syncProductPurchasePrice($product);

                $total += $subtotal;
            }

            // Update total purchase
            $purchase->update([
                'total' => $total,
            ]);
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
       //dd($purchase->id, $purchase->invoice_number);
        $purchase->load('items');

        // Simpan product yang terdampak sebelum item dihapus
        $affectedProductIds = $purchase->items
            ->pluck('product_id')
            ->unique();

        // Cek semua stok terlebih dahulu
        foreach ($purchase->items as $item) {
            $product = Product::findOrFail($item->product_id);

            if ($product->stock < $item->qty) {
                return back()->with(
                    'error',
                    "Purchase tidak dapat dihapus karena stok {$product->name} tidak mencukupi."
                );
            }
        }

        DB::transaction(function () use (
            $purchase,
            $affectedProductIds
        ) {

            foreach ($purchase->items as $item) {
                $product = Product::findOrFail($item->product_id);

                // Kurangi stok
                $product->decrement('stock', $item->qty);

                // Catat perubahan stok
                $product->stockMovements()->create([
                    'type' => 'adjustment',
                    'quantity' => -$item->qty,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Penyesuaian stok dari penghapusan purchase ' . $purchase->invoice_number,
                ]);
            }

            // Hapus item purchase
            $purchase->items()->delete();

            // Soft delete purchase
            $purchase->delete();

            // Sync ulang harga beli terakhir
            foreach ($affectedProductIds as $productId) {
                $product = Product::find($productId);

                if ($product) {
                    $this->syncProductPurchasePrice($product);
                }
            }
        });

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase berhasil dihapus.');
    }
}
