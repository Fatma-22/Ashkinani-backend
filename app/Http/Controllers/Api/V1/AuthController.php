<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'PUBLIC',
            'phone' => $request->phone,
            'country' => $request->country,
            'member_type' => $request->member_type,
            'national_id' => $request->national_id,
            'organization' => $request->organization,
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'User registered successfully', 201);
    }

    /**
     * Login user and create token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid login credentials', 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->is_active) {
            Auth::logout();
            return $this->error('Your account is suspended. Please contact support.', 403);
        }

        // Check if role matches (only if role is explicitly provided in request)
        if ($request->filled('role') && $user->role !== $request->role) {
            Auth::logout();
            return $this->error('You do not have permission to access this portal.', 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Update authenticated User profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $data = [];
        if (isset($validated['name']))
            $data['name'] = $validated['name'];
        if (isset($validated['email']))
            $data['email'] = $validated['email'];

        if ($request->hasFile('avatar')) {
            // Delete old avatar if it's not a default one
            if ($user->avatar) {
                $this->mediaService->delete($user->avatar);
            }
            $data['avatar'] = $this->mediaService->upload($request->file('avatar'), 'users/avatars');
        }

        if (isset($validated['new_password'])) {
            $data['password'] = Hash::make($validated['new_password']);
        }

        if (isset($validated['organization']))
            $data['organization'] = $validated['organization'];
        if (isset($validated['phone']))
            $data['phone'] = $validated['phone'];
        if (isset($validated['country']))
            $data['country'] = $validated['country'];
            
        $user->update($data);

        // If user has an admin profile, sync name and phone to it
        if ($user->role === 'ADMIN' && $user->adminProfile) {
            $adminData = [];
            if (isset($data['name'])) $adminData['name'] = $data['name'];
            if (isset($data['phone'])) $adminData['phone'] = $data['phone'];
            if (isset($data['email'])) $adminData['email'] = $data['email'];
            if (isset($data['avatar'])) $adminData['avatar'] = $data['avatar'];
            
            if (!empty($adminData)) {
                $user->adminProfile->update($adminData);
            }
        }
        
        return $this->success(new UserResource($user->fresh()), 'Profile updated successfully');
    }

    /**
     * Logout user (Revoke token)
     */
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get the authenticated User
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();
        
        // Eager load admin profile if user is admin
        if ($user->role === 'ADMIN') {
            $user->load('adminProfile');
        }
        
        return $this->success(new UserResource($user));
    }
}
