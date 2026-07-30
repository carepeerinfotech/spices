<?php

namespace Tests\Feature\Admin;

use App\Models\HomepageSlide;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageSliderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $permission = Permission::create(['name' => 'Manage Pages', 'slug' => 'pages.manage', 'group' => 'CMS']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create([
            'is_active' => true,
            'is_customer' => false,
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_admin_can_create_homepage_slide(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.homepage-slides.store'), [
            'title' => 'Launch banner',
            'link_url' => '/shop',
            'sort_order' => 1,
            'is_active' => 1,
            'image_file' => UploadedFile::fake()->image('desk.jpg', 1440, 768),
            'mobile_image_file' => UploadedFile::fake()->image('mob.jpg', 600, 780),
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('homepage_slides', ['title' => 'Launch banner', 'is_active' => 1]);
        $this->assertNotEmpty(HomepageSlide::first()->image);
    }

    public function test_homepage_renders_active_slides(): void
    {
        HomepageSlide::create([
            'title' => 'Visible',
            'image' => 'https://example.com/a.jpg',
            'mobile_image' => 'https://example.com/a-m.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        HomepageSlide::create([
            'title' => 'Hidden',
            'image' => 'https://example.com/b.jpg',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->get(route('shop.home'));

        $response->assertOk();
        $response->assertSee('hero-slider', false);
        $response->assertSee('https://example.com/a.jpg');
        $response->assertDontSee('https://example.com/b.jpg');
    }
}
