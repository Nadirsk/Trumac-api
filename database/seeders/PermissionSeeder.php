<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($companyId = 1): void
    {
        $company = Company::find($companyId);
        if (!$company) {
            $this->command->error("Company with ID $companyId not found.");
            return;
        }

        // Use dynamic module lookup instead of hardcoded IDs
        $allModules = \App\Models\Module::where('company_id', $companyId)->get();
        $modules = [];
        foreach ($allModules as $mod) {
            $modules[$mod->id] = $mod->name;
        }

        $permissions = ['VIEW', 'SINGLE_VIEW', 'CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'MASTERS', 'EXPORT', 'IMPORT'];

        foreach ($modules as $moduleId => $moduleName) {
            foreach ($permissions as $permissionName) {
                $existingPermission = $company->permissions()
                    ->where('module_id', $moduleId)
                    ->where('name', $permissionName)
                    ->exists();

                if (!$existingPermission) {
                    $newPermission = new Permission([
                        'module_id' => $moduleId,
                        'name' => $permissionName,
                    ]);
                    $savedPermission = $company->permissions()->save($newPermission);

                    if ($moduleId === 2 && in_array($permissionName, ['SINGLE_VIEW', 'UPDATE'])) {
                        $company->positions()->each(
                            fn($position) =>
                            $savedPermission->position_permissions()->create(['position_id' => $position->id])
                        );
                    }
                }
            }
        }
    }
}
