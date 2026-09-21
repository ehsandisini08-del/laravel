<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\User;

class InstallationReportService
{
    public function createForCustomer(Customer $customer, User $technician): InstallationReport
    {
        return InstallationReport::create([
            'customer_id' => $customer->id,
            'user_id' => $technician->id,
            'installation_date' => now(),
        ]);
    }
}
