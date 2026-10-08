<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with('product');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $products = Product::orderBy('name')->get();

        return view('admin.stock-movements.index', compact(
            'movements',
            'products'
        ));
    }
}