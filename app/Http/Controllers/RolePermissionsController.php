<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'permission_id'  =>  'required',
            'role_id'        =>  'required'
        ]);

        $role =  Role::find($request->role_id);
        $permission =  Permission::find($request->permission_id);
        if ($request->op == 'assign') {
            $role->assignPermission($permission->id);
        }
        if ($request->op == 'unassign') {
            $role->unassignPermission($permission->id);
        }
        
        $permissionRole = Role::with('permissions')->find($request->role_id);

        return response()->json([
            'data'    =>  $permissionRole
        ], 200);
    }
}
