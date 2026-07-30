<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->orderBy('name')->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return $this->respond($request, 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        $category->update($data);

        return $this->respond($request, 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        foreach (['image', 'banner'] as $field) {
            if ($category->{$field} && ! str_starts_with($category->{$field}, 'http')) {
                Storage::disk('public')->delete($category->{$field});
            }
        }
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'banner_file' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['image_file'], $data['banner_file']);

        if ($request->hasFile('image_file')) {
            if ($category?->image && ! str_starts_with($category->image, 'http')) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image_file')->store('categories', 'public');
        }

        if ($request->hasFile('banner_file')) {
            if ($category?->banner && ! str_starts_with($category->banner, 'http')) {
                Storage::disk('public')->delete($category->banner);
            }
            $data['banner'] = $request->file('banner_file')->store('categories/banners', 'public');
        }

        return $data;
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.categories.index'),
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', $message);
    }
}
