<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{

public function index(Request $request): JsonResponse
{
    $filters = $request->validate([
        'search' => ['nullable', 'string', 'max:100'],
        'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
    ]);

    $products = Product::query()
        ->with('category')
        ->where('is_active', true)
        ->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        })
        ->orderBy('name')
        ->paginate($filters['per_page'] ?? 10)
        ->withQueryString();

    return response()->json([
        'message' => 'Daftar produk berhasil diambil.',
        'data' => ProductResource::collection(
            $products->getCollection()
        ),
        'meta' => [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            ],
        ]);
    }


    public function show(Product $product): JsonResponse
    {
        abort_if(! $product->is_active, 404);

        $product->load('category');

        return response()->json([
            'message' => 'Detail produk berhasil diambil.',
            'data' => new ProductResource($product),
        ]);
    }
}