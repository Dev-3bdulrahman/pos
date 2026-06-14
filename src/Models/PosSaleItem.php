<?php

namespace Dev3bdulrahman\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class PosSaleItem extends Model
{
    protected $table = 'pos_sale_items';

    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'tax'        => 'decimal:2',
        'subtotal'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
