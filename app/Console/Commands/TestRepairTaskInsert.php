<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestRepairTaskInsert extends Command
{
    protected $signature = 'test:repair-task-insert';

    protected $description = 'Test repair task insert to debug Data values error';

    public function handle()
    {
        $this->info('=== Testing Repair Task Insert ===');

        // Test 1: Check customer data
        $this->newLine();
        $this->info('TEST 1: Customer Data');
        $customer = Customer::first();
        if (! $customer) {
            $this->error('No customers found in database');

            return;
        }

        $this->line("Customer ID: {$customer->id}");
        $this->line('Latitude: '.var_export($customer->latitude, true).' (type: '.gettype($customer->latitude).')');
        $this->line('Longitude: '.var_export($customer->longitude, true).' (type: '.gettype($customer->longitude).')');

        $attrs = $customer->getAttributes();
        $this->line('Raw latitude: '.var_export($attrs['latitude'] ?? null, true).' (type: '.gettype($attrs['latitude'] ?? null).')');
        $this->line('Raw longitude: '.var_export($attrs['longitude'] ?? null, true).' (type: '.gettype($attrs['longitude'] ?? null).')');

        // Test 2: Direct DB insert with NULL
        $this->newLine();
        $this->info('TEST 2: Insert with NULL');
        try {
            $id = DB::table('repair_tasks')->insertGetId([
                'customer_id' => 1,
                'assigned_by_user_id' => 1,
                'nama_customer' => 'Test NULL',
                'alamat' => 'Test Alamat',
                'latitude' => null,
                'longitude' => null,
                'no_telp' => '08123456789',
                'keterangan' => 'Test with NULL',
                'status' => 'baru',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✅ Success with NULL! ID: {$id}");
            DB::table('repair_tasks')->where('id', $id)->delete();
        } catch (\Exception $e) {
            $this->error('❌ Failed: '.$e->getMessage());
        }

        // Test 3: Direct DB insert with string
        $this->newLine();
        $this->info('TEST 3: Insert with string coordinates');
        try {
            $id = DB::table('repair_tasks')->insertGetId([
                'customer_id' => 1,
                'assigned_by_user_id' => 1,
                'nama_customer' => 'Test String',
                'alamat' => 'Test Alamat',
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
                'no_telp' => '08123456789',
                'keterangan' => 'Test with string',
                'status' => 'baru',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✅ Success with string! ID: {$id}");
            DB::table('repair_tasks')->where('id', $id)->delete();
        } catch (\Exception $e) {
            $this->error('❌ Failed: '.$e->getMessage());
        }

        // Test 4: Direct DB insert with numeric
        $this->newLine();
        $this->info('TEST 4: Insert with numeric coordinates');
        try {
            $id = DB::table('repair_tasks')->insertGetId([
                'customer_id' => 1,
                'assigned_by_user_id' => 1,
                'nama_customer' => 'Test Numeric',
                'alamat' => 'Test Alamat',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'no_telp' => '08123456789',
                'keterangan' => 'Test with numeric',
                'status' => 'baru',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✅ Success with numeric! ID: {$id}");
            DB::table('repair_tasks')->where('id', $id)->delete();
        } catch (\Exception $e) {
            $this->error('❌ Failed: '.$e->getMessage());
        }

        // Test 5: Using customer data (this is what fails in production)
        $this->newLine();
        $this->info('TEST 5: Using REAL customer data');
        try {
            $id = DB::table('repair_tasks')->insertGetId([
                'customer_id' => $customer->id,
                'assigned_by_user_id' => 1,
                'nama_customer' => $customer->name,
                'alamat' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'no_telp' => $customer->phone,
                'keterangan' => 'Test with customer data',
                'status' => 'baru',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✅ Success with customer data! ID: {$id}");
            DB::table('repair_tasks')->where('id', $id)->delete();
        } catch (\Exception $e) {
            $this->error('❌ Failed: '.$e->getMessage());
            $this->newLine();
            $this->warn('Full exception:');
            $this->line($e->__toString());
        }

        $this->newLine();
        $this->info('=== Tests Complete ===');
    }
}
