<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedTinyInteger('isolation_day')->nullable()->after('due_day');
        });

        $customers = DB::table('customers')->whereNotNull('isolation_date')->get(['id', 'isolation_date']);

        foreach ($customers as $customer) {
            try {
                $day = Carbon::parse($customer->isolation_date)->day;
                DB::table('customers')->where('id', $customer->id)->update(['isolation_day' => $day]);
            } catch (Exception $e) {
                //
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('isolation_date');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('isolation_date')->nullable()->after('due_day');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('isolation_day');
        });
    }
};
