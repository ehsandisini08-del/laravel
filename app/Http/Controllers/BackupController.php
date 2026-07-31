<?php

namespace App\Http\Controllers;

class BackupController extends Controller
{
    public function index()
    {
        return view('backup.index');
    }
}
