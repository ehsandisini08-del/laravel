<div>
    <label for="kode_odc" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kode ODC <span class="text-red-500">*</span></label>
    <input type="text" name="kode_odc" id="kode_odc" value="{{ old('kode_odc', $odc->kode_odc ?? null) }}" required maxlength="50" placeholder="ODC-001" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
    @error('kode_odc')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="nama_odc" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama ODC <span class="text-red-500">*</span></label>
    <input type="text" name="nama_odc" id="nama_odc" value="{{ old('nama_odc', $odc->nama_odc ?? null) }}" required maxlength="255" placeholder="ODC Kebon Jeruk" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('nama_odc')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi ODC</label>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik pada peta untuk menandai titik lokasi ODC.</p>
    <div id="odc-map" class="relative z-0 mt-3 h-96 w-full overflow-hidden rounded-xl border border-gray-300 dark:border-gray-600"></div>
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $odc->latitude ?? null) }}" readonly placeholder="Klik pada peta" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
            @error('latitude')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $odc->longitude ?? null) }}" readonly placeholder="Klik pada peta" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
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
            containerId: 'odc-map',
            latInputId: 'latitude',
            lngInputId: 'longitude',
        });
    });
</script>
@endpush