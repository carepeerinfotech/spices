<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderByDesc('id')->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['discount_type' => 'percentage', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->validated($request));

        return $this->respond($request, 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));

        return $this->respond($request, 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json(['success' => true, 'message' => 'Coupon deleted successfully.']);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'discount_type' => ['required', Rule::in(['flat', 'percentage'])],
            'value' => [
                'required', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('discount_type') === 'percentage' && (float) $value > 100) {
                        $fail('A percentage discount cannot exceed 100.');
                    }
                },
            ],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'code.regex' => 'Coupon code may only contain letters and numbers — no spaces or special characters.',
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.coupons.index'),
            ]);
        }

        return redirect()->route('admin.coupons.index')->with('success', $message);
    }
}
