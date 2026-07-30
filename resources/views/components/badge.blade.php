@props(['tone' => 'slate'])
@php
$classes = match ($tone) {
    'success' => 'bg-emerald-50 text-emerald-700',
    'danger' => 'bg-rose-50 text-rose-700',
    'warn' => 'bg-amber-50 text-amber-700',
    'brand' => 'bg-teal-50 text-teal-800',
    default => 'bg-slate-100 text-slate-600',
};
@endphp
<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-0.5 text-xs {$classes}"]) }}>{{ $slot }}</span>
