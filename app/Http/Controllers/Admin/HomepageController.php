<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function edit()
    {
        $sections = HomepageSection::orderBy('sort_order')->get()->keyBy('key');

        return view('admin.homepage.edit', [
            'sections' => $sections,
            'categories' => Category::active()->orderBy('name')->get(),
            'products' => Product::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sections' => ['required', 'array'],
        ]);

        foreach ($data['sections'] as $key => $section) {
            HomepageSection::updateOrCreate(
                ['key' => $key],
                [
                    'type' => $section['type'] ?? $key,
                    'title' => $section['title'] ?? null,
                    'content' => $section['content'] ?? [],
                    'sort_order' => (int) ($section['sort_order'] ?? 0),
                    'is_enabled' => ! empty($section['is_enabled']),
                    'is_published' => ! empty($section['is_published']),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Homepage updated.',
            'redirect' => route('admin.homepage.edit'),
        ]);
    }
}
