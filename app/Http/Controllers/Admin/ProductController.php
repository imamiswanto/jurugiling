<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $products = Product::with('category')
        ->latest()
        ->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $image = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image')->store('products', 'public');
            }

        Product::create([

        'category_id' => $request->category_id,

        'sku' => strtoupper($request->sku),

        'name' => $request->name,

        'slug' => Str::slug($request->name),

        'description' => $request->description,

        'purchase_price' => $request->purchase_price,

        'selling_price' => $request->selling_price,

        'minimum_stock' => $request->minimum_stock,

        'is_active' => $request->boolean('is_active'),
        
        'image' => $image,

    ]);

    return redirect()
        ->route('products.index')
        ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view(
        'admin.products.edit',
        compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $image = $product->image;
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
                }
                $image = $request->file('image')->store('products', 'public');
                }

        $product->update([

        'category_id' => $request->category_id,

        'sku' => strtoupper($request->sku),

        'name' => $request->name,

        'slug' => Str::slug($request->name),

        'description' => $request->description,

        'selling_price' => $request->selling_price,

        'minimum_stock' => $request->minimum_stock,
        
        'image' => $image,
        
        'is_active' => $request->boolean('is_active'),

    ]);

    return redirect()
        ->route('products.index')
        ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if (
            $product->purchaseItems()->exists() ||
            $product->saleItems()->exists() ||
            $product->stockMovements()->exists()
        ) {
            $product->update([
                'is_active' => false,
            ]);

            return redirect()
                ->route('products.index')
                ->with(
                    'success',
                    'Produk sudah digunakan dalam transaksi dan dinonaktifkan.'
                );
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
