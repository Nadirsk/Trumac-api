<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/locations",
     *     tags={"Location"},
     *     summary="Get all Locations",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedLocations()
            : $request->company->allLocations();

        $query = Utility::prepareSearchQuery($query, $request, new Location());

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhere('state', 'LIKE', "%{$search}%")
                  ->orWhere('pincode', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('id', 'desc');
        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->get();
        }

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Location listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/locations/cities",
     *     tags={"Location"},
     *     summary="Get distinct cities",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function cities(Request $request)
    {
        $cities = $request->company->allLocations()
            ->select('city')
            ->distinct()
            ->whereNotNull('city')
            ->orderBy('city')
            ->pluck('city');

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Cities Fetched Successfully',
            'success'   => true,
            'data'      => $cities,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/locations/states",
     *     tags={"Location"},
     *     summary="Get distinct states",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function states(Request $request)
    {
        $states = $request->company->allLocations()
            ->select('state')
            ->distinct()
            ->whereNotNull('state')
            ->orderBy('state')
            ->pluck('state');

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'States Fetched Successfully',
            'success'   => true,
            'data'      => $states,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/locations",
     *     tags={"Location"},
     *     summary="Create a new Location",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name'      => 'required|string|max:100',
            'address'   => 'nullable|string|max:500',
            'city'      => 'nullable|string|max:100',
            'state'     => 'nullable|string|max:100',
            'pincode'   => 'nullable|string|max:10',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'type'      => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $location = new Location($attributes);
        $request->company->locations()->save($location);

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Location Created Successfully',
            'success'   => true,
            'data'      => $location,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/locations/{id}",
     *     tags={"Location"},
     *     summary="Get a Location by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $location = Location::where('id', $decryptedID)->first();

        if (!$location) {
            return response()->json([
                'title'     => 'Location',
                'sub-title' => 'Location Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Location Data Fetched Successfully',
            'success'   => true,
            'data'      => $location,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/locations/{id}",
     *     tags={"Location"},
     *     summary="Update a Location by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $location = Location::where('id', $decryptedID)->first();

        if (!$location) {
            return response()->json([
                'title'     => 'Location',
                'sub-title' => 'Location Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name'      => 'required|string|max:100',
            'address'   => 'nullable|string|max:500',
            'city'      => 'nullable|string|max:100',
            'state'     => 'nullable|string|max:100',
            'pincode'   => 'nullable|string|max:10',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'type'      => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $location->update($attributes);

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Location Updated Successfully',
            'success'   => true,
            'data'      => $location,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/locations/delete/{id}",
     *     tags={"Location"},
     *     summary="Soft delete a Location",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request)
    {
        $id = $request->id;
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'title'     => 'Location',
                'sub-title' => 'Location Not Found',
                'success'   => false,
            ], 404);
        }

        $location->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Location Deleted Successfully',
            'success'   => true,
            'data'      => $location,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/locations/restore/{id}",
     *     tags={"Location"},
     *     summary="Restore a soft deleted Location",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function restore(Request $request)
    {
        $id = $request->id;
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'title'     => 'Location',
                'sub-title' => 'Location Not Found',
                'success'   => false,
            ], 404);
        }

        $location->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Location',
            'sub-title' => 'Location Restored Successfully',
            'success'   => true,
            'data'      => $location,
        ], 200);
    }
}
