<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Image;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryImageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A non-super-admin role, so permission checks are actually exercised
     * rather than short-circuited by User::hasPermission().
     */
    private function admin(string $permission = 'categories.manage', string $roleSlug = 'editor'): User
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

    public function test_uploads_are_stored_as_images_rows_in_their_collections(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name' => 'Cardamom',
            'sort_order' => 1,
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg', 400, 400),
            'banner_file' => UploadedFile::fake()->image('wide.jpg', 1400, 500),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'cardamom')->sole();

        $this->assertCount(2, $category->images);
        $this->assertNotNull($category->imageUrl());
        $this->assertNotNull($category->bannerUrl());

        foreach ($category->images as $image) {
            $this->assertSame('category', $image->imageable_type);
            $this->assertTrue($image->is_primary);
            Storage::disk('public')->assertExists($image->path);
        }

        $this->assertSame(
            ['banner', 'image'],
            $category->images->pluck('collection')->sort()->values()->all()
        );
    }

    public function test_edit_form_renders_an_upload_field_and_a_remove_button_per_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Fennel',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'fennel')->sole();
        $image = $category->image('image');

        $this->actingAs($admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('name="image_file"', false)
            ->assertSee('name="banner_file"', false)
            ->assertSee('Category picture')
            ->assertSee('Category page banner')
            ->assertSee('data-image-upload-dropzone', false)
            // The picture has preview / download / remove controls; the empty
            // banner slot has none.
            ->assertSee('data-image-preview="'.$image->url().'"', false)
            ->assertSee('href="'.route('admin.images.download', $image).'"', false)
            ->assertSee('data-delete="'.route('admin.images.destroy', $image).'"', false)
            ->assertSee('No image uploaded yet.');
    }

    public function test_admin_can_download_a_stored_image_under_a_readable_name(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Star Anise',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $image = Category::where('slug', 'star-anise')->sole()->image('image');

        $this->actingAs($admin)
            ->get(route('admin.images.download', $image))
            ->assertOk()
            ->assertDownload('star-anise-image.jpg');
    }

    public function test_downloading_an_external_image_redirects_to_its_source(): void
    {
        $category = Category::create(['name' => 'Pepper', 'slug' => 'pepper', 'is_active' => true]);
        $url = 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=400';
        $image = app(\App\Services\Media\ImageService::class)->attach($category, 'image', $url);

        $this->actingAs($this->admin())
            ->get(route('admin.images.download', $image))
            ->assertRedirect($url);
    }

    public function test_downloading_an_image_requires_the_owners_permission(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name' => 'Cassia',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $image = Category::where('slug', 'cassia')->sole()->image('image');

        $this->actingAs($this->admin('products.manage', 'admin'))
            ->get(route('admin.images.download', $image))
            ->assertForbidden();
    }

    public function test_downloading_an_image_whose_file_vanished_returns_404(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Ajwain',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $image = Category::where('slug', 'ajwain')->sole()->image('image');
        Storage::disk('public')->delete($image->path);

        $this->actingAs($admin)
            ->get(route('admin.images.download', $image))
            ->assertNotFound();
    }

    public function test_uploading_again_replaces_the_single_image_and_removes_the_old_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $category = Category::create(['name' => 'Clove', 'slug' => 'clove', 'is_active' => true]);
        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Clove',
            'slug' => 'clove',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('first.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $original = $category->fresh()->image('image');

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Clove',
            'slug' => 'clove',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('second.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $category->refresh()->unsetRelation('images');
        $replacement = $category->image('image');

        $this->assertCount(1, $category->imagesIn('image'));
        $this->assertNotSame($original->id, $replacement->id);
        Storage::disk('public')->assertMissing($original->path);
        Storage::disk('public')->assertExists($replacement->path);
    }

    public function test_saving_without_an_upload_keeps_the_existing_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $category = Category::create(['name' => 'Mace', 'slug' => 'mace', 'is_active' => true]);
        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Mace',
            'slug' => 'mace',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('keep.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $existing = $category->fresh()->image('image');

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Mace Renamed',
            'slug' => 'mace',
            'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $category->refresh()->unsetRelation('images');
        $this->assertSame($existing->id, $category->image('image')?->id);
        Storage::disk('public')->assertExists($existing->path);
    }

    public function test_admin_can_delete_a_single_image_without_touching_the_other_collection(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Nutmeg',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
            'banner_file' => UploadedFile::fake()->image('wide.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'nutmeg')->sole();
        $picture = $category->image('image');
        $banner = $category->image('banner');

        $this->actingAs($admin)
            ->deleteJson(route('admin.images.destroy', $picture))
            ->assertOk()
            ->assertJson(['success' => true]);

        $category->refresh()->unsetRelation('images');

        $this->assertNull($category->imageUrl());
        $this->assertNotNull($category->bannerUrl());
        $this->assertDatabaseMissing('images', ['id' => $picture->id]);
        Storage::disk('public')->assertMissing($picture->path);
        Storage::disk('public')->assertExists($banner->path);
    }

    public function test_deleting_an_image_requires_the_owners_permission(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name' => 'Saffron',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $image = Category::where('slug', 'saffron')->sole()->image('image');

        // A different admin holding an unrelated permission must not qualify.
        $this->actingAs($this->admin('products.manage', 'admin'))
            ->deleteJson(route('admin.images.destroy', $image))
            ->assertForbidden();

        $this->assertDatabaseHas('images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_deleting_a_category_removes_its_images_and_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Bay Leaf',
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('pic.jpg'),
            'banner_file' => UploadedFile::fake()->image('wide.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'bay-leaf')->sole();
        $paths = $category->images->pluck('path');

        $this->actingAs($admin)
            ->deleteJson(route('admin.categories.destroy', $category))
            ->assertOk();

        $this->assertSame(0, Image::where('imageable_id', $category->id)->where('imageable_type', 'category')->count());

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_external_urls_are_referenced_and_never_deleted_from_disk(): void
    {
        Storage::fake('public');

        $category = Category::create(['name' => 'Anise', 'slug' => 'anise', 'is_active' => true]);
        $url = 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=400';

        $image = app(\App\Services\Media\ImageService::class)->attach($category, 'image', $url);

        $this->assertTrue($image->isRemote());
        $this->assertSame($url, $category->fresh()->imageUrl());

        // Deleting must not attempt a filesystem call for a remote reference.
        $image->delete();
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
    }
}
