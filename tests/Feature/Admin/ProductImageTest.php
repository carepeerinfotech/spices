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
            'allow_cod' => 1,
            'allow_online' => 1,
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
