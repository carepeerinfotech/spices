<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Services\Media\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One endpoint every admin module reuses to remove an image, so no controller
 * has to grow its own delete route. Access is decided by the permission the
 * owning model declares in config/media.php.
 */
class ImageController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function destroy(Request $request, Image $image): JsonResponse
    {
        $this->authorizeImage($request, $image);

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image removed.',
        ]);
    }

    /**
     * Externally hosted images are not ours to stream, so we hand the browser
     * the original URL instead of proxying an arbitrary remote fetch.
     */
    public function download(Request $request, Image $image): StreamedResponse|RedirectResponse
    {
        $this->authorizeImage($request, $image);

        if ($image->isRemote()) {
            return redirect()->away($image->path);
        }

        $disk = Storage::disk($image->disk ?: config('media.disk', 'public'));

        abort_unless($disk->exists($image->path), 404, 'This image file is missing.');

        return $disk->download($image->path, $image->downloadName());
    }

    public function primary(Request $request, Image $image): JsonResponse
    {
        $this->authorizeImage($request, $image);

        $this->images->setPrimary($image);

        return response()->json([
            'success' => true,
            'message' => 'Primary image updated.',
        ]);
    }

    /**
     * Reorder one collection. Every id must belong to the same owner and
     * collection — a mixed list is a malformed request, not a partial reorder.
     */
    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $images = Image::whereIn('id', $ids)->get();

        abort_if($images->count() !== count($ids), 404, 'One or more images no longer exist.');

        $scopes = $images->map(
            fn (Image $image) => $image->imageable_type.':'.$image->imageable_id.':'.$image->collection
        )->unique();

        abort_if($scopes->count() > 1, 422, 'Images must belong to a single collection.');

        $first = $images->first();
        $this->authorizeImage($request, $first);

        $owner = $first->imageable;
        abort_unless($owner, 404, 'The owning record no longer exists.');

        $this->images->reorder($owner, $first->collection, $ids);

        return response()->json([
            'success' => true,
            'message' => 'Image order updated.',
        ]);
    }

    /**
     * An image whose owner type is not registered is not deletable through
     * here — better to refuse than to guess at who may touch it.
     */
    private function authorizeImage(Request $request, Image $image): void
    {
        try {
            $permission = $this->images->permissionFor($image->imageable_type);
        } catch (InvalidArgumentException) {
            abort(403, 'This image cannot be managed here.');
        }

        if ($permission && ! $request->user()?->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
