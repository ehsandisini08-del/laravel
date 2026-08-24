<?php

namespace App\Services\Mobile;

use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class PushNotificationService
{
    public function toCustomer(Customer $customer, Notification $notification): void
    {
        try {
            $customer->notify($notification);
        } catch (\Throwable $e) {
            $this->logFailure('customer', $customer->id, $e);
        }
    }

    public function toCustomerById(int $customerId, Notification $notification): void
    {
        try {
            $customer = Customer::find($customerId);

            if ($customer) {
                $customer->notify($notification);
            }
        } catch (\Throwable $e) {
            $this->logFailure('customer', $customerId, $e);
        }
    }

    public function toAdmins(Notification $notification): void
    {
        try {
            $adminIds = DeviceToken::where('user_type', DeviceToken::TYPE_ADMIN)
                ->distinct()
                ->pluck('user_id');

            $admins = User::whereIn('id', $adminIds)->get();

            NotificationFacade::send($admins, $notification);
        } catch (\Throwable $e) {
            $this->logFailure('admin', 0, $e);
        }
    }

    public function toTeknisi(Notification $notification): void
    {
        try {
            $teknisi = User::where('role', User::ROLE_TEKNISI)->get();

            if ($teknisi->isNotEmpty()) {
                NotificationFacade::send($teknisi, $notification);
            }
        } catch (\Throwable $e) {
            $this->logFailure('teknisi', 0, $e);
        }
    }

    protected function logFailure(string $userType, int $userId, \Throwable $e): void
    {
        Log::warning('Failed to send push notification', [
            'user_type' => $userType,
            'user_id' => $userId,
            'error' => $e->getMessage(),
        ]);
    }
}
