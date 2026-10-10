<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('items.product')
            ->latest('sale_date')
            ->latest()
            ->get();

        return view('admin.sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:255', 'unique:sales,invoice_number'],
            'sale_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('is_active', true),
            ],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($data) {

            $sale = Sale::create([
                'invoice_number' => $data['invoice_number'],
                'sale_date' => $data['sale_date'],
                'customer_name' => $data['customer_name'] ?? null,
                'total' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {

                $product = Product::whereKey($item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock < $item['qty']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}.",
                    ]);
                }

                $subtotal = $item['qty'] * $product->selling_price;

                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $product->selling_price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['qty']);

                $product->stockMovements()->create([
                    'type' => 'sale',
                    'quantity' => -$item['qty'],
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'note' => 'Penjualan ' . $sale->invoice_number,
                ]);

                $total += $subtotal;
            }

            $sale->update([
                'total' => $total,
            ]);
        });

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale berhasil disimpan.');
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product');

        return view('admin.sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $sale->load('items.product');

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.sales.edit', compact('sale', 'products'));
    }

    public function update(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                'unique:sales,invoice_number,' . $sale->id,
            ],
            'sale_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('is_active', true),
            ],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($data, $sale) {

            $sale->load('items');

            
            $oldItems = $sale->items
                ->groupBy('product_id')
                ->map(function ($items) {
                    return [
                        'qty' => $items->sum('qty'),
                    ];
                });


            $newItems = collect($data['items'])
                ->groupBy('product_id')
                ->map(function ($items) {
                    return [
                        'qty' => $items->sum('qty'),
                    ];
                });

            
            $productIds = $oldItems->keys()
                ->merge($newItems->keys())
                ->unique()
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;

            foreach ($productIds as $productId) {
                $product = $products->get($productId);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu produk tidak ditemukan.',
                    ]);
                }

                $oldQty = $oldItems->get($productId)['qty'] ?? 0;
                $newQty = $newItems->get($productId)['qty'] ?? 0;

                $delta = $newQty - $oldQty;

                if ($delta > 0) {
                    if ($product->stock < $delta) {
                        throw ValidationException::withMessages([
                            'items' => "Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}, tambahan kebutuhan: {$delta}.",
                        ]);
                    }

                    $product->decrement('stock', $delta);
                } elseif ($delta < 0) {
                    $product->increment('stock', abs($delta));
                }

                if ($newQty > 0) {
                    $total += $newQty * $product->selling_price;
                }

                if ($delta != 0) {
                    $product->stockMovements()->create([
                        'type' => 'adjustment',
                        'quantity' => -$delta,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'note' => 'Perubahan penjualan ' . $sale->invoice_number,
                    ]);
                }
            }


            $sale->items()->delete();

            foreach ($newItems as $productId => $item) {

                $subtotal = $item['qty'] * $products->get($productId)->selling_price;

                $sale->items()->create([
                    'product_id' => $productId,
                    'qty' => $item['qty'],
                    'price' => $products->get($productId)->selling_price,
                    'subtotal' => $subtotal,
                ]);
            }

            $sale->update([
                'invoice_number' => $data['invoice_number'],
                'sale_date' => $data['sale_date'],
                'customer_name' => $data['customer_name'] ?? null,
                'total' => $total,
            ]);
        });

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale berhasil diperbarui.');
    }

    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {

            $sale->load('items');

            foreach ($sale->items as $item) {
                $product = Product::findOrFail($item->product_id);

                // Kembalikan stock karena sale dibatalkan
                $product->increment('stock', $item->qty);

                // Catat perubahan stock
                $product->stockMovements()->create([
                    'type' => 'adjustment',
                    'quantity' => $item->qty,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'note' => 'Pembatalan penjualan ' . $sale->invoice_number,
                ]);
            }

            // Soft delete sale
            $sale->delete();
        });

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale berhasil dihapus.');
    }
}
