@extends('admin.layouts.app')

@section('title', 'Settings')
@section('heading', 'Settings')
@section('subtitle', 'Features, payments, email, shipping and notifications')

@section('content')
<div class="space-y-6">
    @foreach([
        'features' => 'Features',
        'commerce' => 'General commerce',
        'payments' => 'Payment modes',
        'paytm' => 'Paytm configuration',
        'email' => 'Email / SMTP',
        'notifications' => 'Notifications',
        'shipping' => 'Shipping charges',
        'shiprocket' => 'Shiprocket',
    ] as $group => $title)
        <form data-ajax method="POST" action="{{ route('admin.settings.update') }}" class="rounded-xl bg-white border border-slate-200 p-5 space-y-4">
            @csrf
            <input type="hidden" name="group" value="{{ $group }}">
            <h2 class="font-medium text-lg">{{ $title }}</h2>

            @if($group === 'features')
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="email_verification" value="1" data-bool @checked($features['email_verification'] ?? \App\Support\Features::DEFAULTS['email_verification'])> Email verification (customers must verify before checkout)</label>
                <label class="inline-flex items-center gap-2 text-sm block"><input type="checkbox" name="password_reset" value="1" data-bool @checked($features['password_reset'] ?? \App\Support\Features::DEFAULTS['password_reset'])> Forgot / reset password</label>
            @elseif($group === 'commerce')
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="text-sm">Store name</label><input name="store_name" value="{{ $commerce['store_name'] ?? 'Elephant Shop' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Support email</label><input name="support_email" value="{{ $commerce['support_email'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Currency</label><input name="currency" value="{{ $commerce['currency'] ?? 'INR' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">GST %</label><input name="gst_percent" type="number" step="0.01" value="{{ $commerce['gst_percent'] ?? 18 }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Pickup pincode</label><input name="pickup_pincode" value="{{ $commerce['pickup_pincode'] ?? '110001' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                </div>
            @elseif($group === 'payments')
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="cod_enabled" value="1" data-bool @checked($payments['cod_enabled'] ?? true)> COD enabled</label>
                <label class="inline-flex items-center gap-2 text-sm block"><input type="checkbox" name="online_enabled" value="1" data-bool @checked($payments['online_enabled'] ?? true)> Online payment enabled</label>
            @elseif($group === 'paytm')
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="text-sm">Driver</label>
                        <select name="driver" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="fake" @selected(($paytm['driver'] ?? 'fake') === 'fake')>Fake (testing)</option>
                            <option value="live" @selected(($paytm['driver'] ?? '') === 'live')>Live API</option>
                        </select>
                    </div>
                    <div><label class="text-sm">Environment</label>
                        <select name="environment" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="staging" @selected(($paytm['environment'] ?? 'staging') === 'staging')>Staging</option>
                            <option value="production" @selected(($paytm['environment'] ?? '') === 'production')>Production</option>
                        </select>
                    </div>
                    <div><label class="text-sm">Merchant ID</label><input name="merchant_id" value="{{ $paytm['merchant_id'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Merchant Key {{ $masked['paytm_key'] ? '('.$masked['paytm_key'].')' : '' }}</label><input name="merchant_key" placeholder="Leave blank to keep" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Website</label><input name="website" value="{{ $paytm['website'] ?? 'WEBSTAGING' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Industry type</label><input name="industry_type" value="{{ $paytm['industry_type'] ?? 'Retail' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                </div>
                <button type="button" id="test-paytm" class="text-sm text-teal-700">Test Paytm connection</button>
            @elseif($group === 'email')
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="text-sm">Mailer</label><input name="mailer" value="{{ $email['mailer'] ?? 'log' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Host</label><input name="host" value="{{ $email['host'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Port</label><input name="port" type="number" value="{{ $email['port'] ?? 587 }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Encryption</label><input name="encryption" value="{{ $email['encryption'] ?? 'tls' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Username</label><input name="username" value="{{ $email['username'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Password {{ $masked['email_password'] ? '('.$masked['email_password'].')' : '' }}</label><input name="password" placeholder="Leave blank to keep" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">From address</label><input name="from_address" value="{{ $email['from_address'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">From name</label><input name="from_name" value="{{ $email['from_name'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                </div>
            @elseif($group === 'notifications')
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="enabled" value="1" data-bool @checked($notifications['enabled'] ?? true)> Notifications active</label>
                <label class="inline-flex items-center gap-2 text-sm block"><input type="checkbox" name="notify_order_placed" value="1" data-bool @checked($notifications['notify_order_placed'] ?? true)> Order placed</label>
                <label class="inline-flex items-center gap-2 text-sm block"><input type="checkbox" name="notify_payment_result" value="1" data-bool @checked($notifications['notify_payment_result'] ?? true)> Payment result</label>
                <label class="inline-flex items-center gap-2 text-sm block"><input type="checkbox" name="notify_shipment_update" value="1" data-bool @checked($notifications['notify_shipment_update'] ?? true)> Shipment updates</label>
                <label class="inline-flex items-center gap-2 text-sm block"><input type="checkbox" name="notify_verify_email" value="1" data-bool @checked($notifications['notify_verify_email'] ?? true)> Verify email</label>
            @elseif($group === 'shipping')
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="charges_enabled" value="1" data-bool @checked($shipping['charges_enabled'] ?? true)> Shipping charges active</label>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="text-sm">Fallback flat rate (INR)</label><input name="flat_rate" type="number" step="0.01" value="{{ $shipping['flat_rate'] ?? 49 }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Free shipping above (INR)</label><input name="free_above" type="number" step="0.01" value="{{ $shipping['free_above'] ?? 999 }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                </div>
            @elseif($group === 'shiprocket')
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="enabled" value="1" data-bool @checked($shiprocket['enabled'] ?? false)> Shiprocket enabled</label>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="text-sm">Driver</label>
                        <select name="driver" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="fake" @selected(($shiprocket['driver'] ?? 'fake') === 'fake')>Fake</option>
                            <option value="live" @selected(($shiprocket['driver'] ?? '') === 'live')>Live API</option>
                        </select>
                    </div>
                    <div><label class="text-sm">Pickup location name</label><input name="pickup_location" value="{{ $shiprocket['pickup_location'] ?? 'Primary' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">API email</label><input name="email" value="{{ $shiprocket['email'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">API password {{ $masked['shiprocket_password'] ? '('.$masked['shiprocket_password'].')' : '' }}</label><input name="password" placeholder="Leave blank to keep" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    <div><label class="text-sm">Channel ID</label><input name="channel_id" value="{{ $shiprocket['channel_id'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                </div>
                <button type="button" id="test-shiprocket" class="text-sm text-teal-700">Test Shiprocket connection</button>
            @endif

            <button type="submit" class="rounded-lg bg-teal-700 hover:bg-teal-600 text-white px-4 py-2 text-sm">Save {{ $title }}</button>
        </form>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
document.getElementById('test-paytm')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.settings.test-paytm')), { method: 'POST', body: {} });
});
document.getElementById('test-shiprocket')?.addEventListener('click', function () {
  AppAjax.request(@json(route('admin.settings.test-shiprocket')), { method: 'POST', body: {} });
});
</script>
@endpush
