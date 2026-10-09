<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Purchase;

class Supplier extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
