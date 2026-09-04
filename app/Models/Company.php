<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Company;

    protected $fillable = [
        "is_active",
        "is_deleted",
        "name",
        "email",
        "phone",
        "admin_name",
        "address",
        "city_id",
        "state_id",
        "pincode",
        "about_us",
        "terms_conditions",
        "privacy_policy",
        "faqs",
        "disclaimer",
        "logo_path",
        "url",
    ];

    // User
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->where('is_active', 1)
            ->where('deleted_at', false)
            ->with(['roles', 'companies', 'position', 'referred_user']);
    }

    public function allUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->where('deleted_at', false)
            ->with(['roles', 'companies', 'position', 'referred_user']);
    }

    public function totalUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->with(['roles', 'companies', 'position', 'referred_user']);
    }

    public function deletedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->where('deleted_at', true)
            ->with(['roles', 'companies', 'position', 'referred_user']);
    }

    // Position
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allPositions(): HasMany
    {
        return $this->hasMany(Position::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalPositions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
    public function deletedPositions(): HasMany
    {
        return $this->hasMany(Position::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Module
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allModules(): HasMany
    {
        return $this->hasMany(Module::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalModules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
    public function deletedModules(): HasMany
    {
        return $this->hasMany(Module::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Permission
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class)->with('module')
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allPermissions(): HasMany
    {
        return $this->hasMany(Permission::class)->with('module')
            ->where('is_deleted', '=', FALSE);
    }
    public function totalPermissions(): HasMany
    {
        return $this->hasMany(Permission::class)->with('module');
    }
    public function deletedPermissions(): HasMany
    {
        return $this->hasMany(Permission::class)->with('module')
            ->where('is_deleted', '=', TRUE);
    }

    // Value
    public function values(): HasMany
    {
        return $this->hasMany(Value::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allValues(): HasMany
    {
        return $this->hasMany(Value::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalValues(): HasMany
    {
        return $this->hasMany(Value::class);
    }
    public function deletedValues(): HasMany
    {
        return $this->hasMany(Value::class)
            ->where('is_deleted', '=', TRUE);
    }

    public function user_timestamps(): HasMany
    {
        return $this->hasMany(UserTimestamp::class);
    }

    // DriverDocument
    public function driver_documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE)
            ->with('doc_type', 'user');
    }
    public function allDriverDocuments(): HasMany
    {
        return $this->hasMany(DriverDocument::class)
            ->where('is_deleted', '=', FALSE)
            ->with('doc_type', 'user');
    }
    public function totalDriverDocuments(): HasMany
    {
        return $this->hasMany(DriverDocument::class)
            ->with('doc_type', 'user');
    }
    public function deletedDriverDocuments(): HasMany
    {
        return $this->hasMany(DriverDocument::class)
            ->where('is_deleted', '=', TRUE)
            ->with('doc_type', 'user');
    }
    // Clinic
    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class)
            ->where('is_active', '=', 1)
            ->where('deleted_at', '=', NULL);
    }
    public function allClinics(): HasMany
    {
        return $this->hasMany(Clinic::class)
            ->where('deleted_at', '=', NULL);
    }
    public function totalClinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }
    public function deletedClinics(): HasMany
    {
        return $this->hasMany(Clinic::class)
             ->where('deleted_at', '!=', NULL);
    }
    // Lab
    public function labs(): HasMany
    {
        return $this->hasMany(Lab::class)
            ->where('is_active', '=', 1)
            ->where('deleted_at', '=', NULL);
    }
    public function allLabs(): HasMany
    {
        return $this->hasMany(Lab::class)
            ->where('deleted_at', '=', NULL);
    }
    public function totalLabs(): HasMany
    {
        return $this->hasMany(Lab::class);
    }
    public function deletedLabs(): HasMany
    {
        return $this->hasMany(Lab::class)
             ->where('deleted_at', '!=', NULL);
    }
    // LabTest
    public function lab_tests(): HasMany
    {
        return $this->hasMany(LabTest::class)
            ->where('is_active', '=', 1)
            ->where('deleted_at', '=', NULL);
    }
    public function allLabTests(): HasMany
    {
        return $this->hasMany(LabTest::class)
            ->where('deleted_at', '=', NULL);
    }
    public function totalLabTests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }
    public function deletedLabTests(): HasMany
    {
        return $this->hasMany(LabTest::class)
             ->where('deleted_at', '!=', NULL);
    }

    // SkuCategory
    public function sku_categories(): HasMany
    {
        return $this->hasMany(SkuCategory::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allSkuCategories(): HasMany
    {
        return $this->hasMany(SkuCategory::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalSkuCategories(): HasMany
    {
        return $this->hasMany(SkuCategory::class);
    }
    public function deletedSkuCategories(): HasMany
    {
        return $this->hasMany(SkuCategory::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Sku
    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allSkus(): HasMany
    {
        return $this->hasMany(Sku::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalSkus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }
    public function deletedSkus(): HasMany
    {
        return $this->hasMany(Sku::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Location
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allLocations(): HasMany
    {
        return $this->hasMany(Location::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalLocations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
    public function deletedLocations(): HasMany
    {
        return $this->hasMany(Location::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Warehouse
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
    public function deletedWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class)
            ->where('is_deleted', '=', TRUE);
    }

    // CompanyGodown
    public function companyGodowns(): HasMany
    {
        return $this->hasMany(CompanyGodown::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allCompanyGodowns(): HasMany
    {
        return $this->hasMany(CompanyGodown::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalCompanyGodowns(): HasMany
    {
        return $this->hasMany(CompanyGodown::class);
    }
    public function deletedCompanyGodowns(): HasMany
    {
        return $this->hasMany(CompanyGodown::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Franchise
    public function franchises(): HasMany
    {
        return $this->hasMany(Franchise::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allFranchises(): HasMany
    {
        return $this->hasMany(Franchise::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalFranchises(): HasMany
    {
        return $this->hasMany(Franchise::class);
    }
    public function deletedFranchises(): HasMany
    {
        return $this->hasMany(Franchise::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Retailer
    public function retailers(): HasMany
    {
        return $this->hasMany(Retailer::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allRetailers(): HasMany
    {
        return $this->hasMany(Retailer::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalRetailers(): HasMany
    {
        return $this->hasMany(Retailer::class);
    }
    public function deletedRetailers(): HasMany
    {
        return $this->hasMany(Retailer::class)
            ->where('is_deleted', '=', TRUE);
    }

    // Vendor
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allVendors(): HasMany
    {
        return $this->hasMany(Vendor::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalVendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
    public function deletedVendors(): HasMany
    {
        return $this->hasMany(Vendor::class)
            ->where('is_deleted', '=', TRUE);
    }

    // RetailerVisit
    public function retailerVisits(): HasMany
    {
        return $this->hasMany(RetailerVisit::class)
            ->where('is_active', '=', 1)
            ->where('is_deleted', '=', FALSE);
    }
    public function allRetailerVisits(): HasMany
    {
        return $this->hasMany(RetailerVisit::class)
            ->where('is_deleted', '=', FALSE);
    }
    public function totalRetailerVisits(): HasMany
    {
        return $this->hasMany(RetailerVisit::class);
    }
    public function deletedRetailerVisits(): HasMany
    {
        return $this->hasMany(RetailerVisit::class)
            ->where('is_deleted', '=', TRUE);
    }
}
