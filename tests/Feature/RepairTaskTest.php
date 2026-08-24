<?php

use App\Enums\RepairTaskStatus;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\RepairTask;
use App\Models\User;
use App\Notifications\NewRepairTaskNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
    $this->teknisi = User::factory()->create(['role' => User::ROLE_TEKNISI]);
    $this->customer = Customer::factory()->create();
});

test('admin can view repair tasks index', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('teknisi.repair-tasks.index'));

    $response->assertOk();
});

test('teknisi can view repair tasks index', function () {
    $this->actingAs($this->teknisi);

    $response = $this->get(route('teknisi.repair-tasks.index'));

    $response->assertOk();
});

test('admin can view create repair task form', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('teknisi.repair-tasks.create'));

    $response->assertOk();
});

test('teknisi cannot view create repair task form', function () {
    $this->actingAs($this->teknisi);

    $response = $this->get(route('teknisi.repair-tasks.create'));

    $response->assertForbidden();
});

test('admin can create repair task', function () {
    $this->actingAs($this->admin);

    Notification::fake();

    $response = $this->post(route('teknisi.repair-tasks.store'), [
        'customer_id' => $this->customer->id,
        'keterangan' => 'Internet mati sejak kemarin',
    ]);

    $response->assertRedirect(route('teknisi.repair-tasks.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('repair_tasks', [
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'nama_customer' => $this->customer->name,
        'status' => RepairTaskStatus::Baru->value,
    ]);

    $task = RepairTask::latest()->first();

    $this->assertDatabaseHas('repair_task_comments', [
        'repair_task_id' => $task->id,
        'is_system' => true,
    ]);

    Notification::assertSentTo(
        User::where('role', User::ROLE_TEKNISI)->get(),
        NewRepairTaskNotification::class
    );
});

test('teknisi cannot create repair task', function () {
    $this->actingAs($this->teknisi);

    $response = $this->post(route('teknisi.repair-tasks.store'), [
        'customer_id' => $this->customer->id,
        'keterangan' => 'Internet mati sejak kemarin',
    ]);

    $response->assertForbidden();
});

test('teknisi can view repair task detail', function () {
    $this->actingAs($this->teknisi);

    $task = RepairTask::factory()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
    ]);

    $response = $this->get(route('teknisi.repair-tasks.show', $task));

    $response->assertOk();
});

test('teknisi can take available task', function () {
    $this->actingAs($this->teknisi);

    $task = RepairTask::factory()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'status' => RepairTaskStatus::Baru,
    ]);

    $response = $this->post(route('teknisi.repair-tasks.take', $task));

    $response->assertRedirect(route('teknisi.repair-tasks.show', $task));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('repair_tasks', [
        'id' => $task->id,
        'taken_by_user_id' => $this->teknisi->id,
        'status' => RepairTaskStatus::Proses->value,
    ]);

    $this->assertDatabaseHas('repair_task_comments', [
        'repair_task_id' => $task->id,
        'user_id' => $this->teknisi->id,
        'is_system' => true,
    ]);
});

test('teknisi cannot take task that already taken', function () {
    $this->actingAs($this->teknisi);

    $otherTeknisi = User::factory()->create(['role' => User::ROLE_TEKNISI]);

    $task = RepairTask::factory()->proses()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'taken_by_user_id' => $otherTeknisi->id,
    ]);

    $response = $this->post(route('teknisi.repair-tasks.take', $task));

    $response->assertSessionHas('error');
});

test('teknisi can complete their own task', function () {
    Storage::fake('public');

    $this->actingAs($this->teknisi);

    $task = RepairTask::factory()->proses()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'taken_by_user_id' => $this->teknisi->id,
    ]);

    $file = UploadedFile::fake()->image('bukti.jpg');

    $response = $this->post(route('teknisi.repair-tasks.complete', $task), [
        'keterangan_teknisi' => 'Masalah sudah diperbaiki',
        'foto_bukti' => $file,
    ]);

    $response->assertRedirect(route('teknisi.repair-tasks.index'));
    $response->assertSessionHas('success');

    $task->refresh();

    expect($task->status)->toBe(RepairTaskStatus::Selesai);
    expect($task->keterangan_teknisi)->toBe('Masalah sudah diperbaiki');
    expect($task->foto_bukti)->not->toBeNull();
    expect($task->completed_at)->not->toBeNull();

    Storage::disk('public')->assertExists($task->foto_bukti);

    $this->assertDatabaseHas('repair_task_comments', [
        'repair_task_id' => $task->id,
        'user_id' => $this->teknisi->id,
        'is_system' => true,
    ]);
});

test('teknisi cannot complete other teknisi task', function () {
    $this->actingAs($this->teknisi);

    $otherTeknisi = User::factory()->create(['role' => User::ROLE_TEKNISI]);

    $task = RepairTask::factory()->proses()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'taken_by_user_id' => $otherTeknisi->id,
    ]);

    $response = $this->post(route('teknisi.repair-tasks.complete', $task), [
        'keterangan_teknisi' => 'Masalah sudah diperbaiki',
    ]);

    $response->assertForbidden();
});

