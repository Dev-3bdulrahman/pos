<?php

namespace Dev3bdulrahman\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class PosShift extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $table = 'pos_shifts';

    protected $fillable = [
        'company_id',
        'user_id',
        'terminal_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'expected_closing_balance',
        'actual_closing_balance',
        'difference',
        'notes',
        'status',
    ];

    protected $casts = [
        'opening_balance'          => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'actual_closing_balance'   => 'decimal:2',
        'difference'               => 'decimal:2',
        'opened_at'                => 'datetime',
        'closed_at'                => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'terminal_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'shift_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'shift_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'shift_id');
    }
}
