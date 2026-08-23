<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\RepairTask;
use App\Models\RepairTaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairTaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', User::ROLE_SUPERADMIN)->first()
            ?? User::where('role', User::ROLE_DEVELOPER)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $teknisi = User::where('role', User::ROLE_TEKNISI)->first()
            ?? User::factory()->create(['role' => User::ROLE_TEKNISI, 'name' => 'Teknisi Demo']);

        $customers = Customer::inRandomOrder()->limit(10)->get();

        if ($customers->isEmpty()) {
            $customers = Customer::factory()->count(10)->create();
        }

        foreach ($customers->take(5) as $customer) {
            $task = RepairTask::factory()->create([
                'customer_id' => $customer->id,
                'assigned_by_user_id' => $admin->id,
                'nama_customer' => $customer->name,
                'alamat' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'no_telp' => $customer->phone,
            ]);

            RepairTaskComment::factory()->system()->create([
                'repair_task_id' => $task->id,
                'user_id' => $admin->id,
                'comment' => 'Tugas dibuat oleh '.$admin->name,
            ]);
        }

        foreach ($customers->slice(5, 3) as $customer) {
            $task = RepairTask::factory()->proses()->create([
                'customer_id' => $customer->id,
                'assigned_by_user_id' => $admin->id,
                'taken_by_user_id' => $teknisi->id,
                'nama_customer' => $customer->name,
                'alamat' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'no_telp' => $customer->phone,
            ]);

            RepairTaskComment::factory()->system()->create([
                'repair_task_id' => $task->id,
                'user_id' => $admin->id,
                'comment' => 'Tugas dibuat oleh '.$admin->name,
            ]);

            RepairTaskComment::factory()->system()->create([
                'repair_task_id' => $task->id,
                'user_id' => $teknisi->id,
                'comment' => 'Tugas diambil oleh '.$teknisi->name,
            ]);

            RepairTaskComment::factory()->create([
                'repair_task_id' => $task->id,
                'user_id' => $teknisi->id,
                'comment' => 'Sedang dalam perjalanan ke lokasi',
            ]);
        }

        foreach ($customers->slice(8, 2) as $customer) {
            $task = RepairTask::factory()->selesai()->create([
                'customer_id' => $customer->id,
                'assigned_by_user_id' => $admin->id,
                'taken_by_user_id' => $teknisi->id,
                'nama_customer' => $customer->name,
                'alamat' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'no_telp' => $customer->phone,
            ]);

            RepairTaskComment::factory()->system()->create([
                'repair_task_id' => $task->id,
                'user_id' => $admin->id,
                'comment' => 'Tugas dibuat oleh '.$admin->name,
            ]);

            RepairTaskComment::factory()->system()->create([
                'repair_task_id' => $task->id,
                'user_id' => $teknisi->id,
                'comment' => 'Tugas diambil oleh '.$teknisi->name,
            ]);

            RepairTaskComment::factory()->system()->create([
                'repair_task_id' => $task->id,
                'user_id' => $teknisi->id,
                'comment' => 'Tugas diselesaikan oleh '.$teknisi->name,
            ]);
        }
    }
}
