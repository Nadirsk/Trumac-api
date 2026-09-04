<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AllowedURLMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // URLs that are always allowed (internal/helper endpoints)
        $alwaysAllowedUrls = [
            'permissions',      // For loading permissions in position form
            'modules',          // For loading modules dropdown
            'roles',            // For loading roles dropdown
            'positions',        // For loading positions dropdown
            'attendances',      // Attendance is allowed for all logged-in users
            'leave-requests',   // Leave requests for all users
            'leave-types',      // Leave types lookup
            'notifications',    // Notifications for all users
            'dashboard',        // Dashboard access
            'profile',          // Profile access
        ];

        // Get the URL from the Request object
        $is_URL_allowed = false;

        $url = $request->url();
        $method = $request->method();
        $url_segment = explode('/', $url);
        // Convert hyphens to underscores so URL segments like "purchase-orders"
        // match module names like "PURCHASE_ORDERS"
        $module = strtoupper(str_replace('-', '_', $url_segment[4] ?? ''));
        $moduleLower = strtolower($url_segment[4] ?? '');
        $loggedInUser = Auth::user();
        $loggedInUserRole = $loggedInUser->roles[0];

        // Check if URL is in always allowed list
        if (in_array($moduleLower, $alwaysAllowedUrls)) {
            return $next($request);
        }

        $permission = "";
        $permissions = [];
        if ($loggedInUserRole['name'] != 'SUPER ADMIN' && $loggedInUserRole['name'] != 'ADMIN') {
            // For roles with positions (USER, IT ADMIN, WAREHOUSE ADMIN, CG ADMIN, FRANCHISE ADMIN)
            // Get permissions from position
            $position = $loggedInUser['position'];
            if ($position && isset($position['permissions'])) {
                $permissions = $position['permissions'];
            } elseif (isset($loggedInUserRole['permissions'])) {
                // Fallback to role permissions if no position permissions
                $permissions = $loggedInUserRole['permissions'];
            }

            // if ($method === 'PATCH') {
            //     return response()->json([
            //         'LoggedInUser' => $loggedInUser,
            //         'module' => $module,
            //         'method' => $method,
            //         'url_segment' => $url_segment,
            //         'size' => sizeof($url_segment),
            //         'permission' => $permission,
            //         'message' => 'Debug',
            //     ], 200);
            // }

            // Determine the last meaningful segment for action detection
            $lastSegment = strtolower($url_segment[sizeof($url_segment) - 1] ?? '');
            // Approval action keywords
            $approveActions = ['approve', 'reject', 'cancel', 'dispatch', 'deliver', 'complete', 'fulfill', 'submit'];

            switch (true) {
                case strpos($module, 'CRUD') !== false:
                    // UPLOAD Data
                    $module = str_replace("CRUD_", "", $module);
                    $permission = 'UPLOAD';
                    break;
                case $method === "GET" && sizeof($url_segment) == 5  && strpos($module, 'CRUD') === false:
                    // List
                    $permission = 'VIEW';
                    break;
                case $method === "GET" && sizeof($url_segment) == 6  && $url_segment[5] != 'masters':
                    // Single ID wise Data
                    $permission = 'SINGLE_VIEW';
                    break;
                case $method  === "GET" && sizeof($url_segment) > 5 && $url_segment[5] == 'masters':
                    // Get Master
                    $permission = 'MASTERS';
                    break;
                case $method  == "POST"  && sizeof($url_segment) == 5:
                    // Post
                    $permission = "CREATE";
                    break;
                case $method === "POST" && sizeof($url_segment) > 5 && in_array($lastSegment, $approveActions):
                    // Approval/workflow actions (approve, reject, cancel, dispatch, deliver, complete, fulfill, submit)
                    $permission = 'APPROVE';
                    break;
                case $method  === "POST" && sizeof($url_segment) > 5 && $lastSegment == 'delete':
                    // Soft Delete
                    $permission = 'DELETE';
                    break;
                case $method  === "POST" && sizeof($url_segment) > 5:
                    // Other POST actions (restore, etc.) - treat as UPDATE
                    $permission = 'UPDATE';
                    break;
                case  $method  === "PATCH":
                    // Update
                    $permission = 'UPDATE';
                    break;

                default:
                    $permission = 'DEFAULT';
                    break;
            }


            // Convert the array to a collection
            $collection = collect($permissions);
            $usersModules = collect([]);

            // Use the filter method to search for items with module name equal to "USERS"
            if (count($permissions) > 0) {
                $usersModules = $collection->filter(function ($item) use ($module, $permission) {
                    return isset($item['module']['name']) && $item['module']['name'] === $module && $item['name'] === $permission;
                });
            }
            if ($usersModules->count() > 0) {
                $is_URL_allowed = true;
            }
            // Check if the current URL is in the allowed URLs array

            if ($is_URL_allowed != false) {
                return $next($request);
            } else {
                return response()->json([
                    'permissions' => $permissions,
                    'usersModules' => $usersModules,
                    'module' => $module,
                    'method' => $method,
                    'permission' => $permission,
                    'url_segment' => $url_segment,
                    'is_upload' => strpos($module, 'CRUD'),
                    'message' => 'URL Not Allowed',
                ], 403);
            }
        } else {
            return $next($request);
        }
    }
}
