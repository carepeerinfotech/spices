<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-'.fake()->unique()->bothify('??###'),
            'name' => fake()->words(2, true),
            'option_label' => 'Default',
            'option_values' => [],
            'price' => fake()->randomFloat(2, 100, 5000),
            'stock' => 20,
            'weight' => 0.5,
            'is_default' => true,
            'is_active' => true,
        ];
    }
}
