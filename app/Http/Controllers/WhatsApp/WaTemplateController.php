<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\StoreWaTemplateRequest;
use App\Models\WaTemplate;
use App\Services\ActivityLoggerService;
use App\Services\WhatsApp\WhatsAppGatewayService;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaTemplateController extends Controller
{
    public function __construct(
        protected WhatsAppTemplateService $templateService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $templates = $this->templateService->getAll($request->only(['search', 'category', 'is_active']));

        return view('whatsapp.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('whatsapp.templates.create');
    }

    public function store(StoreWaTemplateRequest $request)
    {
        try {
            $template = $this->templateService->create($request->validated());

            $this->activityLogger->created('WhatsApp Template', "Template {$template->name} created", $template);

            return redirect()->route('whatsapp.templates.index')
                ->with('success', 'Template berhasil dibuat.');
        } catch (Exception $e) {
            Log::error('Failed to create template', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Gagal membuat template: '.$e->getMessage());
        }
    }

    public function edit(WaTemplate $template)
    {
        return view('whatsapp.templates.edit', compact('template'));
    }

    public function update(StoreWaTemplateRequest $request, WaTemplate $template)
    {
        try {
            $this->templateService->update($template, $request->validated());

            $this->activityLogger->updated('WhatsApp Template', "Template {$template->name} updated", $template);

            return redirect()->route('whatsapp.templates.index')
                ->with('success', 'Template berhasil diperbarui.');
        } catch (Exception $e) {
            Log::error('Failed to update template', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Gagal memperbarui template: '.$e->getMessage());
        }
    }

    public function destroy(WaTemplate $template)
    {
        try {
            $name = $template->name;
            $this->templateService->delete($template);

            $this->activityLogger->deleted('WhatsApp Template', "Template {$name} deleted");

            return redirect()->route('whatsapp.templates.index')
                ->with('success', 'Template berhasil dihapus.');
        } catch (Exception $e) {
            Log::error('Failed to delete template', ['error' => $e->getMessage()]);

            return back()->with('error', 'Gagal menghapus template: '.$e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $template = WaTemplate::findOrFail($request->template_id);

        $variables = [
            'customer_name' => $request->customer_name ?? 'John Doe',
            'phone' => $request->phone ?? '08123456789',
            'package' => $request->package ?? 'Paket 10 Mbps',
            'price' => $request->price ?? 'Rp 150.000',
            'due_date' => $request->due_date ?? '15 Juli 2026',
            'invoice_number' => $request->invoice_number ?? 'INV-2026-0001',
            'company' => $request->company ?? 'ISP Company',
        ];

        $parsed = app(WhatsAppGatewayService::class)->parseTemplate($template->content, $variables);

        return response()->json([
            'success' => true,
            'content' => nl2br(e($parsed)),
        ]);
    }
}
