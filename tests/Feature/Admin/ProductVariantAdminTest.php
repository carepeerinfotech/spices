<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $permission = Permission::create(['name' => 'Manage Products', 'slug' => 'products.manage', 'group' => 'Catalog']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_admin_can_create_product_with_variants(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('admin.products.store'), [
            'name' => 'Variant Tee',
            'sku' => 'TEE-BASE',
            'price' => 500,
            'stock' => 0,
            'is_active' => 1,
            'allow_cod' => 1,
            'allow_online' => 1,
            'options' => [
                ['name' => 'Size', 'values' => ['S,M']],
            ],
            'variants' => [
                ['sku' => 'TEE-S', 'option_label' => 'S', 'price' => 500, 'stock' => 3, 'is_default' => 1, 'is_active' => 1],
                ['sku' => 'TEE-M', 'option_label' => 'M', 'price' => 550, 'stock' => 4, 'is_active' => 1],
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $product = Product::where('sku', 'TEE-S')->orWhere('name', 'Variant Tee')->first();
        $this->assertNotNull($product);
        $this->assertEquals(2, $product->variants()->count());
        $this->assertEquals(7, $product->fresh()->stock);
    }
}
