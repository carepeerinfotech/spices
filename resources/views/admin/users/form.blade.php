@extends('admin.layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Add User')
@section('heading', $user->exists ? 'Edit user' : 'Add user')

@section('content')
<form data-ajax method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
      class="max-w-2xl rounded-xl bg-white border border-slate-200 p-6 space-y-5">
    @csrf
    @if($user->exists)
        @method('PUT')
    @endif

    <div>
        <label class="block text-sm font-medium mb-1.5">Name</label>
        <input name="name" required value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Email</label>
        <input name="email" type="email" required value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Password {{ $user->exists ? '(leave blank to keep)' : '' }}</label>
        <input name="password" type="password" {{ $user->exists ? '' : 'required' }} minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">Roles</label>
        <div class="flex flex-wrap gap-3">
            @foreach($roles as $role)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                           @checked(collect(old('roles', $user->roles->pluck('id')))->contains($role->id))
                           class="rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                    {{ $role->name }}
                </label>
            @endforeach
        </div>
    </div>
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" data-bool @checked(old('is_active', $user->is_active ?? true)) class="rounded border-slate-300 text-teal-700 focus:ring-teal-500">
        Active
    </label>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save</button>
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
@endsection
