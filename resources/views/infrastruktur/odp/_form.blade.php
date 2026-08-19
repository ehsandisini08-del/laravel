<div>
    <label for="odc_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ODC <span class="text-red-500">*</span></label>
    <select name="odc_id" id="odc_id" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="" disabled {{ old('odc_id', $odp->odc_id ?? null) ? '' : 'selected' }}>Pilih ODC...</option>
        @foreach($odcs as $odc)
            <option value="{{ $odc->id }}" {{ old('odc_id', $odp->odc_id ?? null) == $odc->id ? 'selected' : '' }}>
                {{ $odc->kode_odc }} - {{ $odc->nama_odc }}
            </option>
        @endforeach
    </select>
    @error('odc_id')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="kode_odp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kode ODP <span class="text-red-500">*</span></label>
    <input type="text" name="kode_odp" id="kode_odp" value="{{ old('kode_odp', $odp->kode_odp ?? null) }}" required maxlength="50" placeholder="ODP-001" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
    @error('kode_odp')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="nama_odp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama ODP <span class="text-red-500">*</span></label>
    <input type="text" name="nama_odp" id="nama_odp" value="{{ old('nama_odp', $odp->nama_odp ?? null) }}" required maxlength="255" placeholder="ODP Kebon Jeruk" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('nama_odp')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi ODP</label>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik pada peta untuk menandai titik lokasi ODP.</p>
    <div id="odp-map" class="relative z-0 mt-3 h-96 w-full overflow-hidden rounded-xl border border-gray-300 dark:border-gray-600"></div>
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $odp->latitude ?? null) }}" readonly placeholder="Klik pada peta" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
            @error('latitude')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $odp->longitude ?? null) }}" readonly placeholder="Klik pada peta" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
            @error('longitude')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initMapPicker({
            containerId: 'odp-map',
            latInputId: 'latitude',
            lngInputId: 'longitude',
        });
    });
</script>
@endpush