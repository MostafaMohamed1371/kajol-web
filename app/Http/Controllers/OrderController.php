<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 12);
        $orders = Order::with(['user', 'items'])
                      ->orderBy('created_at', 'DESC')
                      ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ]
        ]);
    }

    /**
     * Get order details with items and transaction
     * GET /api/orders/{order_id}/items
     */
    public function orderItems($order_id)
    {
        try {
            $order = Order::with(['user', 'items.product'])->findOrFail($order_id);
            $transaction = Transaction::where('order_id', $order_id)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'transaction' => $transaction
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update order status
     * PUT /api/orders/{order_id}/status
     */
    public function updateStatus(Request $request, $order_id)
    {
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:pending,processing,shipped,delivered,canceled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($order_id);
            $order->status = $request->order_status;
            
            // Set dates based on status
            if ($request->order_status == 'delivered') {
                $order->delivered_date = Carbon::now();
            } elseif ($request->order_status == 'canceled') {
                $order->canceled_date = Carbon::now();
            }
            
            $order->save();

            // Update transaction status if order is delivered
            if ($request->order_status == 'delivered') {
                Transaction::where('order_id', $order_id)
                          ->update(['status' => 'approved']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