test('teknisi can complete task without photo', function () {
    $this->actingAs($this->teknisi);

    $task = RepairTask::factory()->proses()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'taken_by_user_id' => $this->teknisi->id,
    ]);

    $response = $this->post(route('teknisi.repair-tasks.complete', $task), [
        'keterangan_teknisi' => 'Masalah sudah diperbaiki',
    ]);

    $response->assertRedirect(route('teknisi.repair-tasks.index'));

    $task->refresh();

    expect($task->status)->toBe(RepairTaskStatus::Selesai);
    expect($task->foto_bukti)->toBeNull();
});

test('teknisi can add comment to task', function () {
    $this->actingAs($this->teknisi);

    $task = RepairTask::factory()->proses()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'taken_by_user_id' => $this->teknisi->id,
    ]);

    $response = $this->post(route('teknisi.repair-tasks.comment', $task), [
        'comment' => 'Sedang dalam perjalanan',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('repair_task_comments', [
        'repair_task_id' => $task->id,
        'user_id' => $this->teknisi->id,
        'comment' => 'Sedang dalam perjalanan',
        'is_system' => false,
    ]);
});

test('admin can delete repair task', function () {
    Storage::fake('public');

    $this->actingAs($this->admin);

    $file = UploadedFile::fake()->image('bukti.jpg');
    $path = $file->store('repair-tasks/2026/08', 'public');

    $task = RepairTask::factory()->selesai()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'foto_bukti' => $path,
    ]);

    Storage::disk('public')->assertExists($path);

    $response = $this->delete(route('teknisi.repair-tasks.destroy', $task));

    $response->assertRedirect(route('teknisi.repair-tasks.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('repair_tasks', [
        'id' => $task->id,
    ]);

    Storage::disk('public')->assertMissing($path);
});

test('teknisi cannot delete repair task', function () {
    $this->actingAs($this->teknisi);

    $task = RepairTask::factory()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
    ]);

    $response = $this->delete(route('teknisi.repair-tasks.destroy', $task));

    $response->assertForbidden();
});

test('task status transitions correctly', function () {
    $task = RepairTask::factory()->create(['status' => RepairTaskStatus::Baru]);
    expect($task->isBaru())->toBeTrue();
    expect($task->isProses())->toBeFalse();

    $task->update(['status' => RepairTaskStatus::Proses]);
    expect($task->isProses())->toBeTrue();
    expect($task->isBaru())->toBeFalse();

    $task->update(['status' => RepairTaskStatus::Selesai]);
    expect($task->isSelesai())->toBeTrue();
    expect($task->isProses())->toBeFalse();
});

test('validation rules work correctly', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('teknisi.repair-tasks.store'), []);

    $response->assertSessionHasErrors(['customer_id', 'keterangan']);
});

test('new repair task notification formats fcm message with string-only data payload', function () {
    $task = RepairTask::factory()->create([
        'customer_id' => $this->customer->id,
        'assigned_by_user_id' => $this->admin->id,
        'nama_customer' => 'Customer Test',
        'alamat' => 'Jl. Test No. 123',
    ]);

    $notification = new NewRepairTaskNotification($task);
    $fcmMessage = $notification->toFcm($this->teknisi);

    $data = $fcmMessage->data;
    expect($data)->toBeArray();
    expect($data['type'])->toBe('repair_task');
    expect($data['task_id'])->toBe((string) $task->id);
    expect($data['customer_name'])->toBe('Customer Test');
    expect($data['address'])->toBe('Jl. Test No. 123');

    // Every key and value must strictly be a string for FCM requirements
    foreach ($data as $key => $value) {
        expect(is_string($key))->toBeTrue();
        expect(is_string($value))->toBeTrue();
    }

    // Android channel must match admin app channel ID
    $messageArray = $fcmMessage->toArray();
    expect($messageArray['android']['notification']['channel_id'])->toBe('billnet_admin');
});

test('admin can create multiple repair tasks sequentially without data values error', function () {
    $this->actingAs($this->admin);

    // Test creating first task
    $response1 = $this->post(route('teknisi.repair-tasks.store'), [
        'customer_id' => $this->customer->id,
        'keterangan' => 'Tugas pertama',
    ]);
    $response1->assertRedirect(route('teknisi.repair-tasks.index'));
    $response1->assertSessionHas('success');

    // Test creating second task
    $response2 = $this->post(route('teknisi.repair-tasks.store'), [
        'customer_id' => $this->customer->id,
        'keterangan' => 'Tugas kedua',
    ]);
    $response2->assertRedirect(route('teknisi.repair-tasks.index'));
    $response2->assertSessionHas('success');

    expect(RepairTask::count())->toBe(2);
});

test('teknisi device tokens are retrieved by routeNotificationForFcm', function () {
    DeviceToken::create([
        'user_type' => DeviceToken::TYPE_ADMIN,
        'user_id' => $this->teknisi->id,
        'token' => 'fcm-teknisi-token-123',
    ]);

    $tokens = $this->teknisi->routeNotificationForFcm(new Illuminate\Notifications\Notification);
    expect($tokens)->toContain('fcm-teknisi-token-123');
});
