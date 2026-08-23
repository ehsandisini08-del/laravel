<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairTaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_task_id',
        'user_id',
        'comment',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function repairTask(): BelongsTo
    {
        return $this->belongsTo(RepairTask::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUserComments($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeSystemComments($query)
    {
        return $query->where('is_system', true);
    }
}
