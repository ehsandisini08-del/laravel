<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Dashboard
                </a>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">WhatsApp Menu</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih menu WhatsApp Gateway</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        <div class="app-card p-5">
            <div class="mb-4 flex items-center gap-2">
                <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h2 class="app-label">WhatsApp</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <x-admin.menu-tile
                    label="Dashboard"
                    href="{{ route('whatsapp.dashboard') }}"
                    icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                    color="bg-green-600"
                    :active="request()->routeIs('whatsapp.dashboard')"
                />
                <x-admin.menu-tile
                    label="Device"
                    href="{{ route('whatsapp.devices.index') }}"
                    icon="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                    color="bg-blue-600"
                    :active="request()->routeIs('whatsapp.devices.*')"
                />
                <x-admin.menu-tile
                    label="Template"
                    href="{{ route('whatsapp.templates.index') }}"
                    icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    color="bg-purple-600"
                    :active="request()->routeIs('whatsapp.templates.*')"
                />
                <x-admin.menu-tile
                    label="Pesan"
                    href="{{ route('whatsapp.messages.index') }}"
                    icon="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                    color="bg-teal-600"
                    :active="request()->routeIs('whatsapp.messages.*')"
                />
                <x-admin.menu-tile
                    label="Broadcast"
                    href="{{ route('whatsapp.broadcast.create') }}"
                    icon="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4M7 16a3 3 0 100 6 3 3 0 000-6zm10-6a3 3 0 106 0 3 3 0 00-6 0zM7 4a3 3 0 100 6 3 3 0 000-6z"
                    color="bg-amber-600"
                    :active="request()->routeIs('whatsapp.broadcast.*')"
                />
                <x-admin.menu-tile
                    label="Pengaturan"
                    href="{{ route('whatsapp.settings.index') }}"
                    icon="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    color="bg-slate-600"
                    :active="request()->routeIs('whatsapp.settings.*')"
                />
            </div>
        </div>
    </div>
</x-admin-layout>