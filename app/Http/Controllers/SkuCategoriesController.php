<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\SkuCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SkuCategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/sku_categories",
     *     tags={"SKU Category"},
     *     summary="Get all SKU Categories",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedSkuCategories()
            : $request->company->allSkuCategories();

        $query = Utility::prepareSearchQuery($query, $request, new SkuCategory());

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $query->with(['parent', 'children']);
        $query->orderBy('id', 'desc');
        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->get();
        }

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/sku_categories/tree",
     *     tags={"SKU Category"},
     *     summary="Get SKU Categories as tree structure",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function tree(Request $request)
    {
        $categories = $request->company->allSkuCategories()
            ->whereNull('parent_id')
            ->with(['children.children'])
            ->get();

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category tree Fetched Successfully',
            'success'   => true,
            'data'      => $categories,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/sku_categories",
     *     tags={"SKU Category"},
     *     summary="Create a new SKU Category",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'parent_id'   => 'nullable|exists:sku_categories,id',
            'is_active'   => 'nullable|boolean',
        ]);

        $category = new SkuCategory($attributes);
        $request->company->sku_categories()->save($category);

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category Created Successfully',
            'success'   => true,
            'data'      => $category->load(['parent', 'children']),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/sku_categories/{id}",
     *     tags={"SKU Category"},
     *     summary="Get a SKU Category by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $category = SkuCategory::where('id', $decryptedID)
            ->with(['parent', 'children', 'skus'])
            ->first();

        if (!$category) {
            return response()->json([
                'title'     => 'SKU Category',
                'sub-title' => 'SKU Category Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category Data Fetched Successfully',
            'success'   => true,
            'data'      => $category,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/sku_categories/{id}",
     *     tags={"SKU Category"},
     *     summary="Update a SKU Category by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $category = SkuCategory::where('id', $decryptedID)->first();

        if (!$category) {
            return response()->json([
                'title'     => 'SKU Category',
                'sub-title' => 'SKU Category Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'parent_id'   => 'nullable|exists:sku_categories,id',
            'is_active'   => 'nullable|boolean',
        ]);

        // Prevent setting self as parent
        if (isset($attributes['parent_id']) && $attributes['parent_id'] == $decryptedID) {
            return response()->json([
                'title'     => 'SKU Category',
                'sub-title' => 'Category cannot be its own parent',
                'success'   => false,
                'errors'    => ['parent_id' => ['Category cannot be its own parent']],
            ], 422);
        }

        $category->update($attributes);

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category Updated Successfully',
            'success'   => true,
            'data'      => $category->load(['parent', 'children']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/sku_categories/delete/{id}",
     *     tags={"SKU Category"},
     *     summary="Soft delete a SKU Category",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request)
    {
        $id = $request->id;
        $category = SkuCategory::find($id);

        if (!$category) {
            return response()->json([
                'title'     => 'SKU Category',
                'sub-title' => 'SKU Category Not Found',
                'success'   => false,
            ], 404);
        }

        $category->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category Deleted Successfully',
            'success'   => true,
            'data'      => $category,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/sku_categories/restore/{id}",
     *     tags={"SKU Category"},
     *     summary="Restore a soft deleted SKU Category",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function restore(Request $request)
    {
        $id = $request->id;
        $category = SkuCategory::find($id);

        if (!$category) {
            return response()->json([
                'title'     => 'SKU Category',
                'sub-title' => 'SKU Category Not Found',
                'success'   => false,
            ], 404);
        }

        $category->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'SKU Category',
            'sub-title' => 'SKU Category Restored Successfully',
            'success'   => true,
            'data'      => $category,
        ], 200);
    }
}
