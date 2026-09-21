<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\User;

class InstallationReportService
{
    /**
     * @param  array{rx_power?: string|null, device_name?: string|null, parts_used?: string|null}  $extra
     */
    public function createForCustomer(Customer $customer, User $technician, array $extra = []): InstallationReport
    {
        return InstallationReport::create([
            'customer_id' => $customer->id,
            'user_id' => $technician->id,
            'installation_date' => $customer->installation_date ?? now(),
            'port_odp' => $customer->port_odp,
            'rx_power' => $extra['rx_power'] ?? null,
            'device_name' => $extra['device_name'] ?? null,
            'parts_used' => $extra['parts_used'] ?? null,
        ]);
    }
}
