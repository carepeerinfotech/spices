@extends('admin.layouts.app')
@section('title', 'Email templates')
@section('heading', 'Email templates')
@section('content')
<div class="rounded-xl bg-white border overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
        <tr>
            <th class="px-5 py-3">Name</th>
            <th class="px-5 py-3">Slug</th>
            <th class="px-5 py-3">Subject</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @foreach($templates as $template)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $template->name }}</td>
                <td class="px-5 py-3 font-mono text-xs">{{ $template->slug }}</td>
                <td class="px-5 py-3">{{ $template->subject }}</td>
                <td class="px-5 py-3">{{ $template->is_active ? 'Active' : 'Off' }}</td>
                <td class="px-5 py-3 text-right"><a href="{{ route('admin.email-templates.edit', $template) }}" class="text-teal-700">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
