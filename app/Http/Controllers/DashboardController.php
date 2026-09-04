<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get dashboard data",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $companyId = $request->company->id;
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfWeek = Carbon::now()->startOfWeek();

        // KPI Stats
        $stats = [
            'total_retailers' => DB::table('retailers')->where('company_id', $companyId)->where('is_deleted', false)->count(),
            'active_retailers' => DB::table('retailers')->where('company_id', $companyId)->where('is_deleted', false)->where('is_active', true)->count(),
            'flagged_retailers' => DB::table('retailers')->where('company_id', $companyId)->where('is_deleted', false)->where('is_flagged', true)->count(),
            'total_franchises' => DB::table('franchises')->where('company_id', $companyId)->where('is_deleted', false)->count(),
            'active_franchises' => DB::table('franchises')->where('company_id', $companyId)->where('is_deleted', false)->where('is_active', true)->count(),
            'total_warehouses' => DB::table('warehouses')->where('company_id', $companyId)->where('is_deleted', false)->count(),
            'total_godowns' => DB::table('company_godowns')->where('company_id', $companyId)->where('is_deleted', false)->count(),
            'total_skus' => DB::table('skus')->where('company_id', $companyId)->where('is_deleted', false)->count(),
            'active_skus' => DB::table('skus')->where('company_id', $companyId)->where('is_deleted', false)->where('is_active', true)->count(),
            'total_vendors' => DB::table('vendors')->where('company_id', $companyId)->where('is_deleted', false)->count(),
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
            'total_locations' => DB::table('locations')->where('company_id', $companyId)->where('is_deleted', false)->count(),
        ];

        // New this month
        $newThisMonth = [
            'retailers' => DB::table('retailers')->where('company_id', $companyId)->where('is_deleted', false)->where('created_at', '>=', $startOfMonth)->count(),
            'franchises' => DB::table('franchises')->where('company_id', $companyId)->where('is_deleted', false)->where('created_at', '>=', $startOfMonth)->count(),
            'skus' => DB::table('skus')->where('company_id', $companyId)->where('is_deleted', false)->where('created_at', '>=', $startOfMonth)->count(),
            'users' => DB::table('company_user')
                ->join('users', 'company_user.user_id', '=', 'users.id')
                ->where('company_user.company_id', $companyId)
                ->whereNull('users.deleted_at')
                ->where('users.created_at', '>=', $startOfMonth)
                ->count(),
        ];

        // Recent retailers
        $recentRetailers = DB::table('retailers')
            ->leftJoin('franchises', 'retailers.franchise_id', '=', 'franchises.id')
            ->where('retailers.company_id', $companyId)
            ->where('retailers.is_deleted', false)
            ->select(
                'retailers.id',
                'retailers.name',
                'retailers.shop_name',
                'retailers.image',
                'retailers.rating',
                'retailers.is_flagged',
                'retailers.is_active',
                'retailers.created_at',
                'franchises.name as franchise_name'
            )
            ->orderBy('retailers.created_at', 'desc')
            ->limit(5)
            ->get();

        // Top franchises by retailer count
        $topFranchises = DB::table('franchises')
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
                DB::raw('COUNT(retailers.id) as retailer_count')
            )
            ->groupBy('franchises.id', 'franchises.name', 'franchises.code')
            ->orderBy('retailer_count', 'desc')
            ->limit(5)
            ->get();

        // Retailers by status distribution
        $retailerStatusDistribution = [
            ['status' => 'Active', 'count' => $stats['active_retailers']],
            ['status' => 'Inactive', 'count' => $stats['total_retailers'] - $stats['active_retailers']],
            ['status' => 'Flagged', 'count' => $stats['flagged_retailers']],
        ];

        // SKU categories summary
        $skuCategories = DB::table('sku_categories')
            ->leftJoin('skus', function ($join) {
                $join->on('sku_categories.id', '=', 'skus.category_id')
                    ->where('skus.is_deleted', false);
            })
            ->where('sku_categories.company_id', $companyId)
            ->where('sku_categories.is_deleted', false)
            ->select(
                'sku_categories.id',
                'sku_categories.name',
                DB::raw('COUNT(skus.id) as sku_count')
            )
            ->groupBy('sku_categories.id', 'sku_categories.name')
            ->orderBy('sku_count', 'desc')
            ->limit(6)
            ->get();

        // Retailer growth last 6 months
        $retailerGrowth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = DB::table('retailers')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $retailerGrowth[] = [
                'month' => $month->format('M Y'),
                'count' => $count,
            ];
        }

        // Distribution by location (state)
        $locationDistribution = DB::table('retailers')
            ->leftJoin('locations', 'retailers.location_id', '=', 'locations.id')
            ->where('retailers.company_id', $companyId)
            ->where('retailers.is_deleted', false)
            ->whereNotNull('locations.state')
            ->select(
                'locations.state',
                DB::raw('COUNT(retailers.id) as count')
            )
            ->groupBy('locations.state')
            ->orderBy('count', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'title' => 'Dashboard',
            'sub-title' => 'Dashboard data fetched successfully',
            'success' => true,
            'data' => [
                'stats' => $stats,
                'new_this_month' => $newThisMonth,
                'recent_retailers' => $recentRetailers,
                'top_franchises' => $topFranchises,
                'retailer_status_distribution' => $retailerStatusDistribution,
                'sku_categories' => $skuCategories,
                'retailer_growth' => $retailerGrowth,
                'location_distribution' => $locationDistribution,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/quick-stats",
     *     tags={"Dashboard"},
     *     summary="Get quick stats for cards",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function quickStats(Request $request)
    {
        $companyId = $request->company->id;
        $startOfMonth = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentMonthRetailers = DB::table('retailers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $lastMonthRetailers = DB::table('retailers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $retailerGrowthPercent = $lastMonthRetailers > 0
            ? round((($currentMonthRetailers - $lastMonthRetailers) / $lastMonthRetailers) * 100, 1)
            : ($currentMonthRetailers > 0 ? 100 : 0);

        $currentMonthSkus = DB::table('skus')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $lastMonthSkus = DB::table('skus')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $skuGrowthPercent = $lastMonthSkus > 0
            ? round((($currentMonthSkus - $lastMonthSkus) / $lastMonthSkus) * 100, 1)
            : ($currentMonthSkus > 0 ? 100 : 0);

        return response()->json([
            'title' => 'Dashboard',
            'sub-title' => 'Quick stats fetched successfully',
            'success' => true,
            'data' => [
                'retailers' => [
                    'total' => DB::table('retailers')->where('company_id', $companyId)->where('is_deleted', false)->count(),
                    'new_this_month' => $currentMonthRetailers,
                    'growth_percent' => $retailerGrowthPercent,
                ],
                'skus' => [
                    'total' => DB::table('skus')->where('company_id', $companyId)->where('is_deleted', false)->count(),
                    'new_this_month' => $currentMonthSkus,
                    'growth_percent' => $skuGrowthPercent,
                ],
                'franchises' => [
                    'total' => DB::table('franchises')->where('company_id', $companyId)->where('is_deleted', false)->count(),
                ],
                'users' => [
                    'total' => DB::table('company_user')
                        ->join('users', 'company_user.user_id', '=', 'users.id')
                        ->where('company_user.company_id', $companyId)
                        ->whereNull('users.deleted_at')
                        ->count(),
                    'active' => DB::table('company_user')
                        ->join('users', 'company_user.user_id', '=', 'users.id')
                        ->where('company_user.company_id', $companyId)
                        ->whereNull('users.deleted_at')
                        ->where('users.is_active', true)
                        ->count(),
                ],
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/alerts",
     *     tags={"Dashboard"},
     *     summary="Get dashboard alerts and notifications",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function alerts(Request $request)
    {
        $companyId = $request->company->id;

        $alerts = [];

        // Flagged retailers alert
        $flaggedCount = DB::table('retailers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('is_flagged', true)
            ->count();

        if ($flaggedCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'tabler-flag',
                'title' => 'Flagged Retailers',
                'message' => "{$flaggedCount} retailer(s) are flagged and need attention",
                'count' => $flaggedCount,
                'link' => '/admin/retailers?flagged=1',
            ];
        }

        // Low rated retailers alert
        $lowRatedCount = DB::table('retailers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('rating', '>', 0)
            ->where('rating', '<', 2)
            ->count();

        if ($lowRatedCount > 0) {
            $alerts[] = [
                'type' => 'error',
                'icon' => 'tabler-star-off',
                'title' => 'Low Rated Retailers',
                'message' => "{$lowRatedCount} retailer(s) have a rating below 2.0",
                'count' => $lowRatedCount,
                'link' => '/admin/retailers',
            ];
        }

        // Inactive franchises alert
        $inactiveFranchises = DB::table('franchises')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('is_active', false)
            ->count();

        if ($inactiveFranchises > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'tabler-building-community',
                'title' => 'Inactive Franchises',
                'message' => "{$inactiveFranchises} franchise(s) are inactive",
                'count' => $inactiveFranchises,
                'link' => '/admin/franchises',
            ];
        }

        // Inactive SKUs alert
        $inactiveSkus = DB::table('skus')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('is_active', false)
            ->count();

        if ($inactiveSkus > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'tabler-package-off',
                'title' => 'Inactive SKUs',
                'message' => "{$inactiveSkus} SKU(s) are inactive",
                'count' => $inactiveSkus,
                'link' => '/admin/masters/skus',
            ];
        }

        return response()->json([
            'title' => 'Dashboard',
            'sub-title' => 'Alerts fetched successfully',
            'success' => true,
            'data' => $alerts,
        ], 200);
    }
}
