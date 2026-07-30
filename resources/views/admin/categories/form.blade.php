@extends('admin.layouts.app')

@section('title', $category->exists ? 'Edit Category' : 'Add Category')
@section('heading', $category->exists ? 'Edit category' : 'Add category')

@section('content')
<form data-ajax="formdata" method="POST" enctype="multipart/form-data"
      action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      class="max-w-2xl rounded-xl bg-white border border-slate-200 p-6 space-y-5">
    @csrf
    @if($category->exists) @method('PUT') @endif
    <div>
        <label class="block text-sm font-medium mb-1.5">Name</label>
        <input name="name" required value="{{ old('name', $category->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Slug</label>
        <input name="slug" value="{{ old('slug', $category->slug) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Description</label>
        <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $category->description) }}</textarea>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Category picture</label>
            <input type="file" name="image_file" accept="image/*" class="block w-full text-sm">
            @if($category->imageUrl())
                <img src="{{ $category->imageUrl() }}" alt="" class="mt-2 h-24 rounded object-cover">
            @endif
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Category page banner</label>
            <input type="file" name="banner_file" accept="image/*" class="block w-full text-sm">
            @if($category->bannerUrl())
                <img src="{{ $category->bannerUrl() }}" alt="" class="mt-2 h-24 w-full rounded object-cover">
            @endif
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Meta title</label>
        <input name="meta_title" value="{{ old('meta_title', $category->meta_title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Meta description</label>
        <textarea name="meta_description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('meta_description', $category->meta_description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1.5">Sort order</label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" data-bool @checked(old('is_active', $category->is_active ?? true))> Active
    </label>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save</button>
        <a href="{{ route('admin.categories.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</a>
    </div>
</form>
@endsection
