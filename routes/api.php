<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\WishList\WishListController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;

// User order routes
Route::middleware('auth:api')->group(function () {
    Route::post('/orders/place', [OrderController::class, 'placeOrder']);
    Route::get('/orders', [OrderController::class, 'getUserOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);
    Route::put('/orders/cancel/{id}', [OrderController::class, 'cancelOrder']);
});

// Admin order routes
Route::middleware(['auth:api', 'can:viewAll,App\Models\Order'])->group(function () {
    Route::get('/admin/orders', [OrderController::class, 'getAllOrders']);
    Route::put('/admin/orders/update-status/{id}', [OrderController::class, 'updateOrderStatus']);
});


Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::get('categories', [CategoryController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    Route::get('seller/products', [ProductController::class, 'sellerProducts']);
});

Route::apiResource('categories', CategoryController::class); // Keep public if needed

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function ($router) {

    Route::post('register', [AuthController::class, 'registration']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::post('/make-admin', [AuthController::class, 'makeAdmin']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('all_users', [AuthController::class, 'getAllUsers']);
    Route::put('update_user_role', [AuthController::class, 'updateUserRole']);
    Route::put('update_profile', [AuthController::class, 'update']);
    Route::delete('delete', [AuthController::class, 'destroy']);
    Route::post('wishlist/register', [WishListController::class, 'store']);
    Route::get('wishlist/show', [WishListController::class, 'index']);
    Route::delete('wishlist/delete', [WishListController::class, 'destroy']);
    Route::post('cart/register', [CartController::class, 'store']);
    Route::put('cart/update', [CartController::class, 'update']);
    Route::get('cart/show', [CartController::class, 'index']);
    Route::delete('cart/delete', [CartController::class, 'destroy']);
    Route::post('payment/register', [PaymentController::class, 'store']);
    Route::get('payment/show', [PaymentController::class, 'index']);
    // Route::get('seller/products', [ProductController::class, 'sellerProducts']);
});

// Reviews routes

Route::middleware('auth:api')->group(function () {
    // Reviews
    Route::post('/reviews/{productId}', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});

// Public route
Route::get('/reviews/{productId}', [ReviewController::class, 'index']);

//Socail Login routes
Route::get('/auth/redirect/{provider}', [AuthController::class, 'redirectToProvider']);
Route::get('/auth/callback/{provider}', [AuthController::class, 'handleProviderCallback']);
