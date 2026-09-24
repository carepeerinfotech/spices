<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductCatalogService;
use App\Services\Media\ImageService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductCatalogService $catalog,
        private ImageService $images,
    ) {}

    public function index(Request $request)
    {
        $products = Product::with(['category', 'variants'])
            ->when($request->q, fn ($q) => $q->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('sku', 'like', '%'.$request->q.'%');
            }))
            ->latest()
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product([
                'is_active' => true,
                'stock' => 0,
                'price' => 0,
            ]),
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->catalog->save(
            new Product,
            $this->productData($request),
            $request->input('options', []),
            $request->input('variants', []),
            $request->input('offers', []),
            $request->user()?->id
        );

        $this->images->syncFromRequest($product, $request);
        $this->syncVariantImages($product, $request);

        return $this->respond($request, $product, 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $product->load([
            'options.values', 'variants.images', 'images', 'category',
            'offers.user', 'offers.sourceOffer.product',
        ]);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $product = $this->catalog->save(
            $product,
            $this->productData($request),
            $request->input('options', []),
            $request->input('variants', []),
            $request->input('offers', []),
            $request->user()?->id
        );

        $this->images->syncFromRequest($product, $request);
        $this->syncVariantImages($product, $request);

        return $this->respond($request, $product, 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
    }

    private function productData(StoreProductRequest $request): array
    {
        return [
            'category_id' => $request->input('category_id'),
            'name' => $request->input('name'),
            'brand' => $request->input('brand'),
            'slug' => $request->input('slug'),
            'sku' => $request->input('sku'),
            'hsn_code' => $request->input('hsn_code'),
            'short_description' => $request->input('short_description'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'compare_price' => $request->input('compare_price'),
            'stock' => $request->input('stock', 0),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
            'weight' => $request->input('weight'),
            'length' => $request->input('length'),
            'breadth' => $request->input('breadth'),
            'height' => $request->input('height'),
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
        ];
    }

    /**
     * Variant uploads arrive as variant_images[{variant id}][] and are appended
     * to that variant's images. Applied after the save, and only to variants of
     * this product that survived it — a variant dropped in the same save, or one
     * from another product, is skipped.
     */
    private function syncVariantImages(Product $product, StoreProductRequest $request): void
    {
        $files = array_filter((array) $request->file('variant_images', []));

        if ($files === []) {
            return;
        }

        foreach ($product->variants()->whereKey(array_keys($files))->get() as $variant) {
            foreach (array_filter((array) $files[$variant->id]) as $file) {
                $this->images->attach($variant, 'image', $file);
            }
        }
    }

    /**
     * Saving returns to the product's edit form, reopening the tab the admin
     * was on (posted as `tab`) rather than dropping them back on the listing.
     */
    private function respond(Request $request, Product $product, string $message)
    {
        $redirect = route('admin.products.edit', array_filter([
            'product' => $product,
            'tab' => $request->input('tab'),
        ]));

        if ($request->expectsJson()) {
            // The toast only lives until the page reloads; the flash shows on the reloaded form.
            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
            ]);
        }

        return redirect()->to($redirect)->with('success', $message);
    }
}
