<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Core Setup
        $this->call(CompanySeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(ModuleSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(PermissionSeeder::class);

        // Locations & Facilities (must run BEFORE UserSeeder for foreign keys)
        $this->call(LocationSeeder::class);
        // $this->call(WarehouseSeeder::class);
        // $this->call(CompanyGodownSeeder::class);
        // $this->call(VendorSeeder::class);
        // $this->call(FranchiseSeeder::class);

        // Users (depends on company_godown_id and franchise_id)
        $this->call(UserSeeder::class);

        // Configuration
        // $this->call(ValueListSeeder::class);
        $this->call(LeaveTypeSeeder::class);
        $this->call(VersionSeeder::class);

        // SKU & Products
        // $this->call(SkuCategorySeeder::class);
        // $this->call(SkuSeeder::class);
        // $this->call(SkuImageSeeder::class);

        // Retailers
        // $this->call(RetailerSeeder::class);
        // $this->call(RetailerRatingSeeder::class);

        // HR & Attendance
        // $this->call(AttendanceSeeder::class);
        // $this->call(LeaveRequestSeeder::class);
        // $this->call(RegularisationRequestSeeder::class);

        // Notifications
        // $this->call(NotificationSeeder::class);

        // PJP (Planned Journey Plan)
        // $this->call(JourneyPlanSeeder::class);
        // $this->call(PjpSeeder::class);
        // $this->call(PjpRetailerSeeder::class);
        // $this->call(PjpChangeSeeder::class);

        // Sales
        // $this->call(SalesOrderSeeder::class);
        // $this->call(SalesOrderItemSeeder::class);
        // $this->call(CashCollectionSeeder::class);
        // $this->call(RetailerLedgerSeeder::class);

        // Inventory
        // $this->call(InventorySeeder::class);
        // $this->call(InventoryMovementSeeder::class);

        // Purchase
        // $this->call(PurchaseOrderSeeder::class);
        // $this->call(PurchaseOrderItemSeeder::class);
        // $this->call(GrnSeeder::class);
        // $this->call(GrnItemSeeder::class);

        // Requisitions
        // $this->call(RequisitionSeeder::class);
        // $this->call(RequisitionItemSeeder::class);

        // Questionnaires
        $this->call(QuestionnaireSeeder::class);
        $this->call(QuestionSeeder::class);
        $this->call(QuestionnaireResponseSeeder::class);
        $this->call(QuestionResponseSeeder::class);
    }
}
