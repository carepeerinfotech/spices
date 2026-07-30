<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageSlideController extends Controller
{
    public function index()
    {
        $slides = HomepageSlide::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.homepage-slides.index', compact('slides'));
    }

    public function create()
    {
        return view('admin.homepage-slides.form', [
            'slide' => new HomepageSlide(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        HomepageSlide::create($this->validated($request));

        return $this->respond($request, 'Slide created successfully.');
    }

    public function edit(HomepageSlide $homepage_slide)
    {
        return view('admin.homepage-slides.form', ['slide' => $homepage_slide]);
    }

    public function update(Request $request, HomepageSlide $homepage_slide)
    {
        $homepage_slide->update($this->validated($request, $homepage_slide));

        return $this->respond($request, 'Slide updated successfully.');
    }

    public function destroy(HomepageSlide $homepage_slide)
    {
        $this->deleteStored($homepage_slide->image);
        $this->deleteStored($homepage_slide->mobile_image);
        $homepage_slide->delete();

        return response()->json(['success' => true, 'message' => 'Slide deleted successfully.']);
    }

    private function validated(Request $request, ?HomepageSlide $slide = null): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'mobile_image_file' => ['nullable', 'image', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'mobile_image_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'title' => $data['title'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('image_file')) {
            $this->deleteStored($slide?->image);
            $payload['image'] = $request->file('image_file')->store('homepage/slides', 'public');
        } elseif (! empty($data['image_url'])) {
            if ($slide?->image && $slide->image !== $data['image_url']) {
                $this->deleteStored($slide->image);
            }
            $payload['image'] = $data['image_url'];
        } elseif (! $slide?->image) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image_file' => ['A desktop image upload or URL is required.'],
            ]);
        }

        if ($request->hasFile('mobile_image_file')) {
            $this->deleteStored($slide?->mobile_image);
            $payload['mobile_image'] = $request->file('mobile_image_file')->store('homepage/slides', 'public');
        } elseif ($request->filled('mobile_image_url') || $request->has('mobile_image_url')) {
            $url = $data['mobile_image_url'] ?? null;
            if ($slide?->mobile_image && $slide->mobile_image !== $url) {
                $this->deleteStored($slide->mobile_image);
            }
            $payload['mobile_image'] = $url ?: null;
        }

        return $payload;
    }

    private function deleteStored(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://') && ! str_starts_with($path, '/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function respond(Request $request, string $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('admin.homepage-slides.index'),
        ]);
    }
}
