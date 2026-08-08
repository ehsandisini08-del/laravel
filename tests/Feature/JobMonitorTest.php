<?php

use App\Models\JobLog;
use App\Models\User;
use App\Services\JobLogService;

test('job log service tracks queued -> processing -> success', function () {
    $service = app(JobLogService::class);

    $service->jobQueued('job-1', 'App\Jobs\FooJob', 'default');
    $service->jobProcessing('job-1', 'App\Jobs\FooJob', 1);
    $service->jobProcessed('job-1', 'App\Jobs\FooJob', 1);

    $row = JobLog::where('job_id', 'job-1')->first();

    expect(JobLog::where('job_id', 'job-1')->count())->toBe(1)
        ->and($row->status)->toBe(JobLog::STATUS_SUCCESS)
        ->and($row->finished_at)->not->toBeNull()
        ->and($row->duration_ms)->toBeInt()
        ->and($row->tries)->toBe(1);
});

test('job log service records failures with exception message', function () {
    $service = app(JobLogService::class);

    $service->jobProcessing('job-2', 'App\Jobs\BoomJob', 1);
    $service->jobFailed('job-2', 'App\Jobs\BoomJob', 1, 'Something exploded');

    $row = JobLog::where('job_id', 'job-2')->first();

    expect($row->status)->toBe(JobLog::STATUS_FAILED)
        ->and($row->exception_message)->toBe('Something exploded');
});

test('job log service records schedule runs', function () {
    $service = app(JobLogService::class);

    $service->scheduleRunning('php artisan mikrotik:sync');
    $service->scheduleFinished('php artisan mikrotik:sync', JobLog::STATUS_SUCCESS);

    $row = JobLog::schedules()->latest()->first();

    expect($row->class)->toBe('php artisan mikrotik:sync')
        ->and($row->status)->toBe(JobLog::STATUS_SUCCESS)
        ->and($row->duration_ms)->toBeInt();
});

test('job log prune removes old rows only', function () {
    JobLog::create(['class' => 'Old']);
    JobLog::where('class', 'Old')->update(['created_at' => now()->subDays(10)]);
    JobLog::create(['class' => 'New']);

    app(JobLogService::class)->prune(7);

    expect(JobLog::where('class', 'Old')->exists())->toBeFalse()
        ->and(JobLog::where('class', 'New')->exists())->toBeTrue();
});

test('developer can view the job monitor page and status endpoint', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->get(route('monitoring.jobs'))
        ->assertStatus(200)
        ->assertSee('Job Monitor');

    $this->get(route('monitoring.jobs.status'))
        ->assertOk()
        ->assertJsonStructure(['recent_jobs', 'recent_schedules', 'stats']);
});

test('non developer admin cannot access the job monitor', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $this->get(route('monitoring.jobs'))->assertForbidden();
});
