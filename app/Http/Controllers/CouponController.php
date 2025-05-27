<?php

namespace App\Http\Controllers;
use App\Models\Coupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 12);
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $coupons->items(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
                'last_page' => $coupons->lastPage(),
            ]
        ]);
    }

    /**
     * Store new coupon
     * POST /api/coupons
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'cart_value' => 'required|numeric|min:0',
            'expiry_date' => 'required|date|after_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coupon = Coupon::create([
                'code' => $request->code,
                'type' => $request->type,
                'value' => $request->value,
                'cart_value' => $request->cart_value,
                'expiry_date' => $request->expiry_date
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon created successfully',
                'data' => $coupon
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single coupon
     * GET /api/coupons/{id}
     */
    public function show($id)
    {
        try {
            $coupon = Coupon::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $coupon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update coupon
     * PUT /api/coupons/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:coupons,code,'.$id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'cart_value' => 'required|numeric|min:0',
            'expiry_date' => 'required|date|after_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->update([
                'code' => $request->code,
                'type' => $request->type,
                'value' => $request->value,
                'cart_value' => $request->cart_value,
                'expiry_date' => $request->expiry_date
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon updated successfully',
                'data' => $coupon
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete coupon
     * DELETE /api/coupons/{id}
     */
    public function destroy($id)
    {
        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coupon deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate coupon
     * POST /api/coupons/validate
     */
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'cart_total' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coupon = Coupon::where('code', $request->code)
                          ->where('expiry_date', '>=', Carbon::today())
                          ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code'
                ], 404);
            }

            if ($request->cart_total < $coupon->cart_value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart total must be at least '.$coupon->cart_value
                ], 400);
            }

            $discount = $coupon->type === 'fixed' 
                ? $coupon->value 
                : ($request->cart_total * $coupon->value / 100);

            return response()->json([
                'success' => true,
                'data' => [
                    'coupon' => $coupon,
                    'discount' => $discount,
                    'final_total' => $request->cart_total - $discount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
