@extends('admin.layouts.app')

@section('title', $role->exists ? 'Edit Role' : 'Add Role')
@section('heading', $role->exists ? 'Edit role' : 'Add role')

@section('content')
<form data-ajax method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
      class="max-w-3xl rounded-xl bg-white border border-slate-200 p-6 space-y-5">
    @csrf
    @if($role->exists)
        @method('PUT')
    @endif

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Name</label>
            <input name="name" required value="{{ old('name', $role->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Slug</label>
            <input name="slug" value="{{ old('slug', $role->slug) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="auto-generated">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Description</label>
        <input name="description" value="{{ old('description', $role->description) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
    </div>

    <div>
        <label class="block text-sm font-medium mb-3">Permissions</label>
        <div class="space-y-4">
            @foreach($permissions as $group => $items)
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">{{ $group ?: 'General' }}</p>
                    <div class="grid sm:grid-cols-2 gap-2">
                        @foreach($items as $permission)
                            <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-slate-200 px-3 py-2">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                       @checked(collect(old('permissions', $role->permissions->pluck('id')))->contains($permission->id))
                                       class="rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" data-bool @checked(old('is_active', $role->is_active ?? true)) class="rounded border-slate-300 text-teal-700 focus:ring-teal-500">
        Active
    </label>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save</button>
        <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
@endsection
