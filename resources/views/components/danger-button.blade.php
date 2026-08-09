<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 border border-transparent rounded-xl font-semibold text-sm text-white shadow-[0_4px_12px_rgba(239,68,68,0.3)] hover:bg-red-500 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-150']) }}>
    {{ $slot }}
</button>