@extends('admin.layouts.app')

@section('title', 'Contact Messages')
@section('heading', 'Contact Messages')
@section('subtitle', 'Enquiries submitted from the storefront contact form')

@section('content')
<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium"></th>
            <th class="px-5 py-3 font-medium">Name</th>
            <th class="px-5 py-3 font-medium">Email</th>
            <th class="px-5 py-3 font-medium">Message</th>
            <th class="px-5 py-3 font-medium">Received</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($messages as $message)
            <tr class="{{ $message->read_at ? '' : 'bg-teal-50/40' }}">
                <td class="px-5 py-3">
                    @unless($message->read_at)
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-600" title="Unread"></span>
                    @endunless
                </td>
                <td class="px-5 py-3 font-medium">
                    <a href="{{ route('admin.contact-messages.show', $message) }}" class="hover:underline">{{ $message->name }}</a>
                </td>
                <td class="px-5 py-3 text-slate-600">{{ $message->email }}</td>
                <td class="px-5 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($message->message, 60) }}</td>
                <td class="px-5 py-3 text-slate-500">{{ $message->created_at->format('M j, Y g:ia') }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.contact-messages.show', $message) }}" class="text-teal-700 hover:underline mr-3">View</a>
                    <button type="button" data-delete="{{ route('admin.contact-messages.destroy', $message) }}" data-confirm="Delete this message?" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-5 py-8 text-center text-slate-400">No messages yet.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
