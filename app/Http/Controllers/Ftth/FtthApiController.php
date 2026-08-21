<?php

namespace App\Http\Controllers\Ftth;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FiberLine;
use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FtthApiController extends Controller
{
    /**
     * Stats summary untuk dashboard map.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_odc' => Odc::count(),
            'total_odp' => Odp::count(),
            'total_customers' => Customer::count(),
            'customers_online' => Customer::where('status', CustomerStatus::Active->value)
                ->where('service_status', ServiceStatus::Active->value)
                ->count(),
            'customers_gangguan' => Customer::where('service_status', ServiceStatus::Overdue->value)->count(),
            'customers_isolir' => Customer::where('service_status', ServiceStatus::Isolated->value)->count(),
            'customers_nonaktif' => Customer::whereIn('status', [
                CustomerStatus::Suspended->value,
                CustomerStatus::Terminated->value,
            ])->count(),
        ]);
    }

    /**
     * Daftar semua ODC dengan koordinat.
     */
    public function odcs(): JsonResponse
    {
        $odcs = Odc::select(['id', 'kode', 'nama', 'alamat', 'latitude', 'longitude', 'kapasitas', 'status'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn ($odc) => [
                'id' => $odc->id,
                'kode' => $odc->kode,
                'nama' => $odc->nama,
                'alamat' => $odc->alamat,
                'lat' => (float) $odc->latitude,
                'lng' => (float) $odc->longitude,
                'kapasitas' => $odc->kapasitas,
                'status' => $odc->status,
                'odp_count' => Odp::where('odc_id', $odc->id)->count(),
                'url' => route('ftth.odc.show', $odc->id),
            ]);

        return response()->json($odcs);
    }

    /**
     * Daftar semua ODP dengan koordinat.
     */
    public function odps(Request $request): JsonResponse
    {
        $query = Odp::with('odc:id,kode,nama')
            ->select(['id', 'odc_id', 'kode', 'nama', 'alamat', 'latitude', 'longitude', 'kapasitas', 'port_terpakai', 'status'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('odc_id')) {
            $query->where('odc_id', $request->odc_id);
        }

        $odps = $query->get()->map(fn ($odp) => [
            'id' => $odp->id,
            'kode' => $odp->kode,
            'nama' => $odp->nama,
            'alamat' => $odp->alamat,
            'lat' => (float) $odp->latitude,
            'lng' => (float) $odp->longitude,
            'kapasitas' => $odp->kapasitas,
            'port_terpakai' => $odp->port_terpakai,
            'port_available' => $odp->port_available,
            'status' => $odp->status,
            'odc' => $odp->odc ? ['id' => $odp->odc->id, 'kode' => $odp->odc->kode, 'nama' => $odp->odc->nama] : null,
            'url' => route('ftth.odp.show', $odp->id),
        ]);

        return response()->json($odps);
    }

    /**
     * Daftar pelanggan dengan koordinat (support bounding box).
     */
    public function customers(Request $request): JsonResponse
    {
        $query = Customer::with(['odp:id,kode,nama', 'package:id,name'])
            ->select(['id', 'customer_code', 'name', 'address', 'latitude', 'longitude', 'status', 'service_status', 'odp_id', 'port_odp', 'package_id'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Bounding box filter untuk performa
        if ($request->filled(['south', 'west', 'north', 'east'])) {
            $query->whereBetween('latitude', [(float) $request->south, (float) $request->north])
                ->whereBetween('longitude', [(float) $request->west, (float) $request->east]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service_status')) {
            $query->where('service_status', $request->service_status);
        }

        if ($request->filled('odp_id')) {
            $query->where('odp_id', $request->odp_id);
        }

        $customers = $query->limit(2000)->get()->map(fn ($c) => [
            'id' => $c->id,
            'customer_code' => $c->customer_code,
            'name' => $c->name,
            'address' => $c->address,
            'lat' => (float) $c->latitude,
            'lng' => (float) $c->longitude,
            'status' => $c->status,
            'service_status' => $c->service_status?->value ?? null,
            'odp' => $c->odp ? ['id' => $c->odp->id, 'kode' => $c->odp->kode] : null,
            'port_odp' => $c->port_odp,
            'package' => $c->package ? $c->package->name : null,
            'url' => route('customers.show', $c->id),
        ]);

        return response()->json($customers);
    }

    /**
     * Jalur fiber dengan koordinat.
     */
    public function fibers(): JsonResponse
    {
        $fibers = FiberLine::where('status', FiberLine::STATUS_ACTIVE)
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'nama' => $f->nama,
                'tipe_kabel' => $f->tipe_kabel,
                'source_type' => $f->source_type,
                'source_id' => $f->source_id,
                'destination_type' => $f->destination_type,
                'destination_id' => $f->destination_id,
                'coordinates' => $f->leaflet_coords,
                'status' => $f->status,
            ]);

        return response()->json($fibers);
    }

    /**
     * Search ODC, ODP, pelanggan.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = [];

        $odcs = Odc::where('kode', 'like', "%{$q}%")
            ->orWhere('nama', 'like', "%{$q}%")
            ->whereNotNull('latitude')
            ->limit(5)
            ->get(['id', 'kode', 'nama', 'latitude', 'longitude']);

        foreach ($odcs as $odc) {
            $results[] = [
                'type' => 'odc',
                'id' => $odc->id,
                'label' => "[ODC] {$odc->kode} — {$odc->nama}",
                'lat' => (float) $odc->latitude,
                'lng' => (float) $odc->longitude,
            ];
        }

        $odps = Odp::where('kode', 'like', "%{$q}%")
            ->orWhere('nama', 'like', "%{$q}%")
            ->whereNotNull('latitude')
            ->limit(5)
            ->get(['id', 'kode', 'nama', 'latitude', 'longitude']);

        foreach ($odps as $odp) {
            $results[] = [
                'type' => 'odp',
                'id' => $odp->id,
                'label' => "[ODP] {$odp->kode} — {$odp->nama}",
                'lat' => (float) $odp->latitude,
                'lng' => (float) $odp->longitude,
            ];
        }

        $customers = Customer::where('customer_code', 'like', "%{$q}%")
            ->orWhere('name', 'like', "%{$q}%")
            ->whereNotNull('latitude')
            ->limit(5)
            ->get(['id', 'customer_code', 'name', 'latitude', 'longitude']);

        foreach ($customers as $c) {
            $results[] = [
                'type' => 'customer',
                'id' => $c->id,
                'label' => "[Customer] {$c->customer_code} — {$c->name}",
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
            ];
        }

        return response()->json($results);
    }
}
