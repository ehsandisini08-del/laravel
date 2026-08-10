<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppGatewayService;

class WaDashboardController extends Controller
{
    public function index()
    {
        $stats = app(WhatsAppGatewayService::class)->getDashboardStats();

        $gatewayHealthy = app(WhatsAppGatewayService::class)->checkGatewayHealth();

        return view('whatsapp.dashboard', compact('stats', 'gatewayHealthy'));
    }

    public function menu()
    {
        return view('whatsapp.menu');
    }
}
