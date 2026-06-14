<?php

namespace Dev3bdulrahman\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Dev3bdulrahman\Inventory\Models\Warehouse;

class PosTerminal extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $table = 'pos_terminals';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'warehouse_id',
        'status',
        'created_by',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(PosShift::class, 'terminal_id');
    }
}
