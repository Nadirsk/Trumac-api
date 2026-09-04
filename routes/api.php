<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestingController;
use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\MastersController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\PositionsController;
use App\Http\Controllers\SkuCategoriesController;
use App\Http\Controllers\SkusController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\WarehousesController;
use App\Http\Controllers\CompanyGodownsController;
use App\Http\Controllers\FranchisesController;
use App\Http\Controllers\RetailersController;
use App\Http\Controllers\RetailerVisitsController;
use App\Http\Controllers\VendorsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendancesController;
use App\Http\Controllers\LeaveTypesController;
use App\Http\Controllers\LeaveRequestsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PjpsController;
use App\Http\Controllers\PjpChangesController;
use App\Http\Controllers\SalesOrdersController;
use App\Http\Controllers\CashCollectionsController;
use App\Http\Controllers\JourneyPlansController;
use App\Http\Controllers\InventoriesController;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\GrnsController;
use App\Http\Controllers\RequisitionsController;
use App\Http\Controllers\ChallansController;
use App\Http\Controllers\QuestionnairesController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\PurchaseInvoicesController;
use App\Http\Controllers\PaymentsReceivedController;
use App\Http\Controllers\UploadsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UserTimestampsController;
use App\Http\Controllers\ValueListsController;
use App\Http\Controllers\ValuesController;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });   

