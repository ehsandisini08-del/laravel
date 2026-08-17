<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SingleDeviceSessionService
{
    public function activate(Model $user, string $sessionId): void
    {
        if ($user->active_session_id && $user->active_session_id !== $sessionId) {
            DB::table('sessions')->where('id', $user->active_session_id)->delete();
        }

        $user->forceFill([
            'active_session_id' => $sessionId,
            'remember_token' => null,
        ])->save();
    }

    public function deactivate(Model $user): void
    {
        if ($user->active_session_id) {
            $user->forceFill(['active_session_id' => null])->save();
        }
    }
}
