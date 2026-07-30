@extends('admin.layouts.app')

@section('title', $page->exists ? 'Edit Page' : 'Add Page')
@section('heading', $page->exists ? 'Edit page' : 'Add page')

@section('content')
<form data-ajax method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
      class="max-w-3xl rounded-xl bg-white border border-slate-200 p-6 space-y-5">
    @csrf
    @if($page->exists)
        @method('PUT')
    @endif

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Title</label>
            <input name="title" required value="{{ old('title', $page->title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Slug</label>
            <input name="slug" value="{{ old('slug', $page->slug) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="auto-from-title">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Content (HTML allowed)</label>
        <textarea name="content" rows="10" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">{{ old('content', $page->content) }}</textarea>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Meta title</label>
            <input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Status</label>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <option value="draft" @selected(old('status', $page->status ?? 'draft') === 'draft')>Draft</option>
                <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Meta description</label>
        <textarea name="meta_description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">{{ old('meta_description', $page->meta_description) }}</textarea>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save</button>
        <a href="{{ route('admin.pages.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
@endsection
