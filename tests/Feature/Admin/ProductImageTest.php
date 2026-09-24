<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Image;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Media\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A non-super-admin role, so permission checks are actually exercised
     * rather than short-circuited by User::hasPermission().
     */
    private function admin(string $permission = 'products.manage', string $roleSlug = 'editor'): User
    {
        $role = Role::create(['name' => ucfirst($roleSlug), 'slug' => $roleSlug, 'is_active' => true]);
        $role->permissions()->attach(
            Permission::create(['name' => 'Manage '.$permission, 'slug' => $permission, 'group' => 'Catalog'])
        );

        $user = User::factory()->create([
            'is_active' => true,
            'is_customer' => false,
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Gallery Spice',
            'sku' => 'GAL-001',
            'price' => 500,
            'stock' => 5,
            'is_active' => 1,
        ], $overrides);
    }

    private function createProductWith(User $admin, int $imageCount): Product
    {
        $files = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $files[] = UploadedFile::fake()->image("shot-{$i}.jpg");
        }

        $this->actingAs($admin)
            ->postJson(route('admin.products.store'), $this->payload(['gallery_files' => $files]))
            ->assertOk();

        return Product::where('name', 'Gallery Spice')->sole();
    }

    public function test_uploads_land_in_the_gallery_collection_with_the_first_as_primary(): void
    {
        Storage::fake('public');

        $product = $this->createProductWith($this->admin(), 3);
        $images = $product->imagesIn('gallery');

        $this->assertCount(3, $images);
        $this->assertSame(1, $images->where('is_primary', true)->count());
        $this->assertTrue($images->first()->is_primary);

        foreach ($images as $image) {
            $this->assertSame('product', $image->imageable_type);
            $this->assertStringStartsWith('products/'.$product->id.'/', $image->path);
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_a_later_upload_appends_to_the_gallery_instead_of_replacing_it(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 2);
        $original = $product->imagesIn('gallery')->pluck('id')->all();

        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload([
            'gallery_files' => [UploadedFile::fake()->image('extra.jpg')],
        ]))->assertOk();

        $product->refresh()->unsetRelation('images');
        $images = $product->imagesIn('gallery');

        $this->assertCount(3, $images);
        // The originals survive, and the primary flag does not move.
        $this->assertEmpty(array_diff($original, $images->pluck('id')->all()));
        $this->assertSame(1, $images->where('is_primary', true)->count());
    }

    public function test_saving_without_uploads_leaves_the_gallery_untouched(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 2);
        $before = $product->imagesIn('gallery')->pluck('id')->all();

        $this->actingAs($admin)
            ->putJson(route('admin.products.update', $product), $this->payload(['name' => 'Renamed Spice']))
            ->assertOk();

        $product->refresh()->unsetRelation('images');

        $this->assertSame($before, $product->imagesIn('gallery')->pluck('id')->all());
    }

    public function test_admin_can_promote_another_image_to_primary(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 3);
        $second = $product->imagesIn('gallery')->get(1);

        $this->actingAs($admin)
            ->postJson(route('admin.images.primary', $second))
            ->assertOk()
            ->assertJson(['success' => true]);

        $product->refresh()->unsetRelation('images');

        $this->assertSame($second->id, $product->image('gallery')->id);
        $this->assertSame($second->url(), $product->primaryImageUrl());
        $this->assertSame(1, $product->imagesIn('gallery')->where('is_primary', true)->count());
    }

    public function test_starring_an_image_does_not_move_it_in_the_admin_gallery(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 3);
        $ids = $product->imagesIn('gallery')->pluck('id')->all();

        $this->actingAs($admin)->postJson(route('admin.images.primary', $ids[1]))->assertOk();

        $product->refresh()->unsetRelation('images');
        $this->assertSame($ids, $product->imagesIn('gallery')->pluck('id')->all());
        $this->assertSame($ids[1], $product->image('gallery')->id);

        // The editor lists thumbnails in saved order, not starred-first, so a
        // drag after reloading persists the order the admin actually sees.
        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSeeInOrder(array_map(fn ($id) => 'data-image-id="'.$id.'"', $ids), false);
    }

    public function test_product_page_opens_on_the_starred_image_and_keeps_the_saved_order(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 4);
        $images = $product->imagesIn('gallery');

        $this->actingAs($admin)->postJson(route('admin.images.primary', $images[1]))->assertOk();

        $this->get(route('shop.product', $product->slug))
            ->assertOk()
            ->assertSee('id="main-image" src="'.$images[1]->url().'"', false)
            ->assertSee('var currentImageIndex = 1;', false)
            ->assertSeeInOrder($images->map(fn ($image) => 'src="'.$image->url().'" alt=""')->all(), false);
    }

    public function test_product_page_shows_the_variants_own_images_as_thumbnails(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 3);
        $gallery = $product->imagesIn('gallery');
        $variant = $product->variants()->sole();

        $service = app(ImageService::class);
        $variantImages = collect([
            $service->attach($variant, 'image', UploadedFile::fake()->image('v1.jpg')),
            $service->attach($variant, 'image', UploadedFile::fake()->image('v2.jpg')),
        ]);
        $this->actingAs($admin)->postJson(route('admin.images.primary', $variantImages[1]))->assertOk();

        $this->get(route('shop.product', $product->slug))
            ->assertOk()
            // The variant's starred image opens, highlighted in its own thumbnails…
            ->assertSee('id="main-image" src="'.$variantImages[1]->url().'"', false)
            ->assertSee('var currentImageIndex = 1;', false)
            ->assertSeeInOrder($variantImages->map(fn ($image) => 'src="'.$image->url().'" alt=""')->all(), false)
            // …in place of the product gallery.
            ->assertDontSee('src="'.$gallery[0]->url().'" alt=""', false);
    }

    public function test_each_variant_gallery_falls_back_to_the_product_gallery(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 2);
        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload(['variants' => [
            ['sku' => 'GAL-100G', 'option_label' => '100g', 'price' => 100, 'stock' => 5],
            ['sku' => 'GAL-250G', 'option_label' => '250g', 'price' => 220, 'stock' => 5],
        ]]))->assertOk();

        [$small, $large] = $product->variants()->orderBy('id')->get()->all();
        $largeImage = app(ImageService::class)->attach($large, 'image', UploadedFile::fake()->image('large.jpg'));

        $product = $product->fresh(['images', 'variants.images']);
        $gallery = $product->imagesIn('gallery')->map(fn ($image) => $image->url())->all();

        $this->assertSame(['images' => $gallery, 'active' => 0], $product->galleryFor($product->variants->find($small->id)));
        $this->assertSame(['images' => [$largeImage->url()], 'active' => 0], $product->galleryFor($product->variants->find($large->id)));
        $this->assertSame(['images' => $gallery, 'active' => 0], $product->galleryFor(null));
    }

    public function test_admin_can_reorder_the_gallery_without_disturbing_the_primary(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 3);
        $ids = $product->imagesIn('gallery')->pluck('id')->all();
        $primaryId = $product->image('gallery')->id;

        $reversed = array_reverse($ids);

        $this->actingAs($admin)
            ->postJson(route('admin.images.reorder'), ['ids' => $reversed])
            ->assertOk()
            ->assertJson(['success' => true]);

        $product->refresh()->unsetRelation('images');

        $this->assertSame(
            $reversed,
            $product->images->where('collection', 'gallery')->sortBy('sort_order')->pluck('id')->values()->all()
        );
        // Reordering is about display order, not which image is primary.
        $this->assertSame($primaryId, $product->image('gallery')->id);
    }

    public function test_reorder_rejects_ids_spanning_two_collections(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 2);

        $category = Category::create(['name' => 'Mixed', 'slug' => 'mixed', 'is_active' => true]);
        $foreign = app(ImageService::class)->attach($category, 'image', 'https://example.test/pic.jpg');

        $this->actingAs($admin)
            ->postJson(route('admin.images.reorder'), [
                'ids' => [$product->image('gallery')->id, $foreign->id],
            ])
            ->assertStatus(422);
    }

    public function test_reordering_requires_the_owners_permission(): void
    {
        Storage::fake('public');

        $product = $this->createProductWith($this->admin(), 2);
        $ids = $product->imagesIn('gallery')->pluck('id')->all();

        $this->actingAs($this->admin('categories.manage', 'admin'))
            ->postJson(route('admin.images.reorder'), ['ids' => array_reverse($ids)])
            ->assertForbidden();
    }

    public function test_deleting_a_product_removes_its_gallery_and_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 2);
        $paths = $product->imagesIn('gallery')->pluck('path');

        $this->actingAs($admin)
            ->deleteJson(route('admin.products.destroy', $product))
            ->assertOk();

        $this->assertSame(0, Image::where('imageable_type', 'product')->where('imageable_id', $product->id)->count());

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_media_tab_renders_sortable_gallery_controls(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 2);
        $image = $product->image('gallery');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="gallery_files[]"', false)
            ->assertSee('data-image-upload-sortable', false)
            ->assertSee('data-image-reorder-url="'.route('admin.images.reorder').'"', false)
            ->assertSee('data-image-primary="'.route('admin.images.primary', $image).'"', false)
            ->assertSee('href="'.route('admin.images.download', $image).'"', false)
            // The replaced markup is gone for good.
            ->assertDontSee('name="images[]"', false)
            ->assertDontSee('existing_images', false)
            ->assertDontSee('primary_image_id', false);
    }

    public function test_variant_images_are_stored_in_the_variants_own_folder(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $variant = $product->variants()->sole();

        $first = app(ImageService::class)->attach($variant, 'image', UploadedFile::fake()->image('v1.jpg'));

        $variant->refresh()->unsetRelation('images');
        $this->assertSame($first->url(), $variant->imageUrl());
        $this->assertStringStartsWith('products/variants/'.$variant->id.'/', $first->path);
        Storage::disk('public')->assertExists($first->path);

        // A second image is added alongside; the first stays primary.
        app(ImageService::class)->attach($variant, 'image', UploadedFile::fake()->image('v2.jpg'));

        $variant->refresh()->unsetRelation('images');
        $this->assertCount(2, $variant->imagesIn('image'));
        $this->assertSame($first->url(), $variant->imageUrl());
        Storage::disk('public')->assertExists($first->path);
    }

    public function test_a_variant_without_an_image_falls_back_to_the_product_primary(): void
    {
        Storage::fake('public');

        $product = $this->createProductWith($this->admin(), 1);
        $variant = $product->variants()->sole();

        $this->assertTrue($variant->imagesIn('image')->isEmpty());
        $this->assertSame($product->primaryImageUrl(), $variant->fresh()->imageUrl());
    }

    public function test_deleting_a_variant_image_is_permission_checked_and_removes_the_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $variant = $product->variants()->sole();
        $image = app(ImageService::class)->attach($variant, 'image', UploadedFile::fake()->image('v.jpg'));

        $this->actingAs($this->admin('categories.manage', 'admin'))
            ->deleteJson(route('admin.images.destroy', $image))
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson(route('admin.images.destroy', $image))
            ->assertOk();

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_media_tab_lists_a_field_per_saved_variant(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $variant = $product->variants()->sole();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Variant images')
            ->assertSee($variant->sku)
            // Keyed by variant so several variants' fields never collide.
            ->assertSee('name="variant_images['.$variant->id.'][]"', false)
            ->assertDontSee('name="image_file"', false);
    }

    public function test_saving_the_form_adds_several_images_to_a_variant(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $variant = $product->variants()->sole();

        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload([
            'variant_images' => [$variant->id => [
                UploadedFile::fake()->image('v1.jpg'),
                UploadedFile::fake()->image('v2.jpg'),
            ]],
        ]))->assertOk();

        $first = $variant->fresh()->image('image');
        $this->assertNotNull($first);
        $this->assertStringStartsWith('products/variants/'.$variant->id.'/', $first->path);
        $this->assertCount(2, $variant->fresh()->imagesIn('image'));

        // A later save appends instead of replacing.
        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload([
            'variant_images' => [$variant->id => [UploadedFile::fake()->image('v3.jpg')]],
        ]))->assertOk();

        $images = $variant->fresh()->imagesIn('image');
        $this->assertCount(3, $images);
        $this->assertSame($first->id, $variant->fresh()->image('image')->id);
        Storage::disk('public')->assertExists($first->path);

        // The product gallery is not touched by a variant upload.
        $this->assertCount(1, $product->fresh()->imagesIn('gallery'));
    }

    public function test_each_variant_gets_its_own_upload(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $variants = [
            ['sku' => 'GAL-100G', 'option_label' => '100g', 'price' => 100, 'stock' => 5],
            ['sku' => 'GAL-250G', 'option_label' => '250g', 'price' => 220, 'stock' => 5],
        ];

        $this->actingAs($admin)
            ->putJson(route('admin.products.update', $product), $this->payload(['variants' => $variants]))
            ->assertOk();

        [$small, $large] = $product->variants()->orderBy('id')->get()->all();

        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload([
            'variants' => [
                ['id' => $small->id] + $variants[0],
                ['id' => $large->id] + $variants[1],
            ],
            'variant_images' => [$large->id => [UploadedFile::fake()->image('large.jpg')]],
        ]))->assertOk();

        $this->assertTrue($small->fresh()->imagesIn('image')->isEmpty());
        $this->assertCount(1, $large->fresh()->imagesIn('image'));
    }

    public function test_a_variant_upload_cannot_target_another_products_variant(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $other = Product::create(['name' => 'Other', 'slug' => 'other', 'sku' => 'OTH-001', 'price' => 10, 'stock' => 1]);
        $foreign = $other->variants()->create(['sku' => 'OTH-001', 'name' => 'Other', 'price' => 10, 'stock' => 1, 'is_default' => true]);

        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload([
            'variant_images' => [$foreign->id => [UploadedFile::fake()->image('sneaky.jpg')]],
        ]))->assertOk();

        $this->assertTrue($foreign->fresh()->imagesIn('image')->isEmpty());
    }

    public function test_oversized_variant_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $product = $this->createProductWith($admin, 1);
        $variant = $product->variants()->sole();

        $this->actingAs($admin)->putJson(route('admin.products.update', $product), $this->payload([
            'variant_images' => [$variant->id => [UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg')]],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('variant_images.'.$variant->id.'.0');
    }

    public function test_create_page_tells_you_to_save_before_adding_variant_images(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Save the product first to add images to its variants.');
    }

    public function test_oversized_uploads_are_rejected_by_the_config_driven_rules(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->postJson(route('admin.products.store'), $this->payload([
                'gallery_files' => [UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg')],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('gallery_files.0');
    }
}
