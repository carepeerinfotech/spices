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

        return $this->respond($request, 'Product created successfully.');
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

        return $this->respond($request, 'Product updated successfully.');
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

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.products.index'),
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', $message);
    }
}
