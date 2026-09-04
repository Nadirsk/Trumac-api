<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sku;
use App\Models\SkuCategory;
use App\Models\SkuImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadsController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/companies/upload_logo_path",
     *     tags={"Company"},
     *     summary="Create a new Upload_logo_path",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function uploadCompanyLogoPath(Request $request, $id = null)
    {
        // Support both route parameter {id} and request body id
        $companyId = $id ?? $request->id;

        // Support both 'logo_path' and 'file' field names
        $fileField = $request->hasFile('logo_path') ? 'logo_path' : ($request->hasFile('file') ? 'file' : null);

        if (!$fileField) {
            return response()->json([
                'message' => 'Logo file is required',
                'success' => false
            ], 400);
        }

        if (!$companyId) {
            return response()->json([
                'message' => 'Company ID is required',
                'success' => false
            ], 400);
        }

        if ($request->hasFile($fileField)) {
            $file = $request->file($fileField);
            $extension = $file->getClientOriginalExtension();

            $name = $request->filename ?? "photo_" . time() . '.' . $extension;
            $logo_path = "trumac/company/logo/{$companyId}/{$name}";
            // project_name/model_name/sample_img/1/photo_1722008308.jpg

            // Store the file in DigitalOcean Spaces (or another cloud storage)
            Storage::disk('vultr')->put($logo_path, file_get_contents($file), 'public');
            // Storage::disk('s3')->put($logo_path, file_get_contents($file));

            // Update the company record
            $company = Company::findOrFail($companyId);
            $company->update(['logo_path' => $logo_path]);

            return response()->json([
                'title'     => 'Company',
                'sub-title' => 'Uploaded Company Logo Successfully',
                'success'   => true,
                'data'      => ['logo_path' => $logo_path],
            ]);
        }

        return response()->json([
            'message' => 'No file uploaded',
            'success' => false
        ], 400);
    }


    /**
     * @OA\Post(
     *     path="/api/users/upload_image_path",
     *     tags={"User"},
     *     summary="Create a new Upload_image_path",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function uploadUserImage(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'image_path' => 'required'
        ]);

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $extension = $file->getClientOriginalExtension();

            $name = $request->filename ?? "photo_" . time() . '.' . $extension;
            $image_path = "trumac/user/img/{$request->id}/{$name}";

            Storage::disk('vultr')->put($image_path, file_get_contents($file), 'public');
            // Storage::disk('s3')->put($imagePath, file_get_contents($file));

            $user = User::findOrFail($request->id);
            $user->update(['image_path' => $image_path]);

            return response()->json([
                'title'     => 'User',
                'sub-title' => 'Uploaded User Logo Successfully',
                'success'   => true,
                'data'      => ['image_path' => $image_path],
            ]);
        }

        return response()->json([
            'message' => 'No file uploaded',
            'success' => false
        ], 400);
    }
    /**
     * @OA\Post(
     *     path="/api/sku_categories/upload_image",
     *     tags={"SKU Category"},
     *     summary="Upload SKU Category image",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=400, description="Bad Request")
     * )
     */
    public function uploadSkuCategoryImage(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'image_path' => 'required|image|max:2048'
        ]);

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $extension = $file->getClientOriginalExtension();

            $name = $request->filename ?? "category_" . time() . '.' . $extension;
            $image_path = "trumac/sku_categories/{$request->id}/{$name}";

            Storage::disk('vultr')->put($image_path, file_get_contents($file), 'public');

            $skuCategory = SkuCategory::findOrFail($request->id);
            $skuCategory->update(['image_path' => $image_path]);

            return response()->json([
                'title'     => 'SKU Category',
                'sub-title' => 'Uploaded SKU Category Image Successfully',
                'success'   => true,
                'data'      => ['image_path' => $image_path],
            ]);
        }

        return response()->json([
            'message' => 'No file uploaded',
            'success' => false
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/skus/upload_image",
     *     tags={"SKU"},
     *     summary="Upload SKU image",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=400, description="Bad Request")
     * )
     */
    public function uploadSkuImage(Request $request)
    {
        $request->validate([
            'sku_id' => 'required_without:id',
            'id' => 'required_without:sku_id',
            'image_path' => 'required|image|max:5120',
            'is_primary' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Support both 'id' and 'sku_id' field names
        $skuId = $request->sku_id ?? $request->id;

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $extension = $file->getClientOriginalExtension();

            $name = $request->filename ?? "sku_" . time() . '_' . uniqid() . '.' . $extension;
            $image_path = "trumac/skus/{$skuId}/{$name}";

            Storage::disk('vultr')->put($image_path, file_get_contents($file), 'public');

            $sku = Sku::findOrFail($skuId);

            // Check if this is the first image (make it primary) or use provided value
            $existingImages = SkuImage::where('sku_id', $sku->id)->count();
            $isPrimary = $request->has('is_primary') ? (bool) $request->is_primary : ($existingImages === 0);
            $sortOrder = $request->sort_order ?? ($existingImages + 1);

            // If this image is primary, unset other primary images
            if ($isPrimary) {
                SkuImage::where('sku_id', $sku->id)->update(['is_primary' => false]);
            }

            // Create SKU image record
            $skuImage = SkuImage::create([
                'sku_id' => $sku->id,
                'image_path' => $image_path,
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
            ]);

            return response()->json([
                'title'     => 'SKU',
                'sub-title' => 'Uploaded SKU Image Successfully',
                'success'   => true,
                'data'      => $skuImage,
            ]);
        }

        return response()->json([
            'message' => 'No file uploaded',
            'success' => false
        ], 400);
    }
}



