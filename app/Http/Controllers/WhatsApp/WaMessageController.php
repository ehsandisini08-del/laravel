<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\SendMessageRequest;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\WaDevice;
use App\Models\WaMessage;
use App\Services\WhatsApp\WhatsAppGatewayService;
use Illuminate\Http\Request;

class WaMessageController extends Controller
{
    public function __construct(
        protected WhatsAppGatewayService $whatsAppGatewayService,
    ) {}

    public function index(Request $request)
    {
        $query = WaMessage::with(['device', 'customer']);

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }

        $messages = $query->latest()->paginate(15)->withQueryString();
        $devices = WaDevice::all();

        return view('whatsapp.messages.index', compact('messages', 'devices'));
    }

    public function create()
    {
        $devices = WaDevice::where('status', 'connected')->get();

        return view('whatsapp.messages.create', compact('devices'));
    }

    public function store(SendMessageRequest $request)
    {
        $device = WaDevice::findOrFail($request->device_id);

        SendMessageJob::dispatch(
            $device->id,
            $request->phone,
            $request->message,
            null,
            [],
            $request->filled('customer_id') ? $request->customer_id : null,
        );

        return redirect()->route('whatsapp.messages.index')
            ->with('success', 'Pesan sedang dikirim.');
    }

    public function show(WaMessage $message)
    {
        $message->load(['device', 'customer']);

        return view('whatsapp.messages.show', compact('message'));
    }
}
