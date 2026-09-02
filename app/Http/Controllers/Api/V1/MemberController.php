<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\ArabicSearchService;

class MemberController extends Controller
{
    protected $arabicSearchService;

    public function __construct(ArabicSearchService $arabicSearchService)
    {
        $this->arabicSearchService = $arabicSearchService;
    }
    /**
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        if (!$this->checkPermission('canManageMembers')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Filter for role PUBLIC
        $query = User::where('role', 'PUBLIC');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            
            $likePattern = implode('%', $words) . '%';
            $arabicPatterns = array_map(fn($w) => $this->arabicSearchService->generateRegexPattern($w), $words);
            $regexpPattern = '^' . implode('.*', $arabicPatterns);

            $query->where(function ($q) use ($search, $likePattern, $regexpPattern) {
                $q->where('name', 'like', $likePattern)
                    ->orWhere('email', 'like', "{$search}%")
                    ->orWhere('phone', 'like', "{$search}%");
            });
        }

        $perPage = $request->query('per_page');
        if ($perPage === 'all') {
            $members = $query->latest()->get();
        } else {
            $members = $query->latest()->paginate($perPage ?: 20);
        }

        return UserResource::collection($members);
    }

    /**
     * Toggle member activation status.
     */
    public function toggleStatus(User $user)
    {
        if (!$this->checkPermission('canManageMembers')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();
        
        // If we just deactivated the user, logout them from all devices
        if (!$user->is_active) {
            $user->tokens()->delete();
        }
        
        return response()->json([
            'success' => true,
            'isActive' => $user->is_active, // Match frontend property name
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * Remove the specified member.
     */
    public function destroy(User $user)
    {
        if (!$this->checkPermission('canManageMembers')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user is a member/public role just in case
        if ($user->role !== 'PUBLIC') {
            return response()->json(['message' => 'Cannot delete non-member users through this endpoint'], 403);
        }

        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Member deleted successfully'
        ]);
    }

    /**
     * Reset a member's password to the default "12345678".
     */
    public function resetPassword(User $user)
    {
        if (!$this->checkPermission('canManageMembers')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role !== 'PUBLIC') {
            return response()->json(['message' => 'Cannot reset password for non-member users through this endpoint'], 403);
        }

        $user->password = Hash::make('12345678');
        $user->save();

        // Force logout from all devices after password reset
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    private function checkPermission($permission)
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->role === 'OWNER') {
            return true;
        }
        
        if ($user->role === 'ADMIN') {
            $admin = $user->adminProfile;
            if (!$admin || !$admin->is_active) {
                return false;
            }
            $perms = $admin->permissions;
            if (is_string($perms)) {
                $perms = json_decode($perms, true) ?? [];
            }
            return (bool)($perms[$permission] ?? false);
        }
        
        return false;
    }
}
