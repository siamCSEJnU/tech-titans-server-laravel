<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\User;
use App\Http\Requests\PaymentRegisterRequest;

class PaymentController extends Controller
{
    protected $user;
    public function __construct()
    {
        $this->user = new User();
    }

    public function store(PaymentRegisterRequest $request)
    {


        $user = User::where('email', $request->email)
            ->where('mobile_number', $request->mobile_number)
            ->firstOrFail();

        $payment = Payment::create([
            'transaction_id' => $request->transaction_id,
            'total_amount' => $request->total_amount,
            'user_id' => $user->id,
        ]);

        foreach ($request->products as $product) {
            $payment->items()->create([
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $product['quantity'],
            ]);
        }

        return response()->json([
            'message' => 'Payment successful',
            'payment' => $payment->load('items.product')
        ], 201);
    }

    public function index(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        return Payment::where('user_id', $user->id)
            ->with(['user', 'items.product'])
            ->get();
    }
}
