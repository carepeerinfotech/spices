<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\CartService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(CartService $cartService)
    {
        return view('account.addresses.index', [
            'addresses' => auth()->user()->addresses()->latest()->get(),
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;

        $address = Address::create($data);
        $this->syncDefaults($request->user(), $address, $data);

        return response()->json([
            'success' => true,
            'message' => 'Address saved.',
            'redirect' => route('account.addresses.index'),
        ]);
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($request, $address);
        $data = $this->validated($request);
        $address->update($data);
        $this->syncDefaults($request->user(), $address, $data);

        return response()->json([
            'success' => true,
            'message' => 'Address updated.',
            'redirect' => route('account.addresses.index'),
        ]);
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorizeAddress($request, $address);
        $address->delete();

        return response()->json(['success' => true, 'message' => 'Address deleted.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'regex:/^\d{6}$/'],
            'country' => ['nullable', 'string', 'max:2'],
            'is_default_shipping' => ['sometimes', 'boolean'],
            'is_default_billing' => ['sometimes', 'boolean'],
        ]);

        $data['label'] = $data['label'] ?: 'Home';
        $data['country'] = $data['country'] ?: 'IN';
        $data['is_default_shipping'] = $request->boolean('is_default_shipping');
        $data['is_default_billing'] = $request->boolean('is_default_billing');

        return $data;
    }

    private function syncDefaults($user, Address $address, array $data): void
    {
        if (! empty($data['is_default_shipping'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
        }
        if (! empty($data['is_default_billing'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }
}
