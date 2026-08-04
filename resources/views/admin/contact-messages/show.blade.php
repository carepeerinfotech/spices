@extends('admin.layouts.app')

@section('title', 'Contact Message')
@section('heading', 'Contact Message')
@section('subtitle', 'From '.$message->name)

@section('content')
<div class="max-w-2xl rounded-xl bg-white border border-slate-200 p-6 space-y-4">
    <div class="grid sm:grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-slate-500">Name</p>
            <p class="font-medium">{{ $message->name }}</p>
        </div>
        <div>
            <p class="text-slate-500">Email</p>
            <p class="font-medium"><a href="mailto:{{ $message->email }}" class="text-teal-700 hover:underline">{{ $message->email }}</a></p>
        </div>
        <div>
            <p class="text-slate-500">Phone</p>
            <p class="font-medium">{{ $message->phone ?: '—' }}</p>
        </div>
        <div>
            <p class="text-slate-500">Received</p>
            <p class="font-medium">{{ $message->created_at->format('M j, Y g:ia') }}</p>
        </div>
    </div>
    <div>
        <p class="text-slate-500 text-sm mb-1">Message</p>
        <p class="whitespace-pre-line leading-relaxed">{{ $message->message }}</p>
    </div>
</div>

<div class="max-w-2xl mt-4 flex items-center justify-between">
    <a href="{{ route('admin.contact-messages.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Back to messages</a>
    <button type="button" id="delete-message" class="text-sm text-rose-600 hover:underline">Delete</button>
</div>

@push('scripts')
<script>
document.getElementById('delete-message').addEventListener('click', function () {
    if (!window.confirm('Delete this message?')) return;
    fetch('{{ route('admin.contact-messages.destroy', $message) }}', {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    }).then(function (res) {
        if (res.ok) window.location = '{{ route('admin.contact-messages.index') }}';
    });
});
</script>
@endpush
@endsection
