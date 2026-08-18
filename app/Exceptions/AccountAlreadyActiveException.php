<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class AccountAlreadyActiveException extends Exception implements ShouldntReport {}