// Authentication
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot_password', [AuthController::class, 'forgotPassword']);
Route::get('me', [AuthController::class, 'me']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('update-fcm-token', [AuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');

Route::get('roles', function () {
    return Role::all();
});
  
// Master
Route::get('masters', [MastersController::class, 'masters']);

// UserTimestamp 
Route::resource('user_timestamps', UserTimestampsController::class);

// User  
Route::post('users/upload_image_path', [UploadsController::class, 'uploadUserImage']);
Route::get('users', [UsersController::class, 'index']); // Fetch all users
Route::post('users', [UsersController::class, 'store']); // Create user
Route::get('users/{id}', [UsersController::class, 'show']); // Get single user
Route::patch('users/{id}', [UsersController::class, 'update']); // Update user
Route::delete('users/{id}', [UsersController::class, 'destroy']); // Delete user
Route::post('users/delete/{id}', [UsersController::class, 'clear']); // Soft Delete user
Route::post('users/restore/{id}', [UsersController::class, 'restore']); // Restore user
Route::resource('users', UsersController::class); // User CRUD (using resource routes to handle basic CRUD operations) 

// Company
Route::post('companies/{id}/logo', [UploadsController::class, 'uploadCompanyLogoPath']);
Route::get('companies/{id}', [CompaniesController::class, 'show']);
Route::get('get_company/{id}', [CompaniesController::class, 'simpleShow']);
Route::patch('companies/{id}', [CompaniesController::class, 'update']);
Route::post('companies/delete/{id}', [CompaniesController::class, 'clear']);
Route::post('companies/restore/{id}', [CompaniesController::class, 'restore']);
// Route::resource('companies', CompaniesController::class)->except(['create', 'edit']);
Route::resource('companies', CompaniesController::class);



// Module
Route::get('modules/{id}', [ModulesController::class, 'show']);
Route::patch('modules/{id}', [ModulesController::class, 'update']);
Route::post('modules/delete/{id}', [ModulesController::class, 'clear']);
Route::post('modules/restore/{id}', [ModulesController::class, 'restore']);
Route::resource('modules', ModulesController::class);

// Permission
Route::get('permissions/{id}', [PermissionsController::class, 'show']);
Route::patch('permissions/{id}', [PermissionsController::class, 'update']);
Route::post('permissions/delete/{id}', [PermissionsController::class, 'clear']);
Route::post('permissions/restore/{id}', [PermissionsController::class, 'restore']);
Route::resource('permissions', PermissionsController::class);

// Position
Route::get('positions/{id}', [PositionsController::class, 'show']);
Route::patch('positions/{id}', [PositionsController::class, 'update']);
Route::post('positions/delete/{id}', [PositionsController::class, 'clear']);
Route::delete('positions/{id}', [PositionsController::class, 'destroy']);
Route::post('positions/restore/{id}', [PositionsController::class, 'restore']);
Route::resource('positions', PositionsController::class);

// Value
Route::get('values/{id}', [ValuesController::class, 'show']);
Route::patch('values/{id}', [ValuesController::class, 'update']);
Route::post('values/delete/{id}', [ValuesController::class, 'clear']);
Route::post('values/restore/{id}', [ValuesController::class, 'restore']);
Route::delete('values/{id}', [ValuesController::class, 'destroy']);
Route::resource('values', ValuesController::class);

// Value List
Route::get('values/{value}/value_lists/{id}', [ValueListsController::class, 'show']);
Route::patch('values/{value}/value_lists/{id}', [ValueListsController::class, 'update']);
Route::post('values/{value}/value_lists/delete/{id}', [ValueListsController::class, 'clear']);
Route::post('values/{value}/value_lists/restore/{id}', [ValueListsController::class, 'restore']);
Route::post('values/{value}/multiple_value_lists', [ValueListsController::class, 'storeMultiple']);
Route::resource('values/{value}/value_lists', ValueListsController::class);

// =============================================
// TRUMAC DISTRIBUTION SYSTEM ROUTES
// =============================================

// SKU Categories
Route::post('sku_categories/upload_image', [UploadsController::class, 'uploadSkuCategoryImage']);
Route::get('sku_categories/tree', [SkuCategoriesController::class, 'tree']);
Route::get('sku_categories/{id}', [SkuCategoriesController::class, 'show']);
Route::patch('sku_categories/{id}', [SkuCategoriesController::class, 'update']);
Route::post('sku_categories/delete/{id}', [SkuCategoriesController::class, 'clear']);
Route::post('sku_categories/restore/{id}', [SkuCategoriesController::class, 'restore']);
Route::resource('sku_categories', SkuCategoriesController::class);

// SKUs
Route::post('skus/upload_image', [UploadsController::class, 'uploadSkuImage']);
Route::post('skus/import', [SkusController::class, 'import']);
Route::get('skus/sample-file', [SkusController::class, 'downloadSampleFile']);
Route::get('skus/{id}', [SkusController::class, 'show']);
Route::patch('skus/{id}', [SkusController::class, 'update']);
Route::post('skus/delete/{id}', [SkusController::class, 'clear']);
Route::post('skus/restore/{id}', [SkusController::class, 'restore']);
Route::post('skus/{id}/images', [SkusController::class, 'addImage']);
Route::delete('skus/{sku_id}/images/{id}', [SkusController::class, 'deleteImage']);
Route::patch('skus/{sku_id}/images/{id}/primary', [SkusController::class, 'setPrimaryImage']);
Route::resource('skus', SkusController::class);

// Locations
Route::get('locations/cities', [LocationsController::class, 'cities']);
Route::get('locations/states', [LocationsController::class, 'states']);
Route::get('locations/{id}', [LocationsController::class, 'show']);
Route::patch('locations/{id}', [LocationsController::class, 'update']);
Route::post('locations/delete/{id}', [LocationsController::class, 'clear']);
Route::post('locations/restore/{id}', [LocationsController::class, 'restore']);
Route::resource('locations', LocationsController::class);

// Warehouses
Route::get('warehouses/{id}', [WarehousesController::class, 'show']);
Route::patch('warehouses/{id}', [WarehousesController::class, 'update']);
Route::post('warehouses/delete/{id}', [WarehousesController::class, 'clear']);
Route::post('warehouses/restore/{id}', [WarehousesController::class, 'restore']);
Route::resource('warehouses', WarehousesController::class);

// Company Godowns
Route::get('company_godowns/{id}', [CompanyGodownsController::class, 'show']);
Route::patch('company_godowns/{id}', [CompanyGodownsController::class, 'update']);
Route::post('company_godowns/delete/{id}', [CompanyGodownsController::class, 'clear']);
Route::post('company_godowns/restore/{id}', [CompanyGodownsController::class, 'restore']);
Route::resource('company_godowns', CompanyGodownsController::class);

// Franchises
Route::get('franchises/{id}', [FranchisesController::class, 'show']);
Route::patch('franchises/{id}', [FranchisesController::class, 'update']);
Route::post('franchises/delete/{id}', [FranchisesController::class, 'clear']);
Route::post('franchises/restore/{id}', [FranchisesController::class, 'restore']);
Route::resource('franchises', FranchisesController::class);

// Retailers
Route::get('retailers/flagged', [RetailersController::class, 'flagged']);
Route::get('retailers/{id}', [RetailersController::class, 'show']);
Route::patch('retailers/{id}', [RetailersController::class, 'update']);
Route::post('retailers/delete/{id}', [RetailersController::class, 'clear']);
Route::post('retailers/restore/{id}', [RetailersController::class, 'restore']);
Route::post('retailers/{id}/toggle-flag', [RetailersController::class, 'toggleFlag']);
Route::post('retailers/{id}/create-login', [RetailersController::class, 'createLogin']);
Route::get('retailers/{id}/rating', [RetailersController::class, 'getRating']);
Route::post('retailers/{id}/rating', [RetailersController::class, 'addRating']);
Route::resource('retailers', RetailersController::class);

// Retailer Visits
Route::get('retailer-visits/my-today', [RetailerVisitsController::class, 'myTodayVisits']);
Route::post('retailer-visits/{id}/check-out', [RetailerVisitsController::class, 'checkOut']);
Route::get('retailer-visits/{id}', [RetailerVisitsController::class, 'show']);
Route::resource('retailer-visits', RetailerVisitsController::class);

// Vendors
Route::get('vendors/{id}', [VendorsController::class, 'show']);
Route::patch('vendors/{id}', [VendorsController::class, 'update']);
Route::post('vendors/delete/{id}', [VendorsController::class, 'clear']);
Route::post('vendors/restore/{id}', [VendorsController::class, 'restore']);
Route::resource('vendors', VendorsController::class);

// Reports
Route::get('reports/summary', [ReportsController::class, 'summary']);
Route::get('reports/retailers-by-franchise', [ReportsController::class, 'retailersByFranchise']);
Route::get('reports/retailers-by-location', [ReportsController::class, 'retailersByLocation']);
Route::get('reports/skus-by-category', [ReportsController::class, 'skusByCategory']);
Route::get('reports/retailer-ratings', [ReportsController::class, 'retailerRatings']);
Route::get('reports/users-by-role', [ReportsController::class, 'usersByRole']);
Route::get('reports/warehouses-overview', [ReportsController::class, 'warehousesOverview']);
Route::get('reports/entity-growth', [ReportsController::class, 'entityGrowth']);
Route::get('reports/export', [ReportsController::class, 'export']);
Route::get('reports/attendance-summary', [ReportsController::class, 'attendanceSummary']);
Route::get('reports/leave-report', [ReportsController::class, 'leaveReport']);
Route::get('reports/inventory-summary', [ReportsController::class, 'inventorySummary']);
Route::get('reports/purchase-orders-report', [ReportsController::class, 'purchaseOrdersReport']);
Route::get('reports/sales-orders-report', [ReportsController::class, 'salesOrdersReport']);
Route::get('reports/requisitions-report', [ReportsController::class, 'requisitionsReport']);
Route::get('reports/cash-collections-report', [ReportsController::class, 'cashCollectionsReport']);
Route::get('reports/grn-report', [ReportsController::class, 'grnReport']);
Route::get('reports/inventory-movements', [ReportsController::class, 'inventoryMovements']);
Route::get('reports/daily-summary', [ReportsController::class, 'dailySummary']);
Route::get('reports/employee-performance', [ReportsController::class, 'employeePerformance']);
Route::get('reports/pjp-completion', [ReportsController::class, 'pjpCompletion']);
Route::get('reports/retailer-performance', [ReportsController::class, 'retailerPerformance']);
Route::get('reports/my-attendance', [ReportsController::class, 'myAttendance']);
Route::get('reports/my-sales', [ReportsController::class, 'mySales']);
Route::get('reports/my-collections', [ReportsController::class, 'myCollections']);
Route::get('reports/journey-summary', [ReportsController::class, 'journeySummary']);

// Dashboard
Route::get('dashboard', [DashboardController::class, 'index']);
Route::get('dashboard/quick-stats', [DashboardController::class, 'quickStats']);
Route::get('dashboard/alerts', [DashboardController::class, 'alerts']);

// Attendances
Route::get('attendances/today', [AttendancesController::class, 'today']);
Route::get('attendances/history', [AttendancesController::class, 'history']);
Route::get('attendances/summary', [AttendancesController::class, 'summary']);
Route::get('attendances/team', [AttendancesController::class, 'team']);
Route::post('attendances/punch-in', [AttendancesController::class, 'punchIn']);
Route::post('attendances/punch-out', [AttendancesController::class, 'punchOut']);
Route::get('attendances/{id}', [AttendancesController::class, 'show']);
Route::get('attendances', [AttendancesController::class, 'index']);

// Leave Types
Route::get('leave-types/{id}', [LeaveTypesController::class, 'show']);
Route::patch('leave-types/{id}', [LeaveTypesController::class, 'update']);
Route::post('leave-types/delete/{id}', [LeaveTypesController::class, 'clear']);
Route::post('leave-types/restore/{id}', [LeaveTypesController::class, 'restore']);
Route::resource('leave-types', LeaveTypesController::class);

// Leave Requests
Route::get('leave-requests/my-requests', [LeaveRequestsController::class, 'myRequests']);
Route::get('leave-requests/pending-approvals', [LeaveRequestsController::class, 'pendingApprovals']);
Route::get('leave-requests/processed-approvals', [LeaveRequestsController::class, 'processedApprovals']);
Route::post('leave-requests/{id}/approve', [LeaveRequestsController::class, 'approve']);
Route::post('leave-requests/{id}/reject', [LeaveRequestsController::class, 'reject']);
Route::post('leave-requests/{id}/cancel', [LeaveRequestsController::class, 'cancel']);
Route::get('leave-requests/{id}', [LeaveRequestsController::class, 'show']);
Route::resource('leave-requests', LeaveRequestsController::class);

// Notifications
Route::get('notifications/unread-count', [NotificationsController::class, 'unreadCount']);
Route::get('notifications/recent', [NotificationsController::class, 'recent']);
Route::post('notifications/mark-all-read', [NotificationsController::class, 'markAllAsRead']);
Route::delete('notifications/clear-all', [NotificationsController::class, 'clearAll']);
Route::post('notifications/{id}/read', [NotificationsController::class, 'markAsRead']);
Route::get('notifications/{id}', [NotificationsController::class, 'show']);
Route::delete('notifications/{id}', [NotificationsController::class, 'destroy']);
Route::get('notifications', [NotificationsController::class, 'index']);

// PJPs (Permanent Journey Plans)
Route::get('pjps/days', [PjpsController::class, 'getDays']);
Route::get('pjps/my-pjps', [PjpsController::class, 'myPjps']);
Route::get('pjps/today', [PjpsController::class, 'todayPjp']);
Route::get('pjps/by-date', [PjpsController::class, 'getPjpByDate']);
Route::post('pjps/assign-retailers', [PjpsController::class, 'assignRetailers']);
Route::post('pjps/add-retailer', [PjpsController::class, 'addRetailer']);
Route::post('pjps/remove-retailer', [PjpsController::class, 'removeRetailer']);
Route::post('pjps/reorder-retailers', [PjpsController::class, 'reorderRetailers']);
Route::get('pjps/{id}', [PjpsController::class, 'show']);
Route::patch('pjps/{id}', [PjpsController::class, 'update']);
Route::post('pjps/delete/{id}', [PjpsController::class, 'clear']);
Route::post('pjps/restore/{id}', [PjpsController::class, 'restore']);
Route::delete('pjps/{id}', [PjpsController::class, 'destroy']);
Route::resource('pjps', PjpsController::class);

// PJP Changes (Change Requests)
Route::get('pjp-changes/my-requests', [PjpChangesController::class, 'myRequests']);
Route::get('pjp-changes/pending-approvals', [PjpChangesController::class, 'pendingApprovals']);
Route::post('pjp-changes/{id}/approve', [PjpChangesController::class, 'approve']);
Route::post('pjp-changes/{id}/reject', [PjpChangesController::class, 'reject']);
Route::post('pjp-changes/{id}/cancel', [PjpChangesController::class, 'cancel']);
Route::get('pjp-changes/{id}', [PjpChangesController::class, 'show']);
Route::resource('pjp-changes', PjpChangesController::class);

// Sales Orders
Route::get('sales-orders/my-orders', [SalesOrdersController::class, 'myOrders']);
Route::get('sales-orders/pending-approvals', [SalesOrdersController::class, 'pendingApprovals']);
Route::get('sales-orders/retailer/{retailerId}', [SalesOrdersController::class, 'retailerOrders']);
Route::post('sales-orders/reorder/{orderId}', [SalesOrdersController::class, 'reorder']);
Route::get('sales-orders/{id}/download-pdf', [SalesOrdersController::class, 'downloadPdf']);
Route::post('sales-orders/{id}/approve', [SalesOrdersController::class, 'approve']);
Route::post('sales-orders/{id}/cancel', [SalesOrdersController::class, 'cancel']);
Route::post('sales-orders/{id}/dispatch', [SalesOrdersController::class, 'dispatch']);
Route::post('sales-orders/{id}/deliver', [SalesOrdersController::class, 'deliver']);
Route::post('sales-orders/{id}/confirm-receipt', [SalesOrdersController::class, 'confirmReceipt']);
Route::get('sales-orders/{id}', [SalesOrdersController::class, 'show']);
Route::patch('sales-orders/{id}', [SalesOrdersController::class, 'update']);
Route::post('sales-orders/delete/{id}', [SalesOrdersController::class, 'clear']);
Route::resource('sales-orders', SalesOrdersController::class);

// Invoices
Route::post('invoices/create-from-so/{salesOrderId}', [InvoicesController::class, 'createFromSalesOrder']);
Route::get('invoices/{id}/download-pdf', [InvoicesController::class, 'downloadPdf']);
Route::post('invoices/{id}/mark-sent', [InvoicesController::class, 'markSent']);
Route::post('invoices/{id}/record-payment', [InvoicesController::class, 'recordPayment']);
Route::post('invoices/{id}/cancel', [InvoicesController::class, 'cancel']);
Route::get('invoices/{id}', [InvoicesController::class, 'show']);
Route::resource('invoices', InvoicesController::class);

// Payments Received
Route::get('payments-received/{id}', [PaymentsReceivedController::class, 'show']);
Route::resource('payments-received', PaymentsReceivedController::class);

// Purchase Invoices
Route::get('purchase-invoices/{id}/download-pdf', [PurchaseInvoicesController::class, 'downloadPdf']);
Route::get('purchase-invoices/{id}', [PurchaseInvoicesController::class, 'show']);
Route::resource('purchase-invoices', PurchaseInvoicesController::class)->only(['index']);

// Cash Collections
Route::get('cash-collections/today', [CashCollectionsController::class, 'todayCollections']);
Route::get('cash-collections/my-collections', [CashCollectionsController::class, 'myCollections']);
Route::get('cash-collections/escalated', [CashCollectionsController::class, 'escalated']);
Route::get('cash-collections/summary', [CashCollectionsController::class, 'summary']);
Route::get('cash-collections/retailer/{retailerId}/statement', [CashCollectionsController::class, 'retailerStatement']);
Route::post('cash-collections/{id}/collect', [CashCollectionsController::class, 'collect']);
Route::post('cash-collections/{id}/reschedule', [CashCollectionsController::class, 'reschedule']);
Route::get('cash-collections/{id}', [CashCollectionsController::class, 'show']);
Route::resource('cash-collections', CashCollectionsController::class);

// Journey Plans (IT Driver)
Route::get('journey-plans/today', [JourneyPlansController::class, 'todayJourney']);
Route::get('journey-plans/my-journeys', [JourneyPlansController::class, 'myJourneys']);
Route::get('journey-plans/summary', [JourneyPlansController::class, 'summary']);
Route::post('journey-plans/{id}/start', [JourneyPlansController::class, 'start']);
Route::post('journey-plans/{id}/complete', [JourneyPlansController::class, 'complete']);
Route::post('journey-plans/{id}/cancel', [JourneyPlansController::class, 'cancel']);
Route::post('journey-plans/{id}/add-stop', [JourneyPlansController::class, 'addStop']);
Route::post('journey-plans/{id}/reorder-stops', [JourneyPlansController::class, 'reorderStops']);
Route::post('journey-plans/{id}/stops/{stopId}/arrive', [JourneyPlansController::class, 'arriveStop']);
Route::post('journey-plans/{id}/stops/{stopId}/complete', [JourneyPlansController::class, 'completeStop']);
Route::post('journey-plans/{id}/stops/{stopId}/skip', [JourneyPlansController::class, 'skipStop']);
Route::get('journey-plans/{id}', [JourneyPlansController::class, 'show']);
Route::patch('journey-plans/{id}', [JourneyPlansController::class, 'update']);
Route::resource('journey-plans', JourneyPlansController::class);

// Inventory Management (IT Purchase)
Route::get('inventories/low-stock', [InventoriesController::class, 'lowStock']);
Route::get('inventories/out-of-stock', [InventoriesController::class, 'outOfStock']);
Route::get('inventories/movements', [InventoriesController::class, 'movements']);
Route::get('inventories/summary', [InventoriesController::class, 'summary']);
Route::get('inventories/location/{locationType}/{locationId}', [InventoriesController::class, 'atLocation']);
Route::get('inventories/sku/{skuId}/stock', [InventoriesController::class, 'skuStock']);
Route::post('inventories/{id}/adjust', [InventoriesController::class, 'adjust']);
Route::get('inventories/{id}', [InventoriesController::class, 'show']);
Route::patch('inventories/{id}', [InventoriesController::class, 'update']);
Route::get('inventories', [InventoriesController::class, 'index']);

// Purchase Orders (IT Purchase)
Route::get('purchase-orders/my-orders', [PurchaseOrdersController::class, 'myOrders']);
Route::get('purchase-orders/pending-approvals', [PurchaseOrdersController::class, 'pendingApprovals']);
Route::get('purchase-orders/pending-receipt', [PurchaseOrdersController::class, 'pendingReceipt']);
Route::get('purchase-orders/{id}/download-pdf', [PurchaseOrdersController::class, 'downloadPdf']);
Route::post('purchase-orders/{id}/submit', [PurchaseOrdersController::class, 'submit']);
Route::post('purchase-orders/{id}/approve', [PurchaseOrdersController::class, 'approve']);
Route::post('purchase-orders/{id}/cancel', [PurchaseOrdersController::class, 'cancel']);
Route::post('purchase-orders/{id}/receive', [PurchaseOrdersController::class, 'receive']);
Route::get('purchase-orders/{id}', [PurchaseOrdersController::class, 'show']);
Route::patch('purchase-orders/{id}', [PurchaseOrdersController::class, 'update']);
Route::post('purchase-orders/delete/{id}', [PurchaseOrdersController::class, 'clear']);
Route::resource('purchase-orders', PurchaseOrdersController::class);

// GRN (Goods Receipt Note)
Route::get('grns/pending', [GrnsController::class, 'pending']);
Route::post('grns/from-po/{poId}', [GrnsController::class, 'createFromPo']);
Route::post('grns/{id}/complete', [GrnsController::class, 'complete']);
Route::post('grns/{id}/cancel', [GrnsController::class, 'cancel']);
Route::get('grns/{id}', [GrnsController::class, 'show']);
Route::patch('grns/{id}', [GrnsController::class, 'update']);
Route::resource('grns', GrnsController::class);

// Requisitions
Route::get('requisitions/my-requisitions', [RequisitionsController::class, 'myRequisitions']);
Route::get('requisitions/pending-approvals', [RequisitionsController::class, 'pendingApprovals']);
Route::get('requisitions/pending-fulfillment', [RequisitionsController::class, 'pendingFulfillment']);
Route::post('requisitions/{id}/submit', [RequisitionsController::class, 'submit']);
Route::post('requisitions/{id}/approve', [RequisitionsController::class, 'approve']);
Route::post('requisitions/{id}/reject', [RequisitionsController::class, 'reject']);
Route::post('requisitions/{id}/fulfill', [RequisitionsController::class, 'fulfill']);
Route::post('requisitions/{id}/cancel', [RequisitionsController::class, 'cancel']);
Route::get('requisitions/{id}', [RequisitionsController::class, 'show']);
Route::patch('requisitions/{id}', [RequisitionsController::class, 'update']);
Route::resource('requisitions', RequisitionsController::class);

// Challans
Route::get('challans/my-challans', [ChallansController::class, 'myChallans']);
Route::get('challans/pending-for-location', [ChallansController::class, 'pendingForLocation']);
Route::get('challans/{id}/download-pdf', [ChallansController::class, 'downloadPdf']);
Route::post('challans/{id}/dispatch', [ChallansController::class, 'dispatch']);
Route::post('challans/{id}/deliver', [ChallansController::class, 'deliver']);
Route::post('challans/delete/{id}', [ChallansController::class, 'clear']);
Route::get('challans/{id}', [ChallansController::class, 'show']);
Route::resource('challans', ChallansController::class)->except(['show']);

// Questionnaires
Route::get('questionnaires/active', [QuestionnairesController::class, 'getActive']);
Route::get('questionnaires/my-responses', [QuestionnairesController::class, 'getMyResponses']);
Route::get('questionnaires/{id}/responses', [QuestionnairesController::class, 'getResponses']);
Route::post('questionnaires/{id}/submit', [QuestionnairesController::class, 'submitResponse']);
Route::get('questionnaires/{id}', [QuestionnairesController::class, 'show']);
Route::patch('questionnaires/{id}', [QuestionnairesController::class, 'update']);
Route::post('questionnaires/delete/{id}', [QuestionnairesController::class, 'clear']);
Route::post('questionnaires/restore/{id}', [QuestionnairesController::class, 'restore']);
Route::get('retailers/{retailer_id}/questionnaire-scores', [QuestionnairesController::class, 'getRetailerScores']);
Route::resource('questionnaires', QuestionnairesController::class);

// Testing Dashboard (local environment only)
if (app()->isLocal()) {
    Route::get('testing/tokens', [TestingController::class, 'getTestTokens']);
}

// Temporary route for testing swagger docs access
Route::get('/test-swagger-json', function () {
    $filePath = storage_path('api-docs/api-docs.json');
    
    if (!file_exists($filePath)) {
        abort(404, 'Swagger documentation file not found');
    }
    
    $content = file_get_contents($filePath);
    
    return response($content, 200)
        ->header('Content-Type', 'application/json');
});

// Route to handle the query parameter format that Swagger UI expects
Route::get('/api/docs', function () {
    $filePath = storage_path('api-docs/api-docs.json');
    
    if (!file_exists($filePath)) {
        abort(404, 'Swagger documentation file not found');
    }
    
    $content = file_get_contents($filePath);
    
    return response($content, 200)
        ->header('Content-Type', 'application/json');
});
