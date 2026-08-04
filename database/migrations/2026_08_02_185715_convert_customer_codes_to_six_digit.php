<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            Customer::where('customer_code', 'like', 'CUST-%')->get()->each(function (Customer $customer) {
                $number = (int) substr($customer->customer_code, 5);
                $customer->update(['customer_code' => str_pad((string) $number, 6, '0', STR_PAD_LEFT)]);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            Customer::all()->each(function (Customer $customer) {
                if (preg_match('/^\d{6}$/', $customer->customer_code)) {
                    $customer->update(['customer_code' => 'CUST-'.str_pad((string) ((int) $customer->customer_code), 5, '0', STR_PAD_LEFT)]);
                }
            });
        });
    }
};
