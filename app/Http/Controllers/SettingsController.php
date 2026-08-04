<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsUpdateRequest;
use App\Services\SettingService;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function index()
    {
        $sections = $this->settingService->sections();
        $settings = $this->settingService->all();
        $paymentWebhooks = $this->settingService->paymentWebhooks();

        return view('settings.index', compact('sections', 'settings', 'paymentWebhooks'));
    }

    public function update(SettingsUpdateRequest $request)
    {
        $this->settingService->update($request->validated());

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }
}
