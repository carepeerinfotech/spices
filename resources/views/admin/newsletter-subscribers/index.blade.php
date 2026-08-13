@extends('admin.layouts.app')

@section('title', 'Newsletter Subscribers')
@section('heading', 'Newsletter Subscribers')
@section('subtitle', 'Addresses collected from the storefront subscribe form')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <p class="text-sm text-slate-500">
        {{ $total }} {{ \Illuminate\Support\Str::plural('subscriber', $total) }}
        @if($search !== '')
            &middot; {{ $subscribers->total() }} matching &ldquo;{{ $search }}&rdquo;
        @endif
    </p>

    <div class="flex items-center gap-2">
        <form method="GET" action="{{ route('admin.newsletter-subscribers.index') }}" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search email"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Search</button>
        </form>
        @if($total > 0)
            <a href="{{ route('admin.newsletter-subscribers.export') }}"
               class="rounded-lg bg-teal-700 text-white px-3 py-2 text-sm hover:bg-teal-800">Export CSV</a>
        @endif
    </div>
</div>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Email</th>
            <th class="px-5 py-3 font-medium">Subscribed</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($subscribers as $subscriber)
            <tr>
                <td class="px-5 py-3 font-medium">
                    <a href="mailto:{{ $subscriber->email }}" class="hover:underline">{{ $subscriber->email }}</a>
                </td>
                <td class="px-5 py-3 text-slate-500">{{ $subscriber->created_at->format('M j, Y g:ia') }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <button type="button"
                            data-delete="{{ route('admin.newsletter-subscribers.destroy', $subscriber) }}"
                            data-confirm="Remove {{ $subscriber->email }} from the newsletter?"
                            class="text-rose-600 hover:underline">Remove</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-5 py-8 text-center text-slate-400">
                    {{ $search !== '' ? 'No subscribers match that search.' : 'No subscribers yet.' }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
