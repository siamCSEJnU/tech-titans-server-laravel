<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginUpdateRequest;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $user;
    public function __construct()
    {
        $this->user = new User();
    }


    public function registration(UserRegisterRequest $request)
    {

        // if($request->password == $request->retype_password){
        //     return $this->user->create($request->all());
        // }
        // else{
        //     return "password not match";
        // }

        $validateData = $request->validated();
        $user = $this->user->create($validateData);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(UserLoginUpdateRequest $request)
    {
        $validateData = $request->validated();
        // $credentials = $request->only('email', 'password');
        $credentials = [
            'email' => $validateData['email'],
            'password' => $validateData['password'],
        ];

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard('api');
    }

    public function update(UserLoginUpdateRequest $request)
    {
        $validateData = $request->validated();

        $user = auth()->user();

        // Find user by email
        // $user = $this->user->where('email', $validateData['email'])->first();

        // $user = $this->user->find($validateData['email']);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads', 'public');
            $imageUrl = asset('storage/' . $path);
            $validateData['image'] = $imageUrl;
        }

        $user->update($validateData);
        return response()->json(['message' => 'Updated successfully', 'user' => $user], 201);
    }

    public function getAllUsers()
    {
        // Get the authenticated user
        $currentUser = auth()->user();

        // Check if user is authenticated and is an admin
        if (!$currentUser || $currentUser->type !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        // Return all users if admin
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Update user role (Admin only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRole(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'new_role' => 'required|string|in:admin,seller,user'
        ]);

        // Get the authenticated admin
        $admin = auth()->user();

        // Check if the current user is an admin
        if ($admin->type !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized - Only admins can change roles'
            ], 403);
        }

        // Prevent admin from modifying their own role
        if ($admin->id === $validated['user_id']) {
            return response()->json([
                'message' => 'You cannot change your own role'
            ], 403);
        }

        // Find the user to update
        $user = User::find($validated['user_id']);

        // Update the role
        $user->type = $validated['new_role'];
        $user->save();

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'new_role' => $user->type
            ]
        ]);
    }


    public function destroy(Request $request)
    {
        $currentUser = auth()->user();

        // Only allow admins to delete users
        if ($currentUser->type !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized - Only admins can delete users'
            ], 403);
        }

        // Find user by email (keeping your existing logic)
        $user = $this->user->where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Prevent admin from deleting themselves
        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Account deleted successfully'], 200);
    }

    // socail login
    public function redirectToProvider($provider)
    {
        if ($provider === 'github') {
            return Socialite::driver($provider)
                ->scopes(['user:email']) // Request email scope
                ->stateless()
                ->redirect();
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Handle cases where email might be null (common with GitHub)
            $email = $socialUser->getEmail();

            // Get user photo from social provider
            $avatarUrl = $socialUser->getAvatar();

            if (!$email) {
                // Create a placeholder email if none is provided
                $email = $socialUser->getId() . '@' . $provider . '.example.com';
            }

            // Try finding user
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Register the user if not found
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $email,
                    'password' => bcrypt(Str::random(24)),
                    'image' => $avatarUrl,
                    'email_verified_at' => now(),
                    'type' => "user"
                ]);
            }

            // Login and generate token
            $token = Auth::guard('api')->login($user);
            return redirect()->away("http://localhost:3000/auth/social-callback?access_token={$token}&avatar_url=" . urlencode($avatarUrl));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed', 'details' => $e->getMessage()], 500);
        }
    }
}
