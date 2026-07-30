@extends('admin.layouts.app')

@section('title', 'Roles')
@section('heading', 'Roles & permissions')
@section('subtitle', 'Control what staff can access')

@section('content')
<div class="flex justify-end mb-5">
    <a href="{{ route('admin.roles.create') }}" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Add role</a>
</div>

<div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
            <th class="px-5 py-3 font-medium">Role</th>
            <th class="px-5 py-3 font-medium">Slug</th>
            <th class="px-5 py-3 font-medium">Users</th>
            <th class="px-5 py-3 font-medium">Permissions</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($roles as $role)
            <tr>
                <td class="px-5 py-3">
                    <div class="font-medium">{{ $role->name }}</div>
                    <div class="text-slate-500 text-xs">{{ $role->description }}</div>
                </td>
                <td class="px-5 py-3 font-mono text-xs">{{ $role->slug }}</td>
                <td class="px-5 py-3">{{ $role->users_count }}</td>
                <td class="px-5 py-3">{{ $role->permissions_count }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-teal-700 hover:underline mr-3">Edit</a>
                    @if(!in_array($role->slug, ['super-admin', 'admin', 'editor'], true))
                        <button type="button" data-delete="{{ route('admin.roles.destroy', $role) }}" class="text-rose-600 hover:underline">Delete</button>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $roles->links() }}</div>
@endsection
