<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
    }

    /**
     * Return fresh Sanctum tokens for all active users in the company,
     * grouped by position (one per position type for the testing dashboard).
     *
     * Only available in local/development environment.
     */
    public function getTestTokens(Request $request)
    {
        if (!app()->isLocal()) {
            return response()->json(['error' => 'Only available in local environment'], 403);
        }

        $targetPositions = [
            'IT Admin',
            'IT Team Leader',
            'IT Employee',
            'IT Driver',
            'IT Purchase',
            'Warehouse Admin',
            'Warehouse Billing',
            'Warehouse Driver',
            'CG Admin',
            'CG Billing',
            'CG Driver',
            'Franchise Admin',
            'Franchise Billing',
            'Franchise Driver',
            'Retailer',
        ];

        // One representative user per position from this company.
        // Use totalUsers() (no deleted_at filter) + explicit whereNull to avoid
        // the allUsers() bug where .where('deleted_at', false) matches nothing.
        $users = collect();
        foreach ($targetPositions as $positionName) {
            $user = $request->company->totalUsers()
                ->whereHas('position', fn($q) => $q->where('name', $positionName))
                ->whereNull('users.deleted_at')
                ->where('users.is_active', true)
                ->first();

            if ($user) {
                $users->push($user);
            }
        }

        $result = $users->map(function ($user) {
            // Revoke old testing tokens to avoid pile-up
            $user->tokens()->where('name', 'like', 'testing-%')->delete();

            $token = $user->createToken('testing-' . $user->id)->plainTextToken;

            return [
                'user_id'           => $user->id,
                'name'              => trim("{$user->first_name} {$user->last_name}"),
                'email'             => $user->email,
                'position'          => $user->position?->name,
                'role'              => $user->roles?->first()?->name,
                'role_id'           => $user->position?->role_id,
                'warehouse_id'      => $user->warehouse_id,
                'company_godown_id' => $user->company_godown_id,
                'franchise_id'      => $user->franchise_id,
                'token'             => $token,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $result->values(),
        ]);
    }
}
