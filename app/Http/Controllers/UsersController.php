<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'destroy', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"User"},
     *     summary="Get all Users",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function index_old(Request $request)
    {
        $count = 0;

        $users = $request->boolean('show_deleted') ? $request->company->deletedUsers() : $request->company->allUsers();

        $users = $users->with(['roles'])
            ->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Admin');
            });
        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $users->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('middle_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('user_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('position_id')) {
            $users->where('position_id', $request->position_id);
        }

        if ($request->filled('is_active')) {
            $users->where('is_active', $request->is_active);
        }

        $users->latest();
        $count = $users->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $users = $users->paginate($request->rowsPerPage)->items();
        } else {
            $users = $users->get();
        }

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Fetched Successfully',
            'success' => true,
            'count' => $count,
            'data' => $users,
        ], 200);
    }
    public function index(Request $request)
    {
        try {
            $query = User::query();
            $loggedInUser = Auth::user();

            // Exclude the logged-in user from the list
            $query->where('id', '!=', $loggedInUser->id);

            // Filter by role_id through position relationship
            // This allows admins to see only users under their team (not admins themselves)
            if ($request->filled('role_id')) {
                $query->whereHas('position', function ($q) use ($request) {
                    $q->where('role_id', $request->role_id);
                });

                // Also exclude users who have admin positions (positions with same role_id but are admin positions)
                // Only show team members, not other admins
                $query->whereHas('position', function ($q) {
                    // Exclude admin-level positions by checking position name patterns
                    $q->where('name', 'not like', '%Admin%')
                        ->where('name', 'not like', '%ADMIN%');
                });
            }

            $query = Utility::prepareSearchQuery($query, $request, new User());
            $query->orderBy('id', 'desc');
            $users = Utility::getSearchRequestQueryResults($request, $query);
            $count = $users->count();
            return response()->json([
                'title' => 'User',
                'sub-title' => 'User Data Fetched Successfully',
                'success' => true,
                'count' => $count,
                'data' => $users,
            ], 200);
        } catch (Exception $th) {
            //throw $th;
            throw ValidationException::withMessages(['error' => $th->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"User"},
     *     summary="Create a new Users",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'position_id' => 'required|exists:positions,id',
            'first_name' => 'required|max:100',
            // 'last_name'          => 'required|max:100',
            'email' => 'required|email|max:100|unique:users,email,NULL,id,deleted_at,NULL',
            'phone' => 'required',
            'password' => 'required|min:6',
        ]);
        if (empty($request->id)) {
            $data = $request->all();
            $data['soft_password'] = $data['password'];
            $data['password'] = bcrypt($data['password']);

            // Split first_name into first_name and last_name if last_name is empty
            // Last word goes to last_name, rest stays in first_name
            if (empty($data['last_name']) && !empty($data['first_name'])) {
                $nameParts = explode(' ', trim($data['first_name']));
                if (count($nameParts) > 1) {
                    $data['last_name'] = array_pop($nameParts);
                    $data['first_name'] = implode(' ', $nameParts);
                }
            }

            if ($request->user_name == null) {
                $data['user_name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            }

            // Auto-map new user to the same warehouse/CG/franchise as the logged-in admin
            $loggedInUser = $request->user();
            $loggedInRoleId = $loggedInUser->position?->role_id;

            if ($loggedInRoleId === 5 && $loggedInUser->warehouse_id) {
                $data['warehouse_id'] = $loggedInUser->warehouse_id;
            } elseif ($loggedInRoleId === 6 && $loggedInUser->company_godown_id) {
                $data['company_godown_id'] = $loggedInUser->company_godown_id;
            } elseif ($loggedInRoleId === 7 && $loggedInUser->franchise_id) {
                $data['franchise_id'] = $loggedInUser->franchise_id;
            }

            $user = new User($data);
            $user->referral_code = $user->generateReferralCode();
            $user->save();

            $user->assignRole($request->role_id);
            $user->assignCompany($request->company->id);

            // Notify the new user about account creation
            $creatorName = $request->user()->first_name . ' ' . $request->user()->last_name;
            Notification::send(
                $user->id,
                'Welcome! Your account has been created',
                'Your account has been created by ' . $creatorName . '. You can now login with your credentials.',
                'user_management',
                ['action' => 'account_created', 'created_by' => $request->user()->id],
                $request->company->id
            );
        } else {
            $user = User::findOrFail($request->id);
            $user->update($request->all());
        }
        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Stored Successfully',
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"User"},
     *     summary="Get a User by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    /**
     * @OA\Get(
     *     path="/api/users/{user}",
     *     tags={"User"},
     *     summary="Get all {user}",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $user = User::with(['roles', 'position', 'companies'])
            ->where('id', $decryptedID)
            ->where('deleted_at', null)
            ->first();

        if (!$user) {
            return response()->json([
                'title' => 'User',
                'sub-title' => 'User Not Found',
                'success' => false,
                'message' => 'User data not found or is deleted.',
            ], 404);
        }

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Fetched Successfully',
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/users/{id}",
     *     tags={"User"},
     *     summary="Update a User by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    /**
     * @OA\Put(
     *     path="/api/users/{user}",
     *     tags={"User"},
     *     summary="Update a {user} by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $user = User::where('id', $decryptedID)->first();

        // Check if this is a password-only update
        $isPasswordOnlyUpdate = $request->has('password') && !$request->has('first_name') && !$request->has('email');

        if ($isPasswordOnlyUpdate) {
            // Password reset - only validate password
            $request->validate([
                'password' => 'required|min:6',
            ]);

            $user->update([
                'soft_password' => $request->password,
                'password' => bcrypt($request->password),
            ]);

            // Notify user about password reset
            $resetByName = $request->user()->first_name . ' ' . $request->user()->last_name;
            Notification::send(
                $user->id,
                'Your password has been reset',
                'Your password was reset by ' . $resetByName . '. Please login with your new credentials.',
                'user_management',
                ['action' => 'password_reset', 'reset_by' => $request->user()->id],
                $request->company->id
            );

            return response()->json([
                'title' => 'User',
                'sub-title' => 'Password Updated Successfully',
                'success' => true,
                'data' => $user,
            ], 200);
        }

        // Full update - validate all required fields
        // Check if role is ADMIN or SUPER ADMIN (position_id not required for these roles)
        $isAdminRole = false;
        if ($request->role_id) {
            $role = \App\Models\Role::find($request->role_id);
            $isAdminRole = $role && in_array($role->name, ['ADMIN', 'SUPER ADMIN']);
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'position_id' => $isAdminRole ? 'nullable|exists:positions,id' : 'required|exists:positions,id',
            'first_name' => 'required|max:100',
            // 'last_name'     => 'required|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required',
        ]);

        $data = $request->all();
        $data['soft_password'] = $data['password'] ?? null;
        $data['password'] = isset($data['password']) ? bcrypt($data['password']) : null;
        // Split first_name into first_name and last_name if last_name is empty
        // Last word goes to last_name, rest stays in first_name
        if (empty($data['last_name']) && !empty($data['first_name'])) {
            $nameParts = explode(' ', trim($data['first_name']));
            if (count($nameParts) > 1) {
                $data['last_name'] = array_pop($nameParts);
                $data['first_name'] = implode(' ', $nameParts);
            }
        }

        // Remove role_id from data array as it's handled separately
        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        // Update user data
        $user->update($data);

        // Update role if provided
        if ($roleId) {
            // Detach all existing roles and assign new role
            $user->roles()->detach();
            $user->assignRole($roleId);
        }

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Updated Successfully',
            'success' => true,
            'data' => $user->fresh(['roles', 'companies', 'position']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users/delete/{id}",
     *     tags={"User"},
     *     summary="Create a new Delete",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $id = $request->id;
        $user = User::find($id)->update(['deleted_at' => now()]);

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Restored Successfully',
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users/restore/{id}",
     *     tags={"User"},
     *     summary="Create a new Restore",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function restore(Request $request)
    {
        $id = $request->id;
        $user = User::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Restored Successfully',
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"User"},
     *     summary="Hard delete a User by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        User::find($id)->delete();

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Data Deleted Successfully',
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }
}








