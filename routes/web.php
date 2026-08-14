<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\AuthController as AccountAuthController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\HomepageSlideController;
use App\Http\Controllers\Admin\ImageController as AdminImageController;
use App\Http\Controllers\Admin\NewsletterSubscriberController as AdminNewsletterSubscriberController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\StorageLinkController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Shop\AboutController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\PaymentController;
use App\Http\Controllers\Shop\PrivacyPolicyController;
use App\Http\Controllers\Shop\RefundPolicyController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Controllers\Shop\ShippingPolicyController;
use App\Http\Controllers\Shop\ShippingQuoteController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\TermsController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('shop.home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.catalog');
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('shop.product');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('shop.page');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

Route::get('/about-us', [AboutController::class, 'show'])->name('shop.about');

Route::get('/contact', [ContactController::class, 'show'])->name('shop.contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('shop.contact.store');

Route::get('/shipping-policy', [ShippingPolicyController::class, 'show'])->name('shop.shipping-policy');
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('shop.privacy-policy');
Route::get('/refund-and-return-policy', [RefundPolicyController::class, 'show'])->name('shop.refund-policy');
Route::get('/terms-and-conditions', [TermsController::class, 'show'])->name('shop.terms');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])->middleware('throttle:10,1')->name('shop.newsletter.subscribe');

Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
Route::get('/cart/summary', [CartController::class, 'summary'])->name('shop.cart.summary');
Route::post('/cart', [CartController::class, 'store'])->name('shop.cart.store');
Route::put('/cart/{item}', [CartController::class, 'update'])->name('shop.cart.update');
Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('shop.cart.destroy');

Route::post('/shipping/quote/product', [ShippingQuoteController::class, 'product'])->name('shipping.quote.product');
Route::post('/shipping/quote/cart', [ShippingQuoteController::class, 'cart'])->name('shipping.quote.cart');

// Checkout is deliberately open to guests: the account is created from the
// checkout form itself, so a login wall here only costs orders.
Route::get('/checkout', [CheckoutController::class, 'index'])->name('shop.checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1')->name('shop.checkout.store');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('shop.checkout.success');

// The fake gateway stands in for Paytm's hosted page, which is likewise
// reached without a session, so it cannot sit behind `auth` either.
Route::get('/payments/paytm/fake/{transaction}', [PaymentController::class, 'fakePaytm'])->name('payments.paytm.fake');
Route::post('/payments/paytm/fake/{transaction}', [PaymentController::class, 'fakePaytmComplete'])->name('payments.paytm.fake.complete');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AccountAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AccountAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register', [AccountAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AccountAuthController::class, 'register'])->middleware('throttle:10,1')->name('register.submit');
});

// Not guest-only: a signed-in customer requests a reset link from their account
// page, and `guest` would bounce them off their own link when they open it.
Route::middleware('feature:password_reset')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [AccountAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::middleware('feature:email_verification')->group(function () {
        Route::get('/email/verify', fn () => view('account.auth.verify'))->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            return redirect()->route('account.dashboard')->with('success', 'Email verified.');
        })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return response()->json(['success' => true, 'message' => 'Verification link sent.']);
        })->middleware('throttle:6,1')->name('verification.send');
    });

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountDashboardController::class, 'index'])->name('dashboard');
        Route::put('/profile', [AccountDashboardController::class, 'updateProfile'])->name('profile');
        Route::put('/password', [AccountDashboardController::class, 'updatePassword'])->name('password');
        Route::post('/password/reset-link', [AccountDashboardController::class, 'sendPasswordResetLink'])
            ->middleware(['feature:password_reset', 'throttle:6,1'])
            ->name('password.link');
        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    });
});

Route::post('/payments/paytm/callback', [PaymentController::class, 'paytmCallback'])->name('payments.paytm.callback');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('storage-link', [StorageLinkController::class, 'store'])->name('storage-link');

        // Shared by every module using HasImages; each image is guarded by the
        // permission its owner declares in config/media.php.
        Route::get('images/{image}/download', [AdminImageController::class, 'download'])->name('images.download');
        Route::post('images/reorder', [AdminImageController::class, 'reorder'])->name('images.reorder');
        Route::delete('images/{image}', [AdminImageController::class, 'destroy'])->name('images.destroy');
        Route::post('images/{image}/primary', [AdminImageController::class, 'primary'])->name('images.primary');

        Route::middleware('permission:users.manage')->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
        });
        Route::middleware('permission:roles.manage')->group(function () {
            Route::resource('roles', RoleController::class)->except(['show']);
        });
        Route::middleware('permission:pages.manage')->group(function () {
            Route::resource('pages', CmsPageController::class)->except(['show']);
            Route::get('homepage', [HomepageController::class, 'edit'])->name('homepage.edit');
            Route::put('homepage', [HomepageController::class, 'update'])->name('homepage.update');
            Route::resource('homepage-slides', HomepageSlideController::class)->except(['show']);
            Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
            Route::get('email-templates/{email_template}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
            Route::put('email-templates/{email_template}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
            Route::post('email-templates/{email_template}/test', [EmailTemplateController::class, 'testSend'])->name('email-templates.test');
        });
        Route::middleware('permission:categories.manage')->group(function () {
            Route::resource('categories', AdminCategoryController::class)->except(['show']);
        });
        Route::middleware('permission:contact-messages.manage')->group(function () {
            Route::get('contact-messages', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::get('contact-messages/{contact_message}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
            Route::delete('contact-messages/{contact_message}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

            // Newsletter signups come from the same storefront forms, so they sit
            // behind the permission that already covers storefront enquiries.
            Route::get('newsletter-subscribers', [AdminNewsletterSubscriberController::class, 'index'])->name('newsletter-subscribers.index');
            Route::get('newsletter-subscribers/export', [AdminNewsletterSubscriberController::class, 'export'])->name('newsletter-subscribers.export');
            Route::delete('newsletter-subscribers/{newsletter_subscriber}', [AdminNewsletterSubscriberController::class, 'destroy'])->name('newsletter-subscribers.destroy');
        });
        Route::middleware('permission:products.manage')->group(function () {
            Route::resource('products', AdminProductController::class)->except(['show']);
        });
        Route::middleware('permission:orders.manage')->group(function () {
            Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('orders/{order}/shiprocket', [ShipmentController::class, 'sendToShiprocket'])->name('orders.shiprocket');
            Route::post('shipments/{shipment}/awb', [ShipmentController::class, 'assignAwb'])->name('shipments.awb');
            Route::post('shipments/{shipment}/pickup', [ShipmentController::class, 'pickup'])->name('shipments.pickup');
            Route::get('shipments/{shipment}/track', [ShipmentController::class, 'track'])->name('shipments.track');
            Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
            Route::post('shipments/{shipment}/return', [ShipmentController::class, 'createReturn'])->name('shipments.return');
        });

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/test-paytm', [SettingsController::class, 'testPaytm'])->name('settings.test-paytm');
        Route::post('settings/test-shiprocket', [SettingsController::class, 'testShiprocket'])->name('settings.test-shiprocket');
    });
});
