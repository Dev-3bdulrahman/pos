<?php

namespace Dev3bdulrahman\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Dev3bdulrahman\Crm\Models\Customer;
use App\Models\User;

class PosSale extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $table = 'pos_sales';

    protected $fillable = [
        'company_id',
        'shift_id',
        'session_id',
        'customer_id',
        'code',
        'subtotal',
        'discount',
        'tax',
        'total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax'      => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'shift_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'pos_sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_sale_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
