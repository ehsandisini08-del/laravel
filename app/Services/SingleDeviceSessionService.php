<?php

namespace App\Services;

use App\Exceptions\AccountAlreadyActiveException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SingleDeviceSessionService
{
    public function activate(Model $user, string $sessionId, ?string $installationId = null): void
    {
        if ($user->active_session_id && $user->active_session_id !== $sessionId) {
            $oldSession = $user->active_session_id;

            if ($this->sessionIsAlive($oldSession)) {
                if ($user->active_installation_id && $installationId) {
                    DB::table('sessions')->where('id', $oldSession)->delete();
                } else {
                    throw new AccountAlreadyActiveException('Akun sedang aktif di perangkat lain.');
                }
            } else {
                DB::table('sessions')->where('id', $oldSession)->delete();
            }
        }

        $user->forceFill([
            'active_session_id' => $sessionId,
            'active_installation_id' => $installationId,
            'remember_token' => null,
        ])->save();
    }

    public function deactivate(Model $user): void
    {
        if ($user->active_session_id || $user->active_installation_id) {
            $user->forceFill([
                'active_session_id' => null,
                'active_installation_id' => null,
            ])->save();
        }
    }

    private function sessionIsAlive(string $sessionId): bool
    {
        $session = DB::table('sessions')->where('id', $sessionId)->first();

        if (! $session) {
            return false;
        }

        return $session->last_activity >= now()->subMinutes((int) config('session.lifetime'))->timestamp;
    }
}
