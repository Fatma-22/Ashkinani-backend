<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Admin;

class CheckAdminPermission
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param Closure $next
     * @param string ...$permissions
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        // Owner has all permissions
        if ($user && $user->role === 'OWNER') {
            return $next($request);
        }

        // Check admin permissions
        if ($user && $user->role === 'ADMIN') {
            $admin = Admin::where('user_id', $user->id)->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin profile not found.'
                ], 403);
            }

            if (!$admin->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your admin account is suspended. Please contact support.'
                ], 403);
            }

            // Ensure permissions is always a proper array (handles cases where it is returned as a JSON string)
            $adminPermissions = $admin->permissions;
            if (is_string($adminPermissions)) {
                $adminPermissions = json_decode($adminPermissions, true) ?? [];
            }
            if (!is_array($adminPermissions)) {
                $adminPermissions = [];
            }

            // Check each required permission
            foreach ($permissions as $permission) {
                if (!isset($adminPermissions[$permission]) || !$adminPermissions[$permission]) {
                    return response()->json([
                        'success' => false,
                        'message' => "You do not have permission to {$permission}."
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
