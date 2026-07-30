<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CmsPageController extends Controller
{
    public function index(Request $request)
    {
        $pages = CmsPage::with('author')
            ->when($request->q, fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(15);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new CmsPage]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = ($data['status'] ?? 'draft') === 'published' ? now() : null;

        CmsPage::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Page created successfully.',
                'redirect' => route('admin.pages.index'),
            ]);
        }

        return redirect()->route('admin.pages.index')->with('success', 'Page created successfully.');
    }

    public function edit(CmsPage $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, CmsPage $page)
    {
        $data = $this->validated($request, $page);

        if (($data['status'] ?? $page->status) === 'published' && ! $page->published_at) {
            $data['published_at'] = now();
        }

        $page->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully.',
                'redirect' => route('admin.pages.index'),
            ]);
        }

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully.');
    }

    public function destroy(CmsPage $page)
    {
        $page->delete();

        return response()->json(['success' => true, 'message' => 'Page deleted successfully.']);
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($page?->id)],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        return $data;
    }
}
