@props(['amount', 'currency' => 'INR'])
<span {{ $attributes }}>{{ \App\Support\Money::format($amount, $currency) }}</span>
