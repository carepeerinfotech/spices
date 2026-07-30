<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('banner')->nullable()->after('image');
            $table->string('meta_title')->nullable()->after('banner');
            $table->text('meta_description')->nullable()->after('meta_title');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('name');
            $table->string('hsn_code', 20)->nullable()->after('sku');
            $table->string('tax_class')->default('gst_18')->after('hsn_code');
            $table->boolean('allow_cod')->default(true)->after('is_active');
            $table->boolean('allow_online')->default(true)->after('allow_cod');
            $table->decimal('weight', 8, 3)->nullable()->after('allow_online');
            $table->decimal('length', 8, 2)->nullable()->after('weight');
            $table->decimal('breadth', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('breadth');
            $table->string('meta_title')->nullable()->after('height');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->boolean('has_variants')->default(false)->after('meta_description');
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name')->nullable();
            $table->string('option_label')->nullable();
            $table->json('option_values')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('breadth', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id']);
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_items_cart_product_variant_unique');
            $table->foreign('cart_id')->references('id')->on('carts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('variant_label')->nullable()->after('product_sku');
            $table->json('variant_options')->nullable()->after('variant_label');
            $table->decimal('weight', 8, 3)->nullable()->after('total');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('INR')->after('total');
            $table->string('payment_method')->default('cod')->after('currency');
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('tax_amount');
            $table->string('billing_name')->nullable()->after('customer_phone');
            $table->string('billing_email')->nullable()->after('billing_name');
            $table->string('billing_phone')->nullable()->after('billing_email');
            $table->text('billing_address')->nullable()->after('billing_phone');
            $table->string('billing_city')->nullable()->after('billing_address');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postal_code')->nullable()->after('billing_state');
            $table->string('billing_country')->default('IN')->after('billing_postal_code');
            $table->boolean('billing_same_as_shipping')->default(true)->after('billing_country');
            $table->unsignedInteger('estimated_delivery_days')->nullable()->after('notes');
            $table->string('courier_name')->nullable()->after('estimated_delivery_days');
            $table->decimal('shipping_weight', 8, 3)->nullable()->after('courier_name');
        });

        // Migrate existing products into a default variant
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $variantId = DB::table('product_variants')->insertGetId([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'option_label' => 'Default',
                'option_values' => json_encode([]),
                'price' => $product->price,
                'compare_price' => $product->compare_price,
                'stock' => $product->stock,
                'image' => $product->image,
                'is_default' => true,
                'is_active' => $product->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($product->image) {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'path' => $product->image,
                    'alt' => $product->name,
                    'sort_order' => 0,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('cart_items')->where('product_id', $product->id)->update([
                'product_variant_id' => $variantId,
            ]);

            DB::table('order_items')->where('product_id', $product->id)->update([
                'product_variant_id' => $variantId,
                'variant_label' => 'Default',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'currency', 'payment_method', 'payment_status', 'tax_percent',
                'billing_name', 'billing_email', 'billing_phone', 'billing_address',
                'billing_city', 'billing_state', 'billing_postal_code', 'billing_country',
                'billing_same_as_shipping', 'estimated_delivery_days', 'courier_name', 'shipping_weight',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['variant_label', 'variant_options', 'weight']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id', 'product_variant_id']);
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand', 'hsn_code', 'tax_class', 'allow_cod', 'allow_online',
                'weight', 'length', 'breadth', 'height', 'meta_title', 'meta_description', 'has_variants',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['banner', 'meta_title', 'meta_description']);
        });
    }
};
