<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'sku' => 'SKU-'.fake()->unique()->bothify('??###'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 100, 5000),
            'stock' => 20,
            'is_active' => true,
            'is_featured' => false,
            'allow_cod' => true,
            'allow_online' => true,
            'weight' => 0.5,
            'has_variants' => false,
            'tax_class' => 'gst_18',
        ];
    }
}
