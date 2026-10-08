<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\StockMovement;

class Product extends Model
{
    public function category(): BelongsTo {
    return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    protected $fillable = [
    'category_id',
    'sku',
    'name',
    'slug',
    'description',
    'purchase_price',
    'selling_price',
    'minimum_stock',
    'image',
    'is_active',
    ];
}
