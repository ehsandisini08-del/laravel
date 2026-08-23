<?php

namespace App\Enums;

enum RepairTaskStatus: string
{
    case Baru = 'baru';
    case Proses = 'proses';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Proses => 'Proses',
            self::Selesai => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baru => 'info',
            self::Proses => 'warning',
            self::Selesai => 'success',
        };
    }

    public function badge(): string
    {
        $color = $this->color();
        $label = $this->label();

        $colorClasses = match ($color) {
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
        };

        return "<span class=\"inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {$colorClasses}\">{$label}</span>";
    }
}
