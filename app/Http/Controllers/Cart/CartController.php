<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\User;
use App\Http\Requests\CartRegisterRequest;
use App\Http\Requests\CartUpdateRequest;

class CartController extends Controller
{
    protected $user;
    public function __construct(){
        $this->user = new User();
    }

    public function store(CartRegisterRequest $request){

        $validateData = $request->validated();

        $user = $this->user->where('email', $validateData['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        else{
            
            $cart = Cart::create([
                'price' => $validateData['price'],
                'quantity' => $validateData['quantity'],
                'subtotal' => $validateData['price'] * $validateData['quantity'],
                'user_id' => $user->id,
                'product_id' => $validateData['product_id'],
            ]);
        
            return response()->json(['message' => 'Added to cart successfully!', 'data' => $cart], 201);
        }
    }

    public function index(Request $request){
        $user = $this->user->where('email', $request->email)->first();

        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        else{
            $show_cart = Cart::where('user_id', $user->id)->with(['user', 'product'])->get();

            $total_order = 0;

            // Loop using count() or foreach
            foreach ($show_cart as $item) {
                $total_order += $item->subtotal;
            }

            return response()->json(['data' => $show_cart, 'total_order' => $total_order], 201);
        }
    }

    public function update(CartUpdateRequest $request)
    {
        $validateData = $request->validated();

        // Find user by email
        $user = $this->user->where('email', $validateData['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        else{
            $cart_exist = Cart::where('user_id', $user->id)->where('product_id', $validateData['product_id'])->first();

            if($cart_exist){
                $cart = $cart_exist->update([
                    'price' => $validateData['price'],
                    'quantity' => $validateData['quantity'],
                    'subtotal' => $validateData['price'] * $validateData['quantity'],
                ]);
                return response()->json(['message' => 'Cart Item Updated successfully', 'data' => $cart], 201);
            }
            else {
                return response()->json(['message' => 'Cart item not found'], 404);
            }
        }
    }

    public function destroy(Request $request){
        $user = $this->user->where('email', $request->email)->first();

        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        else{
            $cart = Cart::where('user_id', $user->id)->where('product_id', $request->product_id)->delete();
            if($cart){
                return response()->json(['message' => 'Cart Item delete successfully'], 201);
            }
        }
    }
}
