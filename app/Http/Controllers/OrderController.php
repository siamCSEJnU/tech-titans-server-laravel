<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // Place a new order
    public function placeOrder(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|string|in:cod,credit_card,paypal'
        ]);

        $product = Product::findOrFail($request->product_id);

        $order = Order::create([
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'total_price' => $product->price * $request->quantity,
            'status' => 'pending',
            'shipping_address' => $request->shipping_address,
            'payment_method' => $request->payment_method
        ]);

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order
        ], 201);
    }

    // Get all orders for logged-in user
    public function getUserOrders()
    {
        $orders = Order::with('product')
            ->forUser(Auth::id())
            ->latest()
            ->get();

        return response()->json($orders);
    }

    // Get details of a specific order
    public function getOrderDetails($id)
    {
        $order = Order::with('product')
            ->forUser(Auth::id())
            ->findOrFail($id);

        return response()->json($order);
    }

    // Cancel an order
    public function cancelOrder($id)
    {
        $order = Order::forUser(Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }

    // Admin: Get all orders
    public function getAllOrders()
    {
        $this->authorize('viewAll', Order::class);
        
        $orders = Order::with(['user', 'product'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    // Admin: Update order status
    public function updateOrderStatus(Request $request, $id)
    {
        $this->authorize('update', Order::class);

        $request->validate([
            'status' => 'required|in:processing,shipped,delivered'
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }
}
