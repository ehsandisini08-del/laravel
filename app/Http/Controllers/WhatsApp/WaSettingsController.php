<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WaSetting;
use Illuminate\Http\Request;

class WaSettingsController extends Controller
{
    public function index()
    {
        $settings = WaSetting::allSettings();

        return view('whatsapp.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gateway_url' => ['required', 'url'],
            'api_token' => ['required', 'string'],
            'request_timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'auto_reconnect' => ['nullable', 'boolean'],
            'max_retry' => ['required', 'integer', 'min:1', 'max:10'],
            'session_storage_path' => ['required', 'string'],
            'webhook_url' => ['nullable', 'url'],
            'webhook_secret' => ['nullable', 'string'],
        ]);

        foreach ($validated as $key => $value) {
            WaSetting::set($key, $value);
        }

        return redirect()->route('whatsapp.settings.index')
            ->with('success', 'Pengaturan WhatsApp Gateway berhasil disimpan.');
    }
}
