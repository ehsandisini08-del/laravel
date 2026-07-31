<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'Active';
    case Isolated = 'Isolated';
    case Suspended = 'Suspended';
    case Terminated = 'Terminated';
}
