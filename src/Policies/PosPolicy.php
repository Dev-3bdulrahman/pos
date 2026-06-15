<?php

namespace Dev3bdulrahman\Pos\Policies;

use App\Models\User;
use Dev3bdulrahman\Pos\Models\PosSale;
use Dev3bdulrahman\Pos\Models\PosSession;

class PosPolicy
{
    public function viewAnySessions(User $user): bool
    {
        return $user->can('pos.sessions.view');
    }

    public function viewSession(User $user, PosSession $session): bool
    {
        return $user->can('pos.sessions.view') && $session->company_id === $user->company_id;
    }

    public function createSession(User $user): bool
    {
        return $user->can('pos.sessions.create');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('pos.sales.view');
    }

    public function view(User $user, PosSale $sale): bool
    {
        return $user->can('pos.sales.view') && $sale->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('pos.sales.create');
    }

    public function delete(User $user, PosSale $sale): bool
    {
        return $user->can('pos.sales.delete') && $sale->company_id === $user->company_id;
    }
}
