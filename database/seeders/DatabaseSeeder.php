<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\OrderEmailTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'Dashboard'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'group' => 'Users'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'Roles'],
            ['name' => 'Manage CMS Pages', 'slug' => 'pages.manage', 'group' => 'CMS'],
            ['name' => 'Manage Contact Messages', 'slug' => 'contact-messages.manage', 'group' => 'CMS'],
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'group' => 'Catalog'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'group' => 'Catalog'],
            ['name' => 'Manage Orders', 'slug' => 'orders.manage', 'group' => 'Orders'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $allPermissionIds = Permission::pluck('id');

        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full system access', 'is_active' => true]
        );
        $superAdmin->permissions()->sync($allPermissionIds);

        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Store administration', 'is_active' => true]
        );
        $admin->permissions()->sync($allPermissionIds);

        $editor = Role::updateOrCreate(
            ['slug' => 'editor'],
            ['name' => 'Editor', 'description' => 'Content and catalog editing', 'is_active' => true]
        );
        $editor->permissions()->sync(
            Permission::whereIn('slug', ['dashboard.view', 'pages.manage', 'categories.manage', 'products.manage'])->pluck('id')
        );

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@elephant.com'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_customer' => false,
                'email_verified_at' => now(),
            ]
        );
        $adminUser->roles()->sync([$superAdmin->id]);

        $customer = User::updateOrCreate(
            ['email' => 'customer@elephant.com'],
            [
                'name' => 'Demo Customer',
                'phone' => '9876543210',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_customer' => true,
                'email_verified_at' => now(),
            ]
        );
        $customer->addresses()->updateOrCreate(
            ['label' => 'Home', 'postal_code' => '110001'],
            [
                'name' => 'Demo Customer',
                'phone' => '9876543210',
                'email' => 'customer@elephant.com',
                'address_line1' => '12 Connaught Place',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'country' => 'IN',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]
        );

        $settings = app(SettingsService::class);
        $settings->setMany('commerce', [
            'currency' => 'INR',
            'gst_percent' => 18,
            'store_name' => 'Elephant Shop',
            'support_email' => 'hello@elephantshop.test',
            'pickup_pincode' => '110001',
        ], ['gst_percent' => ['type' => 'float']]);
        $settings->setMany('payments', [
            'cod_enabled' => true,
            'online_enabled' => true,
        ], ['cod_enabled' => ['type' => 'boolean'], 'online_enabled' => ['type' => 'boolean']]);
        $settings->setMany('paytm', [
            'driver' => 'fake',
            'environment' => 'staging',
            'merchant_id' => '',
            'website' => 'WEBSTAGING',
            'industry_type' => 'Retail',
        ]);
        $settings->setMany('email', [
            'mailer' => 'log',
            'host' => '127.0.0.1',
            'port' => 587,
            'encryption' => 'tls',
            'from_address' => 'hello@elephantshop.test',
            'from_name' => 'Elephant Shop',
            'admin_email' => '',
        ], ['port' => ['type' => 'integer']]);
        $settings->setMany('notifications', [
            'enabled' => true,
            'notify_order_placed' => true,
            'notify_order_placed_admin' => true,
            'notify_contact_message_admin' => true,
            'notify_newsletter_signup_admin' => true,
            'notify_payment_result' => true,
            'notify_shipment_update' => true,
            'notify_verify_email' => true,
        ], [
            'enabled' => ['type' => 'boolean'],
            'notify_order_placed' => ['type' => 'boolean'],
            'notify_order_placed_admin' => ['type' => 'boolean'],
            'notify_contact_message_admin' => ['type' => 'boolean'],
            'notify_newsletter_signup_admin' => ['type' => 'boolean'],
            'notify_payment_result' => ['type' => 'boolean'],
            'notify_shipment_update' => ['type' => 'boolean'],
            'notify_verify_email' => ['type' => 'boolean'],
        ]);
        $settings->setMany('shipping', [
            'charges_enabled' => true,
            'flat_rate' => 49,
            'free_above' => 999,
            'show_delivery_details' => false,
        ], [
            'charges_enabled' => ['type' => 'boolean'],
            'show_delivery_details' => ['type' => 'boolean'],
            'flat_rate' => ['type' => 'float'],
            'free_above' => ['type' => 'float'],
        ]);
        $settings->setMany('shiprocket', [
            'enabled' => true,
            'driver' => 'fake',
            'pickup_location' => 'Primary',
            'email' => '',
        ], ['enabled' => ['type' => 'boolean']]);

        $orderTemplates = [];
        foreach (OrderEmailTemplates::all() as $slug => $template) {
            $orderTemplates[] = ['slug' => $slug] + $template;
        }

        foreach ([
            ...$orderTemplates,
            [
                'slug' => 'contact_message_admin',
                'name' => 'Contact Enquiry (Admin)',
                'subject' => 'New enquiry from {{name}}',
                'body' => '<p>A new enquiry arrived on {{received_at}}.</p>'
                    .'<p>Name: {{name}}<br>Email: {{email}}<br>Phone: {{phone}}</p>'
                    .'<p>Message:<br>{{message}}</p>',
                'placeholders' => ['name', 'email', 'phone', 'message', 'received_at'],
            ],
            [
                'slug' => 'newsletter_signup_admin',
                'name' => 'Newsletter Signup (Admin)',
                'subject' => 'New newsletter subscriber',
                'body' => '<p><strong>{{email}}</strong> subscribed to the newsletter on {{subscribed_at}}.</p>'
                    .'<p>That makes {{total_subscribers}} subscribers in total.</p>',
                'placeholders' => ['email', 'subscribed_at', 'total_subscribers'],
            ],
            [
                'slug' => 'payment_result',
                'name' => 'Payment Result',
                'subject' => 'Payment {{status}} for {{order_number}}',
                'body' => '<p>Hi {{customer_name}},</p><p>Payment for order {{order_number}} is {{status}}. Amount: {{total}}</p>',
                'placeholders' => ['customer_name', 'order_number', 'status', 'total'],
            ],
            [
                'slug' => 'shipment_update',
                'name' => 'Shipment Update',
                'subject' => 'Shipment update for {{order_number}}',
                'body' => '<p>Hi {{customer_name}},</p><p>Your order {{order_number}} shipment status is {{status}}. AWB: {{awb}}. Courier: {{courier}}</p>',
                'placeholders' => ['customer_name', 'order_number', 'status', 'awb', 'courier'],
            ],
        ] as $template) {
            EmailTemplate::updateOrCreate(['slug' => $template['slug']], $template + ['is_active' => true]);
        }

        $this->call(SpiceCatalogSeeder::class);
    }
}
