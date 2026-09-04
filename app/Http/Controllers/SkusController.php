<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Sku;
use App\Models\SkuImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SkusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore', 'addImage']);
    }

    /**
     * @OA\Get(
     *     path="/api/skus",
     *     tags={"SKU"},
     *     summary="Get all SKUs",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedSkus()
            : $request->company->allSkus();

        $query = Utility::prepareSearchQuery($query, $request, new Sku());

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('hsn_code', 'LIKE', "%{$search}%")
                  ->orWhere('barcode', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->boolean('out_of_stock_only')) {
            $query->where('out_of_stock_threshold', '>', 0);
        }

        $query->with(['category', 'images', 'primaryImage']);
        $query->orderBy('id', 'desc');
        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->get();
        }

        return response()->json([
            'title'     => 'SKU',
            'sub-title' => 'SKU listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/skus",
     *     tags={"SKU"},
     *     summary="Create a new SKU",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'code'                   => 'nullable|string|max:50',
            'name'                   => 'required|string|max:200',
            'description'            => 'nullable|string|max:1000',
            'category_id'            => 'nullable|exists:sku_categories,id',
            'unit'                   => 'nullable|string|max:20',
            'mrp'                    => 'nullable|numeric|min:0',
            'selling_price'          => 'nullable|numeric|min:0',
            'purchase_price'         => 'nullable|numeric|min:0',
            'hsn_code'               => 'nullable|string|max:20',
            'gst_percent'            => 'nullable|numeric|min:0|max:100',
            'out_of_stock_threshold' => 'nullable|integer|min:0',
            'barcode'                => 'nullable|string|max:50',
            'is_active'              => 'nullable|boolean',
        ]);

        // Generate code if not provided
        if (empty($attributes['code'])) {
            $lastSku = $request->company->totalSkus()->orderBy('id', 'desc')->first();
            $nextId = $lastSku ? $lastSku->id + 1 : 1;
            $attributes['code'] = 'SKU' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        }

        $sku = new Sku($attributes);
        $request->company->skus()->save($sku);

        return response()->json([
            'title'     => 'SKU',
            'sub-title' => 'SKU Created Successfully',
            'success'   => true,
            'data'      => $sku->load(['category', 'images', 'primaryImage']),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/skus/{id}",
     *     tags={"SKU"},
     *     summary="Get a SKU by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $sku = Sku::where('id', $decryptedID)
            ->with(['category', 'images', 'primaryImage'])
            ->first();

        if (!$sku) {
            return response()->json([
                'title'     => 'SKU',
                'sub-title' => 'SKU Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'SKU',
            'sub-title' => 'SKU Data Fetched Successfully',
            'success'   => true,
            'data'      => $sku,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/skus/{id}",
     *     tags={"SKU"},
     *     summary="Update a SKU by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $sku = Sku::where('id', $decryptedID)->first();

        if (!$sku) {
            return response()->json([
                'title'     => 'SKU',
                'sub-title' => 'SKU Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'code'                   => 'nullable|string|max:50',
            'name'                   => 'required|string|max:200',
            'description'            => 'nullable|string|max:1000',
            'category_id'            => 'nullable|exists:sku_categories,id',
            'unit'                   => 'nullable|string|max:20',
            'mrp'                    => 'nullable|numeric|min:0',
            'selling_price'          => 'nullable|numeric|min:0',
            'purchase_price'         => 'nullable|numeric|min:0',
            'hsn_code'               => 'nullable|string|max:20',
            'gst_percent'            => 'nullable|numeric|min:0|max:100',
            'out_of_stock_threshold' => 'nullable|integer|min:0',
            'barcode'                => 'nullable|string|max:50',
            'is_active'              => 'nullable|boolean',
        ]);

        $sku->update($attributes);

        return response()->json([
            'title'     => 'SKU',
            'sub-title' => 'SKU Updated Successfully',
            'success'   => true,
            'data'      => $sku->load(['category', 'images', 'primaryImage']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/skus/delete/{id}",
     *     tags={"SKU"},
     *     summary="Soft delete a SKU",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request)
    {
        $id = $request->id;
        $sku = Sku::find($id);

        if (!$sku) {
            return response()->json([
                'title'     => 'SKU',
                'sub-title' => 'SKU Not Found',
                'success'   => false,
            ], 404);
        }

        $sku->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'SKU',
            'sub-title' => 'SKU Deleted Successfully',
            'success'   => true,
            'data'      => $sku,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/skus/restore/{id}",
     *     tags={"SKU"},
     *     summary="Restore a soft deleted SKU",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function restore(Request $request)
    {
        $id = $request->id;
        $sku = Sku::find($id);

        if (!$sku) {
            return response()->json([
                'title'     => 'SKU',
                'sub-title' => 'SKU Not Found',
                'success'   => false,
            ], 404);
        }

        $sku->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'SKU',
            'sub-title' => 'SKU Restored Successfully',
            'success'   => true,
            'data'      => $sku,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/skus/import",
     *     tags={"SKU"},
     *     summary="Import SKUs from Excel",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $rows = [];
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'csv') {
                // Parse CSV manually
                $handle = fopen($file->getRealPath(), 'r');
                $headers = fgetcsv($handle);
                $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) === count($headers)) {
                        $rows[] = array_combine($headers, $row);
                    }
                }
                fclose($handle);
            } else {
                // Parse XLSX/XLS using PhpSpreadsheet
                $spreadsheet = IOFactory::load($file->getRealPath());
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray(null, true, true, true);

                if (empty($data)) {
                    return response()->json([
                        'title'     => 'SKU Import',
                        'sub-title' => 'File is empty',
                        'success'   => false,
                    ], 422);
                }

                // First row is headers
                $headerRow = array_shift($data);
                $headers = array_map(fn($h) => strtolower(trim($h ?? '')), array_values($headerRow));

                foreach ($data as $row) {
                    $values = array_values($row);
                    if (count($values) === count($headers)) {
                        $combined = array_combine($headers, $values);
                        // Skip empty rows
                        if (!empty(array_filter($combined, fn($v) => !empty($v) && $v !== null))) {
                            $rows[] = $combined;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'title'     => 'SKU Import',
                'sub-title' => 'Failed to parse file: ' . $e->getMessage(),
                'success'   => false,
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'title'     => 'SKU Import',
                'sub-title' => 'File is empty or invalid format',
                'success'   => false,
            ], 422);
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                // Required: code, name
                if (empty($row['code']) || empty($row['name'])) {
                    $errors[] = "Row " . ($index + 2) . ": code and name are required";
                    $skipped++;
                    continue;
                }

                // Skip if SKU code already exists in this company
                $exists = Sku::where('code', $row['code'])
                    ->where('company_id', $request->company->id)
                    ->where('is_deleted', false)
                    ->exists();

                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": SKU code '{$row['code']}' already exists";
                    $skipped++;
                    continue;
                }

                // Find category by name (optional)
                $categoryId = null;
                if (!empty($row['category'])) {
                    $category = \App\Models\SkuCategory::where('name', $row['category'])
                        ->where('company_id', $request->company->id)
                        ->first();
                    $categoryId = $category?->id;
                }

                Sku::create([
                    'is_active'              => 1,
                    'is_deleted'             => 0,
                    'company_id'             => $request->company->id,
                    'category_id'            => $categoryId,
                    'code'                   => $row['code'],
                    'name'                   => $row['name'],
                    'description'            => $row['description'] ?? null,
                    'unit'                   => $row['unit'] ?? 'pcs',
                    'mrp'                    => is_numeric($row['mrp'] ?? null) ? $row['mrp'] : 0,
                    'selling_price'          => is_numeric($row['selling_price'] ?? null) ? $row['selling_price'] : 0,
                    'purchase_price'         => is_numeric($row['purchase_price'] ?? null) ? $row['purchase_price'] : 0,
                    'hsn_code'               => $row['hsn_code'] ?? null,
                    'gst_percent'            => is_numeric($row['gst_percent'] ?? null) ? $row['gst_percent'] : 0,
                    'out_of_stock_threshold' => is_numeric($row['out_of_stock_threshold'] ?? null) ? $row['out_of_stock_threshold'] : 0,
                    'barcode'                => $row['barcode'] ?? null,
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $skipped++;
            }
        }

        return response()->json([
            'title'     => 'SKU Import',
            'sub-title' => "Import completed: {$created} created, {$skipped} skipped",
            'success'   => true,
            'data'      => [
                'created' => $created,
                'skipped' => $skipped,
                'errors'  => $errors,
            ],
        ], 200);
    }

    /**
     * Download a sample XLSX template for SKU import (with auto-sized columns)
     */
    public function downloadSampleFile()
    {
        $headers = [
            'code',
            'name',
            'description',
            'category',
            'unit',
            'mrp',
            'selling_price',
            'purchase_price',
            'hsn_code',
            'gst_percent',
            'out_of_stock_threshold',
            'barcode',
        ];

        $sampleRows = [
            [
                'SKU001',
                'Sample Product 1',
                'Sample description for product 1',
                'Electronics',
                'pcs',
                1500.00,
                1200.00,
                1000.00,
                '8517',
                18,
                10,
                '1234567890123',
            ],
            [
                'SKU002',
                'Sample Product 2',
                'Sample description for product 2',
                'Grocery',
                'kg',
                250.00,
                200.00,
                150.00,
                '0901',
                5,
                20,
                '9876543210987',
            ],
        ];

        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SKU Import');

        // Helper to convert column index (1-based) to Excel column letter (A, B, ..., Z, AA, ...)
        $columnLetter = fn ($index) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);

        // Write headers (row 1)
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue($columnLetter($colIndex + 1) . '1', $header);
        }

        // Style header row: bold, white text, blue background, centered
        $lastColumnLetter = $columnLetter(count($headers));
        $headerRange = 'A1:' . $lastColumnLetter . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A237E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Set header row height
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Write sample data rows
        foreach ($sampleRows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue($columnLetter($colIndex + 1) . ($rowIndex + 2), $value);
            }
        }

        // Style data rows with borders
        $dataRange = 'A2:' . $lastColumnLetter . (1 + count($sampleRows));
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-size all columns based on content
        for ($col = 1; $col <= count($headers); $col++) {
            $sheet->getColumnDimension($columnLetter($col))->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Generate file
        $filename = 'sku_import_sample.xlsx';
        $writer = new Xlsx($spreadsheet);

        $callback = function () use ($writer) {
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/skus/{id}/images",
     *     tags={"SKU"},
     *     summary="Add image to SKU",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function addImage(Request $request)
    {
        $decryptedID = $request->id;
        $sku = Sku::find($decryptedID);

        if (!$sku) {
            return response()->json([
                'title'     => 'SKU',
                'sub-title' => 'SKU Not Found',
                'success'   => false,
            ], 404);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $path = $request->file('image')->store('skus', 'public');

        // Get the next sort order
        $maxSort = $sku->images()->max('sort_order') ?? 0;

        // Check if this is the first image (make it primary)
        $isPrimary = $sku->images()->count() === 0;

        $image = $sku->images()->create([
            'image_path'  => $path,
            'is_primary'  => $isPrimary,
            'sort_order'  => $maxSort + 1,
        ]);

        return response()->json([
            'title'     => 'SKU Image',
            'sub-title' => 'Image Added Successfully',
            'success'   => true,
            'data'      => $image,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/skus/{sku_id}/images/{id}",
     *     tags={"SKU"},
     *     summary="Delete SKU image",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteImage(Request $request, $sku_id, $id)
    {
        $image = SkuImage::where('sku_id', $sku_id)->where('id', $id)->first();

        if (!$image) {
            return response()->json([
                'title'     => 'SKU Image',
                'sub-title' => 'Image Not Found',
                'success'   => false,
            ], 404);
        }

        // Delete from storage
        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $wasPrimary = $image->is_primary;
        $skuId = $image->sku_id;

        $image->delete();

        // If deleted image was primary, make another image primary
        if ($wasPrimary) {
            $nextImage = SkuImage::where('sku_id', $skuId)->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return response()->json([
            'title'     => 'SKU Image',
            'sub-title' => 'Image Deleted Successfully',
            'success'   => true,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/skus/{sku_id}/images/{id}/primary",
     *     tags={"SKU"},
     *     summary="Set image as primary",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function setPrimaryImage(Request $request, $sku_id, $id)
    {
        $image = SkuImage::where('sku_id', $sku_id)->where('id', $id)->first();

        if (!$image) {
            return response()->json([
                'title'     => 'SKU Image',
                'sub-title' => 'Image Not Found',
                'success'   => false,
            ], 404);
        }

        // Remove primary from all other images of this SKU
        SkuImage::where('sku_id', $sku_id)->update(['is_primary' => false]);

        // Set this image as primary
        $image->update(['is_primary' => true]);

        return response()->json([
            'title'     => 'SKU Image',
            'sub-title' => 'Primary Image Set Successfully',
            'success'   => true,
            'data'      => $image,
        ], 200);
    }
}
