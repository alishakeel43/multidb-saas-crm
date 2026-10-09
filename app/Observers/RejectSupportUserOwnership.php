<?php

namespace App\Observers;

use App\Services\TenantSupportAccountService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RejectSupportUserOwnership
{
    public function saving(Model $model): void
    {
        $userId = $model->getAttribute('user_id');

        if ($userId && app(TenantSupportAccountService::class)->isSupportUserId((int) $userId)) {
            throw ValidationException::withMessages([
                'user_id' => 'The support account cannot own or be assigned to CRM records.',
            ]);
        }
    }
}
