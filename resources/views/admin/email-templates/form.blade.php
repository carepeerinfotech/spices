@extends('admin.layouts.app')
@section('title', 'Edit template')
@section('heading', 'Edit email template')
@section('content')
<form data-ajax method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="max-w-3xl rounded-xl bg-white border p-6 space-y-4">
    @csrf @method('PUT')
    <div><label class="text-sm">Name</label><input name="name" value="{{ $template->name }}" class="mt-1 w-full rounded-lg border px-3 py-2"></div>
    <div><label class="text-sm">Subject</label><input name="subject" value="{{ $template->subject }}" class="mt-1 w-full rounded-lg border px-3 py-2"></div>
    <div><label class="text-sm">Body (HTML, placeholders like @{{customer_name}})</label>
        <textarea name="body" rows="12" class="mt-1 w-full rounded-lg border px-3 py-2 font-mono text-sm">{{ $template->body }}</textarea>
    </div>
    <p class="text-xs text-slate-500">Placeholders: {{ implode(', ', $template->placeholders ?? []) }}</p>
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" data-bool @checked($template->is_active)> Active</label>
    <button class="rounded-lg bg-teal-700 text-white px-4 py-2 text-sm">Save</button>
</form>

<form data-ajax method="POST" action="{{ route('admin.email-templates.test', $template) }}" class="max-w-3xl mt-4 rounded-xl bg-white border p-6 flex gap-3 items-end">
    @csrf
    <div class="flex-1"><label class="text-sm">Test send to</label><input type="email" name="email" required class="mt-1 w-full rounded-lg border px-3 py-2"></div>
    <button class="rounded-lg border px-4 py-2 text-sm">Send test</button>
</form>
@endsection
