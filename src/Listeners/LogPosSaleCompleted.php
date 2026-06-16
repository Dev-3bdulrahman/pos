<?php

namespace Dev3bdulrahman\Pos\Listeners;

use App\Services\AuditLogService;
use Dev3bdulrahman\Pos\Events\PosSaleCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogPosSaleCompleted implements ShouldQueue
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function handle(PosSaleCompleted $event): void
    {
        try {
            $this->auditLogService->log(
                action: 'pos_sale_completed',
                companyId: $event->companyId,
                userId: $event->userId,
                model: $event->sale,
            );
        } catch (\Throwable $e) {
            Log::error('LogPosSaleCompleted: Failed to log audit.', [
                'error' => $e->getMessage(),
                'sale_id' => $event->sale->id,
            ]);
        }
    }
}
