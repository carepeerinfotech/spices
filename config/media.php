<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

return [

    /*
    |--------------------------------------------------------------------------
    | Default disk
    |--------------------------------------------------------------------------
    |
    | Disk used for uploads when a collection does not override it.
    |
    */

    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Image owners
    |--------------------------------------------------------------------------
    |
    | Every model that owns images is registered here. The array key doubles as
    | the morph alias stored in images.imageable_type, so the database never
    | holds a PHP class name and models can be moved or renamed freely.
    |
    | Adding a module is a config entry plus the HasImages trait on the model —
    | no new controller, route, validation block or JavaScript. Collections are
    | the named slots on that model ('image', 'banner', 'gallery', ...) and are
    | bound to form fields as "{collection}_file" or "{collection}_files[]".
    |
    | Keys per collection:
    |   label      Shown by the <x-image-upload> component.
    |   directory  Upload target, relative to the disk root. "{id}" is replaced
    |              with the owner's key, so uploads can sit in per-record folders.
    |   multiple   true = gallery (many rows, sortable), false = single slot.
    |   max_kb     Upload size ceiling, fed straight into validation.
    |
    */

    'owners' => [

        'category' => [
            'model' => Category::class,
            'permission' => 'categories.manage',
            'collections' => [
                'image' => [
                    'label' => 'Category picture',
                    'directory' => 'categories',
                    'multiple' => false,
                    'max_kb' => 4096,
                ],
                'banner' => [
                    'label' => 'Category page banner',
                    'directory' => 'categories/banners',
                    'multiple' => false,
                    'max_kb' => 4096,
                ],
            ],
        ],

        'product' => [
            'model' => Product::class,
            'permission' => 'products.manage',
            'collections' => [
                'gallery' => [
                    'label' => 'Product images',
                    'directory' => 'products/{id}',
                    'multiple' => true,
                    'max_kb' => 4096,
                ],
            ],
        ],

        'product_variant' => [
            'model' => ProductVariant::class,
            'permission' => 'products.manage',
            'collections' => [
                'image' => [
                    'label' => 'Variant image',
                    'directory' => 'products/variants/{id}',
                    'multiple' => false,
                    'max_kb' => 4096,
                ],
            ],
        ],

    ],

];
