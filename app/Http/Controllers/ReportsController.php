<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/summary",
     *     tags={"Reports"},
     *     summary="Get overall summary statistics",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function summary(Request $request)
    {
        $companyId = $request->company->id;

        // Get counts
        $data = [
            'total_warehouses' => DB::table('warehouses')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'total_company_godowns' => DB::table('company_godowns')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'total_franchises' => DB::table('franchises')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'active_franchises' => DB::table('franchises')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->count(),
            'total_retailers' => DB::table('retailers')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'active_retailers' => DB::table('retailers')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->count(),
            'flagged_retailers' => DB::table('retailers')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->where('is_flagged', true)
                ->count(),
            'total_vendors' => DB::table('vendors')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'total_skus' => DB::table('skus')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'active_skus' => DB::table('skus')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->count(),
            'total_locations' => DB::table('locations')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->count(),
            'total_users' => DB::table('company_user')
                ->join('users', 'company_user.user_id', '=', 'users.id')
                ->where('company_user.company_id', $companyId)
                ->whereNull('users.deleted_at')
                ->count(),
            'active_users' => DB::table('company_user')
                ->join('users', 'company_user.user_id', '=', 'users.id')
                ->where('company_user.company_id', $companyId)
                ->whereNull('users.deleted_at')
                ->where('users.is_active', true)
                ->count(),
        ];

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'Summary statistics fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/retailers-by-franchise",
     *     tags={"Reports"},
     *     summary="Get retailers count by franchise",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function retailersByFranchise(Request $request)
    {
        $companyId = $request->company->id;

        $data = DB::table('franchises')
            ->leftJoin('retailers', function ($join) {
                $join->on('franchises.id', '=', 'retailers.franchise_id')
                    ->where('retailers.is_deleted', false);
            })
            ->where('franchises.company_id', $companyId)
            ->where('franchises.is_deleted', false)
            ->select(
                'franchises.id',
                'franchises.name',
                'franchises.code',
                DB::raw('COUNT(retailers.id) as retailer_count'),
                DB::raw('SUM(CASE WHEN retailers.is_active = 1 THEN 1 ELSE 0 END) as active_count'),
                DB::raw('SUM(CASE WHEN retailers.is_flagged = 1 THEN 1 ELSE 0 END) as flagged_count')
            )
            ->groupBy('franchises.id', 'franchises.name', 'franchises.code')
            ->orderBy('retailer_count', 'desc')
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'Retailers by franchise fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/retailers-by-location",
     *     tags={"Reports"},
     *     summary="Get retailers count by location",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function retailersByLocation(Request $request)
    {
        $companyId = $request->company->id;

        $data = DB::table('locations')
            ->leftJoin('retailers', function ($join) {
                $join->on('locations.id', '=', 'retailers.location_id')
                    ->where('retailers.is_deleted', false);
            })
            ->where('locations.company_id', $companyId)
            ->where('locations.is_deleted', false)
            ->select(
                'locations.id',
                'locations.name',
                'locations.city',
                'locations.state',
                DB::raw('COUNT(retailers.id) as retailer_count'),
                DB::raw('SUM(CASE WHEN retailers.is_active = 1 THEN 1 ELSE 0 END) as active_count')
            )
            ->groupBy('locations.id', 'locations.name', 'locations.city', 'locations.state')
            ->orderBy('retailer_count', 'desc')
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'Retailers by location fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/skus-by-category",
     *     tags={"Reports"},
     *     summary="Get SKUs count by category",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function skusByCategory(Request $request)
    {
        $companyId = $request->company->id;

        $data = DB::table('sku_categories')
            ->leftJoin('skus', function ($join) {
                $join->on('sku_categories.id', '=', 'skus.category_id')
                    ->where('skus.is_deleted', false);
            })
            ->where('sku_categories.company_id', $companyId)
            ->where('sku_categories.is_deleted', false)
            ->select(
                'sku_categories.id',
                'sku_categories.name',
                DB::raw('COUNT(skus.id) as sku_count'),
                DB::raw('SUM(CASE WHEN skus.is_active = 1 THEN 1 ELSE 0 END) as active_count')
            )
            ->groupBy('sku_categories.id', 'sku_categories.name')
            ->orderBy('sku_count', 'desc')
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'SKUs by category fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/retailer-ratings",
     *     tags={"Reports"},
     *     summary="Get retailer ratings distribution",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function retailerRatings(Request $request)
    {
        $companyId = $request->company->id;

        // Rating distribution
        $distribution = DB::table('retailers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->select(
                DB::raw('CASE
                    WHEN rating >= 4 THEN "4-5 (Excellent)"
                    WHEN rating >= 3 THEN "3-4 (Good)"
                    WHEN rating >= 2 THEN "2-3 (Average)"
                    WHEN rating >= 1 THEN "1-2 (Poor)"
                    ELSE "No Rating"
                END as rating_range'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('rating_range')
            ->get();

        // Top rated retailers
        $topRated = DB::table('retailers')
            ->leftJoin('franchises', 'retailers.franchise_id', '=', 'franchises.id')
            ->where('retailers.company_id', $companyId)
            ->where('retailers.is_deleted', false)
            ->where('retailers.rating', '>', 0)
            ->select(
                'retailers.id',
                'retailers.name',
                'retailers.shop_name',
                'retailers.image',
                'retailers.rating',
                'franchises.name as franchise_name'
            )
            ->orderBy('retailers.rating', 'desc')
            ->limit(10)
            ->get();

        // Lowest rated retailers
        $lowestRated = DB::table('retailers')
            ->leftJoin('franchises', 'retailers.franchise_id', '=', 'franchises.id')
            ->where('retailers.company_id', $companyId)
            ->where('retailers.is_deleted', false)
            ->where('retailers.rating', '>', 0)
            ->select(
                'retailers.id',
                'retailers.name',
                'retailers.shop_name',
                'retailers.image',
                'retailers.rating',
                'franchises.name as franchise_name'
            )
            ->orderBy('retailers.rating', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'Retailer ratings fetched successfully',
            'success' => true,
            'data' => [
                'distribution' => $distribution,
                'top_rated' => $topRated,
                'lowest_rated' => $lowestRated,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/users-by-role",
     *     tags={"Reports"},
     *     summary="Get users count by role",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function usersByRole(Request $request)
    {
        $companyId = $request->company->id;

        $data = DB::table('roles')
            ->leftJoin('role_user', 'roles.id', '=', 'role_user.role_id')
            ->leftJoin('users', function ($join) use ($companyId) {
                $join->on('role_user.user_id', '=', 'users.id')
                    ->whereNull('users.deleted_at')
                    ->whereExists(function ($query) use ($companyId) {
                        $query->select(DB::raw(1))
                            ->from('company_user')
                            ->whereColumn('company_user.user_id', 'users.id')
                            ->where('company_user.company_id', $companyId);
                    });
            })
            ->select(
                'roles.id',
                'roles.name',
                DB::raw('COUNT(DISTINCT users.id) as user_count'),
                DB::raw('SUM(CASE WHEN users.is_active = 1 THEN 1 ELSE 0 END) as active_count')
            )
            ->groupBy('roles.id', 'roles.name')
            ->orderBy('user_count', 'desc')
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'Users by role fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/warehouses-overview",
     *     tags={"Reports"},
     *     summary="Get warehouses overview with godown counts",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function warehousesOverview(Request $request)
    {
        $companyId = $request->company->id;

        $data = DB::table('warehouses')
            ->leftJoin('locations', 'warehouses.location_id', '=', 'locations.id')
            ->leftJoin('company_godowns', function ($join) {
                $join->on('warehouses.id', '=', 'company_godowns.warehouse_id')
                    ->where('company_godowns.is_deleted', false);
            })
            ->leftJoin('franchises', function ($join) {
                $join->on('warehouses.id', '=', 'franchises.warehouse_id')
                    ->where('franchises.is_deleted', false);
            })
            ->where('warehouses.company_id', $companyId)
            ->where('warehouses.is_deleted', false)
            ->select(
                'warehouses.id',
                'warehouses.name',
                'warehouses.code',
                'warehouses.is_active',
                'locations.city',
                'locations.state',
                DB::raw('COUNT(DISTINCT company_godowns.id) as godown_count'),
                DB::raw('COUNT(DISTINCT franchises.id) as franchise_count')
            )
            ->groupBy(
                'warehouses.id',
                'warehouses.name',
                'warehouses.code',
                'warehouses.is_active',
                'locations.city',
                'locations.state'
            )
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => 'Warehouses overview fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/entity-growth",
     *     tags={"Reports"},
     *     summary="Get entity growth over time",
     *     @OA\Parameter(name="entity", in="query", description="Entity type", required=true),
     *     @OA\Parameter(name="period", in="query", description="Period (daily, weekly, monthly)", required=false),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function entityGrowth(Request $request)
    {
        $companyId = $request->company->id;
        $entity = $request->input('entity', 'retailers');
        $period = $request->input('period', 'monthly');
        $months = $request->input('months', 12);

        $validEntities = ['retailers', 'franchises', 'vendors', 'skus', 'users', 'warehouses', 'company_godowns', 'locations'];

        if (!in_array($entity, $validEntities)) {
            return response()->json([
                'title' => 'Reports',
                'sub-title' => 'Invalid entity type',
                'success' => false,
            ], 400);
        }

        $dateFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m',
        };

        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $data = DB::table($entity)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'title' => 'Reports',
            'sub-title' => ucfirst($entity) . ' growth data fetched successfully',
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/export",
     *     tags={"Reports"},
     *     summary="Export report data",
     *     @OA\Parameter(name="type", in="query", description="Report type", required=true),
     *     @OA\Parameter(name="format", in="query", description="Export format (csv, json)", required=false),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function export(Request $request)
    {
        $companyId = $request->company->id;
        $type = $request->input('type', 'retailers');
        $format = $request->input('format', 'json');

        $data = match ($type) {
            'retailers' => DB::table('retailers')
                ->leftJoin('franchises', 'retailers.franchise_id', '=', 'franchises.id')
                ->leftJoin('company_godowns', 'retailers.company_godown_id', '=', 'company_godowns.id')
                ->leftJoin('locations', 'retailers.location_id', '=', 'locations.id')
                ->where('retailers.company_id', $companyId)
                ->where('retailers.is_deleted', false)
                ->select(
                    'retailers.id',
                    'retailers.name',
                    'retailers.shop_name',
                    'retailers.phone',
                    'retailers.email',
                    'retailers.address',
                    'retailers.registration_type',
                    'retailers.gst_no',
                    'retailers.pan_no',
                    'retailers.credit_limit',
                    'retailers.rating',
                    'retailers.is_flagged',
                    'retailers.is_active',
                    'franchises.name as franchise_name',
                    'company_godowns.name as cg_name',
                    'locations.city',
                    'locations.state'
                )
                ->get()
                ->map(function ($r) {
                    // Tax ID: GST if exists, else PAN
                    $r->tax_type = !empty($r->gst_no) ? 'GST' : (!empty($r->pan_no) ? 'PAN' : '-');
                    $r->tax_number = !empty($r->gst_no) ? $r->gst_no : (!empty($r->pan_no) ? $r->pan_no : '-');

                    // Mapped To: Franchise if exists, else CG
                    $r->mapped_type = !empty($r->franchise_name) ? 'Franchise' : (!empty($r->cg_name) ? 'Company Godown' : '-');
                    $r->mapped_to = !empty($r->franchise_name) ? $r->franchise_name : (!empty($r->cg_name) ? $r->cg_name : '-');

                    // Remove individual fields to avoid duplicate columns in export
                    unset($r->franchise_name, $r->cg_name);
                    return $r;
                }),
            'franchises' => DB::table('franchises')
                ->leftJoin('warehouses', 'franchises.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('locations', 'franchises.location_id', '=', 'locations.id')
                ->where('franchises.company_id', $companyId)
                ->where('franchises.is_deleted', false)
                ->select(
                    'franchises.id',
                    'franchises.name',
                    'franchises.code',
                    'franchises.owner_name',
                    'franchises.phone',
                    'franchises.email',
                    'franchises.gst_no',
                    'franchises.pan_no',
                    'franchises.is_active',
                    'warehouses.name as warehouse_name',
                    'locations.city',
                    'locations.state'
                )
                ->get(),
            'vendors' => DB::table('vendors')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->select(
                    'id',
                    'name',
                    'contact_person',
                    'phone',
                    'email',
                    'city',
                    'state',
                    'gst_no',
                    'pan_no',
                    'bank_name',
                    'is_active'
                )
                ->get(),
            'skus' => DB::table('skus')
                ->leftJoin('sku_categories', 'skus.category_id', '=', 'sku_categories.id')
                ->where('skus.company_id', $companyId)
                ->where('skus.is_deleted', false)
                ->select(
                    'skus.id',
                    'skus.code',
                    'skus.name',
                    'skus.unit',
                    'skus.mrp',
                    'skus.selling_price',
                    'skus.purchase_price',
                    'skus.hsn_code',
                    'skus.gst_percent',
                    'skus.is_active',
                    'sku_categories.name as category_name'
                )
                ->get(),
            'attendance' => DB::table('attendances')
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->join('company_user', function($join) use ($companyId) {
                    $join->on('company_user.user_id', '=', 'users.id')
                        ->where('company_user.company_id', $companyId);
                })
                ->whereBetween('attendances.date', [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->whereNull('users.deleted_at')
                ->select(
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee_name"),
                    'attendances.date',
                    DB::raw('TIME(attendances.punch_in_time) as punch_in_time'),
                    DB::raw('TIME(attendances.punch_out_time) as punch_out_time'),
                    'attendances.total_hours',
                    DB::raw("CASE WHEN attendances.punch_out_time IS NULL THEN 'Not Punched Out' ELSE 'Complete' END as status")
                )
                ->orderBy('attendances.date', 'desc')
                ->get(),
            'leave' => DB::table('leave_requests')
                ->join('users', 'leave_requests.user_id', '=', 'users.id')
                ->join('company_user', function($join) use ($companyId) {
                    $join->on('company_user.user_id', '=', 'users.id')
                        ->where('company_user.company_id', $companyId);
                })
                ->leftJoin('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
                ->whereBetween('leave_requests.from_date', [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->whereNull('users.deleted_at')
                ->select(
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee_name"),
                    'leave_types.name as leave_type',
                    'leave_requests.from_date',
                    'leave_requests.to_date',
                    DB::raw("DATEDIFF(leave_requests.to_date, leave_requests.from_date) + 1 as days"),
                    'leave_requests.status',
                    'leave_requests.reason'
                )
                ->orderBy('leave_requests.from_date', 'desc')
                ->get(),
            'inventory' => DB::table('inventories')
                ->join('skus', 'inventories.sku_id', '=', 'skus.id')
                ->leftJoin('sku_categories', 'skus.category_id', '=', 'sku_categories.id')
                ->leftJoin('warehouses', function ($join) {
                    $join->on('inventories.location_id', '=', 'warehouses.id')
                         ->where('inventories.location_type', '=', 'warehouse');
                })
                ->leftJoin('franchises', function ($join) {
                    $join->on('inventories.location_id', '=', 'franchises.id')
                         ->where('inventories.location_type', '=', 'franchise');
                })
                ->where('inventories.company_id', $companyId)
                ->where('skus.is_deleted', false)
                ->select(
                    'skus.code as sku_code',
                    'skus.name as sku_name',
                    'sku_categories.name as category',
                    'skus.unit',
                    'inventories.quantity',
                    'inventories.location_type',
                    DB::raw("COALESCE(warehouses.name, franchises.name, inventories.location_type) as location_name"),
                    DB::raw("CASE WHEN inventories.quantity = 0 THEN 'Out of Stock' WHEN inventories.quantity <= 10 THEN 'Low Stock' ELSE 'In Stock' END as stock_status")
                )
                ->orderBy('inventories.quantity', 'asc')
                ->get(),
            'purchase_orders' => DB::table('purchase_orders')
                ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                ->where('purchase_orders.company_id', $companyId)
                ->whereBetween(DB::raw('DATE(purchase_orders.created_at)'), [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->select(
                    'purchase_orders.po_number',
                    'vendors.name as vendor_name',
                    'purchase_orders.status',
                    'purchase_orders.total_amount',
                    DB::raw('DATE(purchase_orders.created_at) as date')
                )
                ->orderBy('purchase_orders.created_at', 'desc')
                ->get(),
            'sales_orders' => DB::table('sales_orders')
                ->join('retailers', 'sales_orders.retailer_id', '=', 'retailers.id')
                // Sales orders use polymorphic source_type/source_id, not direct franchise_id
                ->leftJoin('franchises', function ($join) {
                    $join->on('sales_orders.source_id', '=', 'franchises.id')
                        ->where('sales_orders.source_type', '=', 'franchise');
                })
                ->leftJoin('company_godowns', function ($join) {
                    $join->on('sales_orders.source_id', '=', 'company_godowns.id')
                        ->where('sales_orders.source_type', '=', 'cg');
                })
                ->where('sales_orders.company_id', $companyId)
                ->whereBetween(DB::raw('DATE(sales_orders.created_at)'), [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->select(
                    'sales_orders.order_number',
                    DB::raw("COALESCE(retailers.shop_name, retailers.name) as retailer_name"),
                    'retailers.phone as retailer_phone',
                    DB::raw("COALESCE(franchises.name, company_godowns.name, '-') as source_name"),
                    DB::raw("CASE WHEN sales_orders.source_type = 'cg' THEN 'Company Godown' WHEN sales_orders.source_type = 'franchise' THEN 'Franchise' ELSE '-' END as source_type"),
                    'sales_orders.status',
                    'sales_orders.subtotal',
                    'sales_orders.tax_amount',
                    'sales_orders.discount_amount',
                    'sales_orders.total_amount',
                    DB::raw('DATE(sales_orders.created_at) as order_date')
                )
                ->orderBy('sales_orders.created_at', 'desc')
                ->get(),
            'requisitions' => DB::table('requisitions')
                ->leftJoin('users', 'requisitions.requested_by', '=', 'users.id')
                ->where('requisitions.company_id', $companyId)
                ->whereBetween(DB::raw('DATE(requisitions.created_at)'), [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->select(
                    'requisitions.requisition_number',
                    DB::raw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as requester_name"),
                    'requisitions.from_location_type',
                    'requisitions.to_location_type',
                    'requisitions.status',
                    'requisitions.request_date'
                )
                ->orderBy('requisitions.created_at', 'desc')
                ->get(),
            'grn' => DB::table('grns')
                ->leftJoin('users', 'grns.received_by', '=', 'users.id')
                ->where('grns.company_id', $companyId)
                ->where('grns.is_deleted', false)
                ->whereBetween(DB::raw('DATE(grns.received_date)'), [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->select(
                    'grns.grn_number',
                    'grns.reference_type',
                    'grns.location_type',
                    'grns.status',
                    'grns.received_date',
                    DB::raw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as received_by_name")
                )
                ->orderBy('grns.received_date', 'desc')
                ->get(),
            'inventory_movements' => DB::table('inventory_movements')
                ->join('skus', 'inventory_movements.sku_id', '=', 'skus.id')
                ->where('inventory_movements.company_id', $companyId)
                ->whereBetween(DB::raw('DATE(inventory_movements.created_at)'), [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->select(
                    'skus.name as sku_name',
                    'skus.code',
                    'inventory_movements.movement_type',
                    'inventory_movements.quantity',
                    'inventory_movements.from_location_type',
                    'inventory_movements.to_location_type',
                    'inventory_movements.reference_type',
                    DB::raw('DATE(inventory_movements.created_at) as date')
                )
                ->orderBy('inventory_movements.created_at', 'desc')
                ->get(),
            'cash_collections' => DB::table('cash_collections')
                ->leftJoin('users', 'cash_collections.collected_by', '=', 'users.id')
                ->leftJoin('retailers', 'cash_collections.retailer_id', '=', 'retailers.id')
                ->where('cash_collections.company_id', $companyId)
                ->where('cash_collections.is_deleted', false)
                ->whereBetween(DB::raw('DATE(cash_collections.collection_date)'), [
                    $request->input('from', Carbon::now()->startOfMonth()->toDateString()),
                    $request->input('to', Carbon::now()->toDateString()),
                ])
                ->select(
                    DB::raw("COALESCE(retailers.shop_name, retailers.name, '-') as retailer_name"),
                    'retailers.phone as retailer_phone',
                    DB::raw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as collector_name"),
                    'cash_collections.collection_date',
                    'cash_collections.amount_due',
                    'cash_collections.amount_collected',
                    'cash_collections.payment_mode',
                    'cash_collections.payment_reference',
                    'cash_collections.status',
                    'cash_collections.reschedule_count',
                    'cash_collections.next_collection_date',
                    'cash_collections.notes'
                )
                ->orderBy('cash_collections.collection_date', 'desc')
                ->get(),
            default => collect([]),
        };

        if (in_array($format, ['csv', 'xlsx', 'excel'])) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Build human-friendly headers — use first row if data exists, otherwise fall back to default columns per type
            if ($data->isNotEmpty()) {
                $rawHeaders = array_keys((array) $data->first());
            } else {
                $rawHeaders = $this->getDefaultExportHeaders($type);
            }
            $friendlyHeaders = array_map(fn($h) => ucwords(str_replace('_', ' ', $h)), $rawHeaders);

            // Write header row
            foreach ($friendlyHeaders as $col => $heading) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
                $sheet->setCellValue($cell, $heading);
            }

            // Style header row: blue background, white bold text, centered
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($rawHeaders));
            $headerRange = 'A1:' . $lastCol . '1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BBDEFB']]],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // Write data rows
            foreach ($data as $rowIndex => $row) {
                $rowNum = $rowIndex + 2;
                $values = array_values((array) $row);
                foreach ($values as $col => $value) {
                    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $rowNum;
                    $sheet->setCellValue($cell, $value ?? '');
                }

                // Stock status row coloring (overrides alternate stripe)
                $rowArr = (array) $row;
                $stockStatus = $rowArr['stock_status'] ?? null;
                $fontColor = '000000';
                if ($stockStatus === 'Out of Stock') {
                    $bgColor = 'E53935'; // red
                    $fontColor = 'FFFFFF';
                } elseif ($stockStatus === 'Low Stock') {
                    $bgColor = 'F9A825'; // amber
                    $fontColor = 'FFFFFF';
                } elseif ($stockStatus === 'In Stock') {
                    $bgColor = '388E3C'; // green
                    $fontColor = 'FFFFFF';
                } else {
                    $bgColor = ($rowIndex % 2 === 0) ? 'FFFFFF' : 'E3F2FD';
                }
                $rowRange = 'A' . $rowNum . ':' . $lastCol . $rowNum;
                $sheet->getStyle($rowRange)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    'font' => ['color' => ['rgb' => $fontColor], 'bold' => $fontColor === 'FFFFFF'],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0C4DE']],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);
                $sheet->getRowDimension($rowNum)->setRowHeight(20);
            }

            // Outer border around entire table
            $totalRows = $data->count() + 1;
            $tableRange = 'A1:' . $lastCol . $totalRows;
            $sheet->getStyle($tableRange)->applyFromArray([
                'borders' => [
                    'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1565C0']],
                ],
            ]);

            // Auto-size columns based on content
            foreach (range(1, count($rawHeaders)) as $colIndex) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Freeze header row
            $sheet->freezePane('A2');

            $filename = $type . '_export_' . now()->format('Y-m-d') . '.xlsx';

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            return response($content, 200)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'max-age=0');
        }

        return response()->json([
            'title' => 'Reports',
            'sub-title' => ucfirst($type) . ' data exported successfully',
            'success' => true,
            'count' => $data->count(),
            'data' => $data,
        ], 200);
    }

    /**
     * Default headers for empty exports — keeps Excel structure consistent even when no data
     */
    private function getDefaultExportHeaders($type)
    {
        return match ($type) {
            'cash_collections' => [
                'retailer_name', 'retailer_phone', 'collector_name',
                'collection_date', 'amount_due', 'amount_collected',
                'payment_mode', 'payment_reference', 'status',
                'reschedule_count', 'next_collection_date', 'notes',
            ],
            'retailers' => [
                'id', 'name', 'shop_name', 'phone', 'email', 'address',
                'registration_type', 'gst_no', 'pan_no', 'credit_limit',
                'rating', 'is_flagged', 'is_active',
                'mapped_type', 'mapped_to', 'city', 'state',
            ],
            'sales_orders' => [
                'order_number', 'retailer_name', 'franchise_name', 'status', 'total_amount', 'date',
            ],
            'purchase_orders' => [
                'po_number', 'vendor_name', 'status', 'total_amount', 'date',
            ],
            'attendance' => [
                'employee_name', 'date', 'punch_in_time', 'punch_out_time', 'status',
            ],
            'leave' => [
                'employee_name', 'leave_type', 'from_date', 'to_date', 'days', 'status', 'reason',
            ],
            'inventory' => [
                'sku_code', 'sku_name', 'category', 'unit', 'quantity',
                'location_type', 'location_name', 'stock_status',
            ],
            'grns' => [
                'grn_number', 'reference_type', 'location_type', 'status', 'received_date', 'received_by_name',
            ],
            'inventory_movements' => [
                'sku_name', 'code', 'movement_type', 'quantity',
                'from_location_type', 'to_location_type', 'reference_type', 'date',
            ],
            'franchises' => [
                'id', 'name', 'code', 'owner_name', 'phone', 'email',
                'gst_no', 'pan_no', 'is_active', 'warehouse_name', 'city', 'state',
            ],
            'vendors' => [
                'id', 'name', 'contact_person', 'phone', 'email',
                'city', 'state', 'gst_no', 'pan_no', 'bank_name', 'is_active',
            ],
            'skus' => [
                'id', 'code', 'name', 'unit', 'mrp', 'selling_price',
                'purchase_price', 'hsn_code', 'gst_percent', 'is_active', 'category_name',
            ],
            default => ['no_data'],
        };
    }

    public function attendanceSummary(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        // Daily attendance stats
        $daily = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->join('company_user', function($join) use ($companyId) {
                $join->on('company_user.user_id', '=', 'users.id')
                    ->where('company_user.company_id', $companyId);
            })
            ->whereBetween('attendances.date', [$from, $to])
            ->whereNull('users.deleted_at')
            ->select(
                'attendances.date',
                DB::raw('COUNT(DISTINCT attendances.user_id) as present_count'),
                DB::raw('COUNT(CASE WHEN attendances.punch_out_time IS NULL THEN 1 END) as not_punched_out')
            )
            ->groupBy('attendances.date')
            ->orderBy('date', 'desc')
            ->get();

        // Summary
        $summary = [
            'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            'avg_daily_attendance' => $daily->avg('present_count') ?? 0,
            'total_attendance_records' => $daily->sum('present_count'),
        ];

        return response()->json([
            'title' => 'Attendance Report',
            'sub-title' => 'Attendance summary fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'daily' => $daily],
        ], 200);
    }

    public function leaveReport(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('leave_requests')
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->join('company_user', function($join) use ($companyId) {
                $join->on('company_user.user_id', '=', 'users.id')
                    ->where('company_user.company_id', $companyId);
            })
            ->leftJoin('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->whereBetween('leave_requests.from_date', [$from, $to])
            ->whereNull('users.deleted_at')
            ->select(
                'leave_requests.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee_name"),
                'leave_types.name as leave_type',
                'leave_requests.from_date',
                'leave_requests.to_date',
                DB::raw("DATEDIFF(leave_requests.to_date, leave_requests.from_date) + 1 as days"),
                'leave_requests.status',
                'leave_requests.reason',
                'leave_requests.remarks'
            )
            ->orderBy('leave_requests.from_date', 'desc')
            ->get();

        $summary = [
            'total' => $data->count(),
            'approved' => $data->where('status', 'approved')->count(),
            'pending' => $data->where('status', 'pending')->count(),
            'rejected' => $data->where('status', 'rejected')->count(),
            'total_days' => $data->where('status', 'approved')->sum('days'),
        ];

        return response()->json([
            'title' => 'Leave Report',
            'sub-title' => 'Leave report fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function inventorySummary(Request $request)
    {
        $companyId = $request->company->id;

        // Overall inventory stats
        $stats = DB::table('inventories')
            ->join('skus', 'inventories.sku_id', '=', 'skus.id')
            ->where('inventories.company_id', $companyId)
            ->where('skus.is_deleted', false)
            ->select(
                DB::raw('COUNT(DISTINCT inventories.sku_id) as total_skus'),
                DB::raw('SUM(inventories.quantity) as total_quantity'),
                DB::raw('COUNT(CASE WHEN inventories.quantity = 0 THEN 1 END) as out_of_stock'),
                DB::raw('COUNT(CASE WHEN inventories.quantity > 0 AND inventories.quantity <= 10 THEN 1 END) as low_stock')
            )
            ->first();

        // Top stocked items
        $topStocked = DB::table('inventories')
            ->join('skus', 'inventories.sku_id', '=', 'skus.id')
            ->leftJoin('sku_categories', 'skus.category_id', '=', 'sku_categories.id')
            ->leftJoin('warehouses', function ($join) {
                $join->on('inventories.location_id', '=', 'warehouses.id')
                     ->where('inventories.location_type', '=', 'warehouse');
            })
            ->leftJoin('franchises', function ($join) {
                $join->on('inventories.location_id', '=', 'franchises.id')
                     ->where('inventories.location_type', '=', 'franchise');
            })
            ->where('inventories.company_id', $companyId)
            ->where('skus.is_deleted', false)
            ->select(
                'skus.name',
                'skus.code',
                'skus.unit',
                'sku_categories.name as category',
                'inventories.quantity',
                'inventories.location_type',
                DB::raw("COALESCE(warehouses.name, franchises.name, inventories.location_type) as location_name"),
                DB::raw("CASE WHEN inventories.quantity = 0 THEN 'Out of Stock' WHEN inventories.quantity <= 10 THEN 'Low Stock' ELSE 'In Stock' END as stock_status")
            )
            ->orderBy('inventories.quantity', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'title' => 'Inventory Report',
            'sub-title' => 'Inventory summary fetched successfully',
            'success' => true,
            'data' => ['stats' => $stats, 'items' => $topStocked],
        ], 200);
    }

    public function purchaseOrdersReport(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('purchase_orders')
            ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->where('purchase_orders.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(purchase_orders.created_at)'), [$from, $to])
            ->select(
                'purchase_orders.id',
                'purchase_orders.po_number',
                'purchase_orders.status',
                'purchase_orders.total_amount',
                'purchase_orders.created_at',
                'vendors.name as vendor_name'
            )
            ->orderBy('purchase_orders.created_at', 'desc')
            ->get();

        $summary = [
            'total' => $data->count(),
            'draft' => $data->where('status', 'draft')->count(),
            'pending' => $data->where('status', 'pending')->count(),
            'approved' => $data->where('status', 'approved')->count(),
            'received' => $data->where('status', 'received')->count(),
            'cancelled' => $data->where('status', 'cancelled')->count(),
            'total_value' => $data->sum('total_amount'),
        ];

        return response()->json([
            'title' => 'Purchase Orders Report',
            'sub-title' => 'Purchase orders report fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function salesOrdersReport(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('sales_orders')
            ->join('retailers', 'sales_orders.retailer_id', '=', 'retailers.id')
            ->leftJoin('franchises', function ($join) {
                $join->on('sales_orders.source_id', '=', 'franchises.id')
                    ->where('sales_orders.source_type', '=', 'franchise');
            })
            ->leftJoin('company_godowns', function ($join) {
                $join->on('sales_orders.source_id', '=', 'company_godowns.id')
                    ->where('sales_orders.source_type', '=', 'cg');
            })
            ->where('sales_orders.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(sales_orders.created_at)'), [$from, $to])
            ->select(
                'sales_orders.id',
                'sales_orders.order_number',
                'sales_orders.status',
                'sales_orders.total_amount',
                'sales_orders.created_at',
                DB::raw("COALESCE(retailers.shop_name, retailers.name) as retailer_name"),
                DB::raw("COALESCE(franchises.name, company_godowns.name, '-') as source_name"),
                'sales_orders.source_type'
            )
            ->orderBy('sales_orders.created_at', 'desc')
            ->get();

        $summary = [
            'total' => $data->count(),
            'pending' => $data->where('status', 'pending')->count(),
            'approved' => $data->where('status', 'approved')->count(),
            'delivered' => $data->where('status', 'delivered')->count(),
            'cancelled' => $data->where('status', 'cancelled')->count(),
            'total_value' => $data->sum('total_amount'),
        ];

        return response()->json([
            'title' => 'Sales Orders Report',
            'sub-title' => 'Sales orders report fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function requisitionsReport(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('requisitions')
            ->leftJoin('users', 'requisitions.requested_by', '=', 'users.id')
            ->where('requisitions.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(requisitions.created_at)'), [$from, $to])
            ->select(
                'requisitions.id',
                'requisitions.requisition_number',
                'requisitions.status',
                'requisitions.from_location_type',
                'requisitions.to_location_type',
                'requisitions.request_date',
                DB::raw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as requester_name")
            )
            ->orderBy('requisitions.created_at', 'desc')
            ->get();

        $summary = [
            'total' => $data->count(),
            'pending' => $data->where('status', 'pending')->count(),
            'approved' => $data->where('status', 'approved')->count(),
            'rejected' => $data->where('status', 'rejected')->count(),
            'fulfilled' => $data->where('status', 'fulfilled')->count(),
        ];

        return response()->json([
            'title' => 'Requisitions Report',
            'sub-title' => 'Requisitions report fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function cashCollectionsReport(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('cash_collections')
            ->leftJoin('users', 'cash_collections.collected_by', '=', 'users.id')
            ->leftJoin('retailers', 'cash_collections.retailer_id', '=', 'retailers.id')
            ->where('cash_collections.company_id', $companyId)
            ->where('cash_collections.is_deleted', false)
            ->whereBetween(DB::raw('DATE(cash_collections.collection_date)'), [$from, $to])
            ->select(
                'cash_collections.id',
                'cash_collections.collection_date',
                'cash_collections.amount_due',
                'cash_collections.amount_collected',
                'cash_collections.status',
                'cash_collections.payment_mode',
                'cash_collections.reschedule_count',
                DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as collector_name"),
                DB::raw("COALESCE(retailers.shop_name, retailers.name) as retailer_name")
            )
            ->orderBy('cash_collections.collection_date', 'desc')
            ->get();

        // Add aliases for frontend backward compatibility (amount field)
        $data = $data->map(function ($r) {
            $r->amount = $r->amount_collected ?? $r->amount_due ?? 0;
            return $r;
        });

        $summary = [
            'total' => $data->count(),
            'total_records' => $data->count(),
            'total_collected' => $data->where('status', 'collected')->sum('amount_collected'),
            'total_outstanding' => $data->sum('amount_due') - $data->sum('amount_collected'),
            'total_amount' => $data->sum('amount_collected'),
            'collected' => $data->where('status', 'collected')->count(),
            'pending' => $data->where('status', 'pending')->count(),
            'rescheduled' => $data->where('status', 'rescheduled')->count(),
        ];

        return response()->json([
            'title' => 'Cash Collections Report',
            'sub-title' => 'Cash collections report fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function grnReport(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('grns')
            ->leftJoin('users', 'grns.received_by', '=', 'users.id')
            ->where('grns.company_id', $companyId)
            ->where('grns.is_deleted', false)
            ->whereBetween(DB::raw('DATE(grns.received_date)'), [$from, $to])
            ->select(
                'grns.id',
                'grns.grn_number',
                'grns.reference_type',
                'grns.location_type',
                'grns.status',
                'grns.received_date',
                DB::raw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as received_by_name")
            )
            ->orderBy('grns.received_date', 'desc')
            ->get();

        $summary = [
            'total' => $data->count(),
            'draft' => $data->where('status', 'draft')->count(),
            'completed' => $data->where('status', 'completed')->count(),
            'cancelled' => $data->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'title' => 'GRN Report',
            'sub-title' => 'GRN report fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function inventoryMovements(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('inventory_movements')
            ->join('skus', 'inventory_movements.sku_id', '=', 'skus.id')
            ->where('inventory_movements.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(inventory_movements.created_at)'), [$from, $to])
            ->select(
                'inventory_movements.id',
                'skus.name as sku_name',
                'skus.code',
                'inventory_movements.movement_type',
                'inventory_movements.quantity',
                'inventory_movements.from_location_type',
                'inventory_movements.to_location_type',
                'inventory_movements.reference_type',
                DB::raw('DATE(inventory_movements.created_at) as date')
            )
            ->orderBy('inventory_movements.created_at', 'desc')
            ->get();

        $summary = [
            'total_movements' => $data->count(),
            'total_in' => $data->whereNotNull('to_location_type')->count(),
            'total_out' => $data->whereNotNull('from_location_type')->count(),
            'by_type' => $data->groupBy('movement_type')->map->count(),
        ];

        return response()->json([
            'title' => 'Inventory Movements',
            'sub-title' => 'Inventory movements fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function dailySummary(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        // Daily attendance count
        $attendance = DB::table('attendances')
            ->join('company_user', function($join) use ($companyId) {
                $join->on('company_user.user_id', '=', 'attendances.user_id')
                    ->where('company_user.company_id', $companyId);
            })
            ->whereBetween('attendances.date', [$from, $to])
            ->select(
                'attendances.date',
                DB::raw('COUNT(DISTINCT attendances.user_id) as attendance_count')
            )
            ->groupBy('attendances.date')
            ->get()
            ->keyBy('date');

        // Daily sales orders count
        $orders = DB::table('sales_orders')
            ->where('sales_orders.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(sales_orders.created_at)'), [$from, $to])
            ->select(
                DB::raw('DATE(sales_orders.created_at) as date'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->groupBy(DB::raw('DATE(sales_orders.created_at)'))
            ->get()
            ->keyBy('date');

        // Daily cash collections sum
        $collections = DB::table('cash_collections')
            ->join('company_user', function($join) use ($companyId) {
                $join->on('company_user.user_id', '=', 'cash_collections.user_id')
                    ->where('company_user.company_id', $companyId);
            })
            ->whereBetween(DB::raw('DATE(cash_collections.collection_date)'), [$from, $to])
            ->select(
                DB::raw('DATE(cash_collections.collection_date) as date'),
                DB::raw('SUM(cash_collections.amount) as collections_amount')
            )
            ->groupBy(DB::raw('DATE(cash_collections.collection_date)'))
            ->get()
            ->keyBy('date');

        // Merge by date
        $allDates = collect(array_keys(
            array_merge($attendance->keys()->flip()->toArray(), $orders->keys()->flip()->toArray(), $collections->keys()->flip()->toArray())
        ))->sort()->values();

        $records = $allDates->map(function ($date) use ($attendance, $orders, $collections) {
            return [
                'date' => $date,
                'attendance_count' => $attendance->get($date)?->attendance_count ?? 0,
                'orders_count' => $orders->get($date)?->orders_count ?? 0,
                'collections_amount' => $collections->get($date)?->collections_amount ?? 0,
            ];
        })->sortByDesc('date')->values();

        return response()->json([
            'title' => 'Daily Summary',
            'sub-title' => 'Daily summary fetched successfully',
            'success' => true,
            'data' => ['records' => $records],
        ], 200);
    }

    public function employeePerformance(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $users = DB::table('users')
            ->join('company_user', function($join) use ($companyId) {
                $join->on('company_user.user_id', '=', 'users.id')
                    ->where('company_user.company_id', $companyId);
            })
            ->whereNull('users.deleted_at')
            ->select('users.id', DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee_name"))
            ->get();

        $records = $users->map(function ($user) use ($companyId, $from, $to) {
            $attendanceDays = DB::table('attendances')
                ->where('user_id', $user->id)
                ->whereBetween('date', [$from, $to])
                ->count();

            $collectionsData = DB::table('cash_collections')
                ->where('collected_by', $user->id)
                ->whereBetween(DB::raw('DATE(collection_date)'), [$from, $to])
                ->select(
                    DB::raw('COUNT(*) as collections_count'),
                    DB::raw('COALESCE(SUM(amount), 0) as collections_amount')
                )
                ->first();

            $ordersCount = DB::table('retailer_visits')
                ->join('sales_orders', 'sales_orders.retailer_id', '=', 'retailer_visits.retailer_id')
                ->where('retailer_visits.user_id', $user->id)
                ->where('sales_orders.company_id', $companyId)
                ->whereBetween(DB::raw('DATE(retailer_visits.created_at)'), [$from, $to])
                ->count();

            return [
                'employee_name' => $user->employee_name,
                'attendance_days' => $attendanceDays,
                'orders_count' => $ordersCount,
                'collections_count' => $collectionsData->collections_count ?? 0,
                'collections_amount' => $collectionsData->collections_amount ?? 0,
            ];
        })->sortByDesc('attendance_days')->values();

        return response()->json([
            'title' => 'Employee Performance',
            'sub-title' => 'Employee performance report fetched successfully',
            'success' => true,
            'data' => ['records' => $records],
        ], 200);
    }

    public function pjpCompletion(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        // PJPs are recurring weekly schedules (day_of_week + employee_id)
        // Completion is calculated from retailer_visits within the date range
        $pjps = DB::table('pjps')
            ->join('users', 'pjps.employee_id', '=', 'users.id')
            ->where('pjps.company_id', $companyId)
            ->where('pjps.is_deleted', false)
            ->whereNull('users.deleted_at')
            ->select(
                'pjps.id',
                'pjps.name',
                'pjps.day_of_week',
                'pjps.employee_id',
                'pjps.is_active',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee_name")
            )
            ->get();

        $records = $pjps->map(function ($pjp) use ($from, $to) {
            $plannedCount = DB::table('pjp_retailers')
                ->where('pjp_id', $pjp->id)
                ->count();

            $visitedCount = DB::table('retailer_visits')
                ->join('pjp_retailers', 'pjp_retailers.retailer_id', '=', 'retailer_visits.retailer_id')
                ->where('pjp_retailers.pjp_id', $pjp->id)
                ->where('retailer_visits.user_id', $pjp->employee_id)
                ->whereBetween(DB::raw('DATE(retailer_visits.created_at)'), [$from, $to])
                ->count();

            $completionRate = $plannedCount > 0 ? round(($visitedCount / $plannedCount) * 100, 1) : 0;

            // Map day_of_week number to name (1=Mon, ..., 7=Sun)
            $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $dayName = $dayNames[$pjp->day_of_week] ?? '-';

            return [
                'pjp_name' => $pjp->name,
                'employee_name' => $pjp->employee_name,
                'day_of_week' => $dayName,
                'status' => $pjp->is_active ? 'Active' : 'Inactive',
                'planned_count' => $plannedCount,
                'visited_count' => $visitedCount,
                'completion_rate' => $completionRate,
            ];
        })->values();

        return response()->json([
            'title' => 'PJP Completion',
            'sub-title' => 'PJP completion report fetched successfully',
            'success' => true,
            'data' => ['records' => $records],
        ], 200);
    }

    public function retailerPerformance(Request $request)
    {
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('retailers')
            ->leftJoin('sales_orders', function($join) use ($companyId, $from, $to) {
                $join->on('sales_orders.retailer_id', '=', 'retailers.id')
                    ->where('sales_orders.company_id', $companyId)
                    ->whereBetween(DB::raw('DATE(sales_orders.created_at)'), [$from, $to]);
            })
            ->leftJoin('franchises', 'retailers.franchise_id', '=', 'franchises.id')
            ->leftJoin('company_godowns', 'retailers.company_godown_id', '=', 'company_godowns.id')
            ->where('retailers.company_id', $companyId)
            ->where('retailers.is_deleted', false)
            ->select(
                'retailers.id',
                'retailers.name',
                'retailers.shop_name',
                'retailers.phone',
                'retailers.email',
                'retailers.gst_no',
                'retailers.pan_no',
                'retailers.registration_type',
                'retailers.rating',
                'retailers.is_flagged',
                'retailers.is_active',
                'franchises.name as franchise_name',
                'company_godowns.name as cg_name',
                DB::raw('COUNT(sales_orders.id) as orders_count'),
                DB::raw('MAX(sales_orders.created_at) as last_order_date'),
                DB::raw('COALESCE(SUM(sales_orders.total_amount), 0) as total_sales')
            )
            ->groupBy(
                'retailers.id',
                'retailers.name',
                'retailers.shop_name',
                'retailers.phone',
                'retailers.email',
                'retailers.gst_no',
                'retailers.pan_no',
                'retailers.registration_type',
                'retailers.rating',
                'retailers.is_flagged',
                'retailers.is_active',
                'franchises.name',
                'company_godowns.name'
            )
            ->orderBy('orders_count', 'desc')
            ->get();

        // Add tax_id (GST or PAN) and mapped_to (Franchise or CG) for display in reports/excel
        $data = $data->map(function ($r) {
            $r->tax_id_label = !empty($r->gst_no) ? 'GST' : (!empty($r->pan_no) ? 'PAN' : '-');
            $r->tax_id_value = !empty($r->gst_no) ? $r->gst_no : (!empty($r->pan_no) ? $r->pan_no : '-');

            // Mapped To: Franchise or Company Godown
            $r->mapped_type = !empty($r->franchise_name) ? 'Franchise' : (!empty($r->cg_name) ? 'Company Godown' : '-');
            $r->mapped_to = !empty($r->franchise_name) ? $r->franchise_name : (!empty($r->cg_name) ? $r->cg_name : '-');
            return $r;
        });

        return response()->json([
            'title' => 'Retailer Performance',
            'sub-title' => 'Retailer performance report fetched successfully',
            'success' => true,
            'data' => ['records' => $data],
        ], 200);
    }

    public function myAttendance(Request $request)
    {
        $userId = $request->user()->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('date', [$from, $to])
            ->select(
                'id',
                'date',
                DB::raw('TIME(punch_in_time) as punch_in'),
                DB::raw('TIME(punch_out_time) as punch_out'),
                DB::raw("CASE WHEN punch_out_time IS NOT NULL THEN ROUND(TIMESTAMPDIFF(MINUTE, punch_in_time, punch_out_time) / 60, 2) ELSE total_hours END as hours_worked"),
                'status'
            )
            ->orderBy('date', 'desc')
            ->get();

        $totalHours = $data->whereNotNull('punch_out')->sum('hours_worked');

        $summary = [
            'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            'present_days' => $data->count(),
            'total_hours' => round($totalHours, 2),
        ];

        return response()->json([
            'title' => 'My Attendance',
            'sub-title' => 'My attendance fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function mySales(Request $request)
    {
        $userId = $request->user()->id;
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        // Get retailers this user has visited
        $visitedRetailerIds = DB::table('retailer_visits')
            ->where('user_id', $userId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->pluck('retailer_id')
            ->unique();

        $data = DB::table('sales_orders')
            ->join('retailers', 'sales_orders.retailer_id', '=', 'retailers.id')
            ->where('sales_orders.company_id', $companyId)
            ->whereIn('sales_orders.retailer_id', $visitedRetailerIds)
            ->whereBetween(DB::raw('DATE(sales_orders.created_at)'), [$from, $to])
            ->select(
                'sales_orders.id',
                'sales_orders.order_number',
                'sales_orders.status',
                'sales_orders.total_amount',
                'sales_orders.created_at',
                'retailers.name as retailer_name'
            )
            ->orderBy('sales_orders.created_at', 'desc')
            ->get();

        $summary = [
            'total_orders' => $data->count(),
            'total_value' => $data->sum('total_amount'),
        ];

        return response()->json([
            'title' => 'My Sales',
            'sub-title' => 'My sales fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function myCollections(Request $request)
    {
        $userId = $request->user()->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('cash_collections')
            ->leftJoin('retailers', 'cash_collections.retailer_id', '=', 'retailers.id')
            ->where('cash_collections.user_id', $userId)
            ->whereBetween(DB::raw('DATE(cash_collections.collection_date)'), [$from, $to])
            ->select(
                'cash_collections.id',
                'cash_collections.collection_date',
                'cash_collections.amount',
                'cash_collections.status',
                'cash_collections.is_rescheduled',
                'retailers.name as retailer_name'
            )
            ->orderBy('cash_collections.collection_date', 'desc')
            ->get();

        $summary = [
            'total_collected' => $data->where('status', 'collected')->sum('amount'),
            'total_rescheduled' => $data->where('is_rescheduled', true)->count(),
            'outstanding_amount' => $data->where('status', 'pending')->sum('amount'),
        ];

        return response()->json([
            'title' => 'My Collections',
            'sub-title' => 'My collections fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }

    public function journeySummary(Request $request)
    {
        $userId = $request->input('driver_id', $request->user()->id);
        $companyId = $request->company->id;
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $data = DB::table('journey_plans')
            ->where('journey_plans.driver_id', $userId)
            ->where('journey_plans.company_id', $companyId)
            ->where('journey_plans.is_deleted', false)
            ->whereBetween(DB::raw('DATE(journey_plans.date)'), [$from, $to])
            ->select(
                'journey_plans.id',
                'journey_plans.date',
                'journey_plans.status',
                'journey_plans.start_time',
                'journey_plans.end_time',
                'journey_plans.start_odometer',
                'journey_plans.end_odometer',
                DB::raw('CASE WHEN journey_plans.end_odometer IS NOT NULL AND journey_plans.start_odometer IS NOT NULL THEN (journey_plans.end_odometer - journey_plans.start_odometer) ELSE 0 END as distance')
            )
            ->orderBy('journey_plans.date', 'desc')
            ->get();

        $totalDistance = $data->sum('distance');

        $summary = [
            'total_trips' => $data->count(),
            'completed_trips' => $data->where('status', 'completed')->count(),
            'total_distance' => round($totalDistance, 2),
            'avg_distance' => $data->count() > 0 ? round($totalDistance / $data->count(), 2) : 0,
        ];

        return response()->json([
            'title' => 'Journey Summary',
            'sub-title' => 'Journey summary fetched successfully',
            'success' => true,
            'data' => ['summary' => $summary, 'records' => $data],
        ], 200);
    }
}
