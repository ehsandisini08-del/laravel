@props(['variant' => 'default', 'size' => 'sm'])

@php
$variants = [
    'default' => 'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-300',
    'primary' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    'info' => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300',
    'success' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'warning' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300',
    'danger' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
];

$sizes = [
    'sm' => 'px-2.5 py-0.5 text-xs',
    'md' => 'px-3 py-1 text-sm',
    'lg' => 'px-4 py-1.5 text-base',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center font-medium rounded-full ' . $variants[$variant] . ' ' . $sizes[$size]]) }}>
    {{ $slot }}
</span>
