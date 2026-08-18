<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Media\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

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
        $category = Category::create($this->validated($request));
        $this->images->syncFromRequest($category, $request);

        return $this->respond($request, 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $category->load('images');

        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));
        $this->images->syncFromRequest($category, $request);

        return $this->respond($request, 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        // Images and their files are cleaned up by the HasImages trait.
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
        ] + $this->images->rules(Category::class));

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        // Uploads are persisted by the ImageService, never as model attributes.
        return Arr::except($data, array_keys($this->images->rules(Category::class)));
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
