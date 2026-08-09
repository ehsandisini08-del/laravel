@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500']) }}>