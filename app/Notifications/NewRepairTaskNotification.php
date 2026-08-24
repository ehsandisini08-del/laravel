<?php

namespace App\Notifications;

use App\Models\RepairTask;

class NewRepairTaskNotification extends BaseMobileNotification
{
    public function __construct(
        public RepairTask $task
    ) {}

    protected function title(): string
    {
        return 'Tugas Perbaikan Baru';
    }

    protected function body(): string
    {
        return 'Ada tugas perbaikan untuk '.$this->task->nama_customer.' di '.$this->task->alamat;
    }

    protected function data(): array
    {
        return [
            'type' => 'repair_task',
            'task_id' => (string) $this->task->id,
            'customer_name' => (string) ($this->task->nama_customer ?? ''),
            'address' => (string) ($this->task->alamat ?? ''),
        ];
    }
}
