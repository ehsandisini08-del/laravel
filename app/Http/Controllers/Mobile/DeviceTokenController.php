<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function storeCustomer(Request $request)
    {
        $data = $this->validated($request);

        $this->upsert(DeviceToken::TYPE_CUSTOMER, auth('customer')->id(), $data);

        return response()->json(['success' => true]);
    }

    public function destroyCustomer(Request $request, string $token)
    {
        $this->remove(DeviceToken::TYPE_CUSTOMER, auth('customer')->id(), $token);

        return response()->json(['success' => true]);
    }

    public function storeAdmin(Request $request)
    {
        $data = $this->validated($request);

        $this->upsert(DeviceToken::TYPE_ADMIN, auth()->id(), $data);

        return response()->json(['success' => true]);
    }

    public function destroyAdmin(Request $request, string $token)
    {
        $this->remove(DeviceToken::TYPE_ADMIN, auth()->id(), $token);

        return response()->json(['success' => true]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);
    }

    protected function upsert(string $userType, int $userId, array $data): void
    {
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_type' => $userType,
                'user_id' => $userId,
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'last_seen_at' => now(),
            ]
        );
    }

    protected function remove(string $userType, int $userId, string $token): void
    {
        DeviceToken::forUser($userType, $userId)->where('token', $token)->delete();
    }
}
