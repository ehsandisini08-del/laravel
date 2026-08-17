<?php

use App\Models\Customer;
use App\Models\PppSecret;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

function shiftMigration(): Migration
{
    return require database_path('migrations/2026_08_17_201801_shift_utc_timestamps_to_wib.php');
}

it('shifts legacy UTC timestamps by seven hours', function () {
    $customer = Customer::factory()->create();
    DB::table('customers')->where('id', $customer->id)->update([
        'created_at' => '2026-08-10 05:30:00',
        'updated_at' => '2026-08-10 05:30:00',
    ]);

    $secret = PppSecret::factory()->create();
    DB::table('ppp_secrets')->where('id', $secret->id)->update([
        'last_logged_out' => '2026-08-10 04:12:00',
        'created_at' => '2026-08-10 05:30:00',
        'updated_at' => '2026-08-10 05:30:00',
    ]);

    DB::table('job_logs')->insert([
        'type' => 'job',
        'class' => 'TestJob',
        'status' => 'queued',
        'started_at' => '2026-08-10 05:30:00',
        'finished_at' => '2026-08-10 05:31:00',
        'created_at' => '2026-08-10 05:31:00',
        'updated_at' => '2026-08-10 05:31:00',
    ]);

    shiftMigration()->up();

    expect(DB::table('customers')->value('created_at'))
        ->toBe('2026-08-10 12:30:00');
    expect(DB::table('job_logs')->value('started_at'))
        ->toBe('2026-08-10 12:30:00');
    expect(DB::table('job_logs')->value('finished_at'))
        ->toBe('2026-08-10 12:31:00');
    expect(DB::table('ppp_secrets')->value('last_logged_out'))
        ->toBe('2026-08-10 11:12:00');
    expect($customer->fresh()->created_at->toDateTimeString())
        ->toBe('2026-08-10 12:30:00');
});

it('leaves values written after the timezone fix untouched', function () {
    $customer = Customer::factory()->create();
    DB::table('customers')->where('id', $customer->id)->update([
        'created_at' => '2026-08-18 19:00:00',
        'updated_at' => '2026-08-18 19:00:00',
    ]);

    shiftMigration()->up();

    expect(DB::table('customers')->value('created_at'))
        ->toBe('2026-08-18 19:00:00');
});

it('is safe to run on an empty database', function () {
    shiftMigration()->up();

    expect(DB::table('customers')->count())->toBe(0);
});
