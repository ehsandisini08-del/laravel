<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Active = 'active';
    case Overdue = 'overdue';
    case Isolated = 'isolated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Overdue => 'Overdue',
            self::Isolated => 'Isolir',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Overdue => 'warning',
            self::Isolated => 'danger',
        };
    }
}
