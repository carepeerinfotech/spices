@extends('admin.layouts.app')

@section('title', 'Users')
@section('heading', 'Users')
@section('subtitle', 'Manage admin and staff accounts')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search users..."
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        <button class="rounded-lg bg-slate-800 text-white px-3 py-2 text-sm">Search</button>
    </form>
    <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add user</a>
</div>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Name</th>
            <th class="px-5 py-3 font-medium">Email</th>
            <th class="px-5 py-3 font-medium">Roles</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($users as $user)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $user->name }}</td>
                <td class="px-5 py-3">{{ $user->email }}</td>
                <td class="px-5 py-3">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td class="px-5 py-3">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    <button type="button" data-delete="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete this user?" class="text-rose-600 hover:underline">Delete</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->withQueryString()->links() }}</div>
@endsection
