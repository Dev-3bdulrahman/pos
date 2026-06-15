<?php

namespace Dev3bdulrahman\Pos\Events;

use Dev3bdulrahman\Pos\Models\PosSale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosSaleCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosSale $sale,
        public int $userId,
        public int $companyId,
    ) {}
}
