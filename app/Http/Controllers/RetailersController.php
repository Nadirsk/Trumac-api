<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Notification;
use App\Models\Retailer;
use App\Models\RetailerRating;
use App\Models\Pjp;
use App\Models\User;
use App\Models\Position;
use App\Mail\RetailerLoginMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetailersController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore', 'toggleFlag', 'getRating', 'addRating', 'createLogin']);
    }

    public function index(Request $request)
    {
        try {
            $query = Retailer::query();

            // Handle no_orders_since filter before Utility::prepareSearchQuery
            // Per project requirement B1.8: "If the retailers have not ordered more than 3 times,
            // that retailer will be managed by the IT Team Leader"
            //
            // A retailer is "inactive" if:
            //  (a) They had orders before but stopped (last_order_date < threshold), OR
            //  (b) They were created before the threshold but never ordered AND
            //      have at least 3 "no_order" sales order entries from PJP visits
            //
            // Newly created retailers (within threshold window) are EXCLUDED — they haven't had time to order yet.
            if ($request->filled('search.no_orders_since')) {
                $days = (int) $request->input('search.no_orders_since');
                $dateThreshold = now()->subDays($days);

                $query->where(function ($q) use ($dateThreshold) {
                    // Case (a): Had orders before but stopped ordering
                    $q->where('last_order_date', '<', $dateThreshold)
                        // Case (b): Never ordered, was created long ago, AND has 3+ no-order visits
                        ->orWhere(function ($q2) use ($dateThreshold) {
                            $q2->whereNull('last_order_date')
                                ->where('created_at', '<', $dateThreshold)
                                ->whereHas('salesOrders', function ($soQuery) {
                                    $soQuery->where('status', \App\Models\SalesOrder::STATUS_NO_ORDER);
                                }, '>=', 3);
                        });
                });

                // Remove no_orders_since from search array so Utility doesn't try to process it
                $searchParams = $request->input('search', []);
                unset($searchParams['no_orders_since']);
                $request->merge(['search' => $searchParams]);
            }

            $query = Utility::prepareSearchQuery($query, $request, new Retailer());
            $query->with(['franchise', 'companyGodown', 'location', 'user:id,retailer_id']);
            $query->orderBy('id', 'desc');

            // Get count before pagination
            $count = $query->count();

            $retailers = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer listing Fetched Successfully',
                'success' => true,
                'count' => $count,
                'data' => $retailers,
            ], 200);
        } catch (\Exception $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        // Validate base fields
        $attributes = $request->validate([
            'name' => 'required|string|max:100',
            'shop_name' => 'required|string|max:150',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'franchise_id' => 'required_without:company_godown_id|nullable|exists:franchises,id',
            'company_godown_id' => 'required_without:franchise_id|nullable|exists:company_godowns,id',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'registration_type' => 'required|in:Registered,Unregistered',
            'gst_no' => 'nullable|string|max:20',
            'pan_no' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable',
            'employee_id' => 'nullable|exists:users,id',
            'day_of_week' => 'nullable|integer|min:1|max:7',
        ]);

        // Conditional validation for GST/PAN based on registration type
        if ($attributes['registration_type'] === 'registered') {
            if (empty($attributes['gst_no'])) {
                return response()->json([
                    'title' => 'Validation Error',
                    'sub-title' => 'GST Number is required for registered retailers',
                    'success' => false,
                    'errors' => ['gst_no' => ['GST Number is required for registered retailers']],
                ], 422);
            }
        } elseif ($attributes['registration_type'] === 'unregistered') {
            if (empty($attributes['pan_no'])) {
                return response()->json([
                    'title' => 'Validation Error',
                    'sub-title' => 'PAN Number is required for unregistered retailers',
                    'success' => false,
                    'errors' => ['pan_no' => ['PAN Number is required for unregistered retailers']],
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $retailer = new Retailer($attributes);
            $retailer->created_by = auth()->id();
            $request->company->retailers()->save($retailer);

            // Handle image upload to Vultr after retailer is created
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $imageName = "shop_" . time() . '_' . uniqid() . '.' . $extension;
                $imagePath = "trumac/retailers/{$retailer->id}/{$imageName}";

                Storage::disk('vultr')->put($imagePath, file_get_contents($file), 'public');
                $retailer->update(['image' => $imagePath]);
            }

            // Create PJP change request if employee_id and day_of_week are provided
            // Retailer won't be directly added to PJP - needs approval first
            if ($request->filled('employee_id') && $request->filled('day_of_week')) {
                // Get employee name
                $employee = \App\Models\User::find($request->employee_id);
                $employeeName = $employee ? ($employee->first_name . ' ' . $employee->last_name) : 'Unknown Employee';

                // Determine franchise_id
                $franchiseId = $attributes['franchise_id'] ?? null;
                if (!$franchiseId && !empty($attributes['company_godown_id'])) {
                    $companyGodown = \App\Models\CompanyGodown::find($attributes['company_godown_id']);
                    $franchiseId = $companyGodown->franchise_id ?? null;
                }

                // Find or create PJP for this employee and day
                // Unique constraint is on (employee_id, day_of_week, is_deleted)
                $pjp = Pjp::firstOrCreate(
                    [
                        'company_id' => $request->company->id,
                        'employee_id' => $request->employee_id,
                        'day_of_week' => $request->day_of_week,
                        'is_deleted' => false,
                    ],
                    [
                        'franchise_id' => $franchiseId,
                        'name' => Pjp::getDayName($request->day_of_week) . ' - ' . $employeeName,
                        'notes' => 'Weekly visit schedule for ' . Pjp::getDayName($request->day_of_week),
                        'is_active' => true,
                    ]
                );

                // Create a PJP change request (pending approval) instead of direct add
                // Calculate next occurrence of the selected day (at least tomorrow)
                $dayName = strtolower(Pjp::getDayName($request->day_of_week));
                $nextDate = \Carbon\Carbon::parse("next {$dayName}");
                if ($nextDate->isToday()) {
                    $nextDate->addWeek();
                }

                \App\Models\PjpChange::create([
                    'company_id' => $request->company->id,
                    'pjp_id' => $pjp->id,
                    'retailer_id' => $retailer->id,
                    'action' => \App\Models\PjpChange::ACTION_ADD,
                    'date' => $nextDate->toDateString(),
                    'reason' => 'New retailer added by ' . auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    'status' => \App\Models\PjpChange::STATUS_PENDING,
                    'requested_by' => auth()->id(),
                ]);
            }

            // Notify Super Admin and Admin about new retailer
            $adminUsers = User::whereHas('roles', function ($q) {
                $q->whereIn('roles.id', [1, 2]); // SUPER ADMIN, ADMIN
            })
                ->whereHas('companies', function ($q) use ($request) {
                    $q->where('companies.id', $request->company->id);
                })
                ->pluck('id')
                ->toArray();

            if (!empty($adminUsers)) {
                $creatorName = auth()->user()->first_name . ' ' . auth()->user()->last_name;
                Notification::sendToMany(
                    $adminUsers,
                    'New Retailer: ' . ($retailer->shop_name ?? $retailer->name),
                    'New retailer "' . ($retailer->shop_name ?? $retailer->name) . '" created by ' . $creatorName,
                    'retailer',
                    ['retailer_id' => $retailer->id, 'action' => 'created'],
                    $request->company->id
                );
            }

            DB::commit();

            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Created Successfully',
                'success' => true,
                'data' => $retailer->load(['franchise', 'companyGodown', 'location']),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $retailer = Retailer::where('id', $decryptedID)
            ->with(['franchise', 'companyGodown', 'location', 'ratings.ratedBy'])
            ->first();

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
                'data' => null,
            ], 404);
        }

        return response()->json([
            'title' => 'Retailer',
            'sub-title' => 'Retailer Data Fetched Successfully',
            'success' => true,
            'data' => $retailer,
        ], 200);
    }

    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $retailer = Retailer::where('id', $decryptedID)->first();

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
                'data' => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name' => 'required|string|max:100',
            'shop_name' => 'nullable|string|max:150',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'franchise_id' => 'nullable|exists:franchises,id',
            'company_godown_id' => 'nullable|exists:company_godowns,id',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'gst_no' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable',
        ]);

        // Handle image upload to Vultr
        if ($request->hasFile('image')) {
            // Delete old image from Vultr if exists
            if ($retailer->image) {
                Storage::disk('vultr')->delete($retailer->image);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $imageName = "shop_" . time() . '_' . uniqid() . '.' . $extension;
            $imagePath = "trumac/retailers/{$retailer->id}/{$imageName}";

            Storage::disk('vultr')->put($imagePath, file_get_contents($file), 'public');
            $attributes['image'] = $imagePath;
        }

        $retailer->update($attributes);

        return response()->json([
            'title' => 'Retailer',
            'sub-title' => 'Retailer Updated Successfully',
            'success' => true,
            'data' => $retailer->load(['franchise', 'companyGodown', 'location']),
        ], 200);
    }

    public function clear(Request $request)
    {
        $id = $request->id;
        $retailer = Retailer::find($id);

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
            ], 404);
        }

        $retailer->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Retailer',
            'sub-title' => 'Retailer Deleted Successfully',
            'success' => true,
            'data' => $retailer,
        ], 200);
    }

    public function restore(Request $request)
    {
        $id = $request->id;
        $retailer = Retailer::find($id);

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
            ], 404);
        }

        $retailer->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'Retailer',
            'sub-title' => 'Retailer Restored Successfully',
            'success' => true,
            'data' => $retailer,
        ], 200);
    }

    /**
     * Toggle the flagged status of a retailer
     */
    public function toggleFlag(Request $request)
    {
        $retailer = Retailer::find($request->id);

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
            ], 404);
        }

        $retailer->update(['is_flagged' => !$retailer->is_flagged]);

        return response()->json([
            'title' => 'Retailer',
            'sub-title' => $retailer->is_flagged ? 'Retailer Flagged' : 'Retailer Unflagged',
            'success' => true,
            'data' => $retailer,
        ], 200);
    }

    public function createLogin(Request $request)
    {
        $retailer = Retailer::find($request->id);

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
            ], 404);
        }

        // Check if login already exists
        $existingUser = User::where('retailer_id', $retailer->id)->first();
        if ($existingUser) {
            return response()->json([
                'title' => 'Login Already Exists',
                'sub-title' => 'This retailer already has a login account',
                'success' => false,
                'data' => [
                    'user_name' => $existingUser->user_name,
                    'email' => $existingUser->email,
                    'phone' => $existingUser->phone,
                ],
            ], 422);
        }

        // Find Retailer position
        $retailerPosition = Position::where('name', 'Retailer')
            ->where('company_id', $request->company->id)
            ->first();

        if (!$retailerPosition) {
            return response()->json([
                'title' => 'Error',
                'sub-title' => 'Retailer position not found in system',
                'success' => false,
            ], 422);
        }

        // Generate password
        $plainPassword = 123456;

        // Split name into first/last
        $nameParts = explode(' ', trim($retailer->name));
        $lastName = count($nameParts) > 1 ? array_pop($nameParts) : '';
        $firstName = implode(' ', $nameParts);

        $user = new User([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_name' => $retailer->name,
            'email' => $retailer->email,
            'phone' => $retailer->phone,
            'password' => bcrypt($plainPassword),
            'soft_password' => $plainPassword,
            'position_id' => $retailerPosition->id,
            'retailer_id' => $retailer->id,
            'is_active' => true,
        ]);

        $user->referral_code = $user->generateReferralCode();
        $user->save();

        $user->assignRole($retailerPosition->role_id);
        $user->assignCompany($request->company->id);

        // Send credentials email if retailer has email
        $emailSent = false;
        if ($retailer->email) {
            try {
                Mail::to($retailer->email)->send(new RetailerLoginMail(
                    retailerName: $retailer->name,
                    shopName: $retailer->shop_name ?? $retailer->name,
                    phone: $retailer->phone ?? '',
                    email: $retailer->email,
                    password: $plainPassword,
                ));
                $emailSent = true;
            } catch (\Exception $e) {
                // Email failure should not block login creation
            }
        }

        return response()->json([
            'title' => 'Login Created',
            'sub-title' => 'Retailer login account created successfully',
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $plainPassword,
                'email_sent' => $emailSent,
            ],
        ], 200);
    }

    /**
     * Get average rating for the retailer
     */
    public function getRating(Request $request)
    {
        $retailer = Retailer::find($request->id);

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
            ], 404);
        }

        // Calculate average rating
        $averageRating = $retailer->ratings()->avg('rating');
        $totalRatings = $retailer->ratings()->count();

        return response()->json([
            'title' => 'Retailer Rating',
            'sub-title' => 'Rating Fetched Successfully',
            'success' => true,
            'data' => [
                'rating' => $averageRating ? round($averageRating, 2) : null,
                'average_rating' => $averageRating ? round($averageRating, 2) : null,
                'total_ratings' => $totalRatings,
            ],
        ], 200);
    }

    /**
     * Add a rating for the retailer
     */
    public function addRating(Request $request)
    {
        $attributes = $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string',
        ]);

        $retailer = Retailer::find($request->id);

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer',
                'sub-title' => 'Retailer Not Found',
                'success' => false,
            ], 404);
        }

        $rating = RetailerRating::create([
            'retailer_id' => $retailer->id,
            'rated_by' => auth()->id(),
            'rating' => $attributes['rating'],
            'comment' => $attributes['comment'] ?? null,
        ]);

        return response()->json([
            'title' => 'Retailer Rating',
            'sub-title' => 'Rating Added Successfully',
            'success' => true,
            'data' => $retailer->fresh(['ratings']),
        ], 200);
    }

    /**
     * Get flagged retailers
     */
    public function flagged(Request $request)
    {
        $retailers = $request->company->allRetailers()
            ->where('is_flagged', true)
            ->with(['franchise', 'companyGodown', 'location'])
            ->latest()
            ->get();

        return response()->json([
            'title' => 'Flagged Retailers',
            'sub-title' => 'Flagged Retailers Fetched Successfully',
            'success' => true,
            'count' => $retailers->count(),
            'data' => $retailers,
        ], 200);
    }
}
