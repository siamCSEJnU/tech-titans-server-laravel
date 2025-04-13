<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // POST /api/reviews/{productId}
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:1000'
        ]);

        // Check if user already reviewed this product
        $existingReview = ProductReview::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product'
            ], 409);
        }

        $review = ProductReview::create([
            'user_id' => Auth::id(),
            'product_id' => $productId,
            'rating' => $request->rating,
            'review' => $request->review
        ]);

        return response()->json([
            'message' => 'Review added successfully',
            'review' => $review
        ], 201);
    }

    // GET /api/reviews/{productId}
    public function index($productId)
    {
        $reviews = ProductReview::with('user')
            ->where('product_id', $productId)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    // PUT /api/reviews/{id}
    public function update(Request $request, $id)
    {
        $review = ProductReview::findOrFail($id);

        // Authorization - only review owner can update
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not authorized to update this review'
            ], 403);
        }

        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'review' => 'sometimes|string|max:1000'
        ]);

        $review->update($request->only(['rating', 'review']));

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review
        ]);
    }

    // DELETE /api/reviews/{id}
    public function destroy($id)
    {
        $review = ProductReview::findOrFail($id);

        // Authorization - only review owner or admin can delete
        if ($review->user_id !== Auth::id() && Auth::user()->type !== 'admin') {
            return response()->json([
                'message' => 'You are not authorized to delete this review'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
