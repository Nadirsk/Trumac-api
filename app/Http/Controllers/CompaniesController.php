<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompaniesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'allowed_urls'])->except('simpleShow');
        $this->middleware('decrypt_id')->only(['show', 'simpleShow', 'update', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/companies",
     *     tags={"Company"},
     *     summary="Get all Companies",
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
    public function index(Request $request)
    {
        try {
            $query = Company::query();
            $query = Utility::prepareSearchQuery($query, $request, new Company());
            $query->orderBy('id', 'desc');
            $companies = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Company',
                'sub-title' => 'Company listing Fetched Successfully',
                'success' => true,
                'data' => $companies,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     tags={"Company"},
     *     summary="Create a new Companies",
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email|max:100|unique:companies,email',
            'phone' => 'required',
            'address' => 'required',
        ]);

        $company_data = $request->all();
        $company = Company::create($company_data);

        if ($company) {
            $this->createAdmin($company);
            $this->createModules($company);
            $this->createPermissions($company);
        }

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Data Stored Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{id}",
     *     tags={"Company"},
     *     summary="Get a Company by ID",
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
    /**
     * @OA\Get(
     *     path="/api/companies/{company}",
     *     tags={"Company"},
     *     summary="Get all {company}",
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
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $company = Company::where('id', $decryptedID)->first();

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Data Fetched Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/get_company/{id}",
     *     tags={"Get_company"},
     *     summary="Get a Get_company by ID",
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
    public function simpleShow(Request $request)
    {
        $decryptedID = $request->id;
        $company = Company::where('id', $decryptedID)->first();

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Data Fetched Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/companies/{id}",
     *     tags={"Company"},
     *     summary="Update a Company by ID",
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
    /**
     * @OA\Put(
     *     path="/api/companies/{company}",
     *     tags={"Company"},
     *     summary="Update a {company} by ID",
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
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|max:100',
            // 'phone'   => 'required|digits:13',
            'phone' => 'required|min:13|max:13',
            'address' => 'required',
        ]);

        $decryptedID = $request->id;
        $company = Company::where('id', $decryptedID)->first();
        $company_data = $request->all();
        $company->update($company_data);

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Data Updated Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/companies/delete/{id}",
     *     tags={"Company"},
     *     summary="Create a new Delete",
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
    public function clear(Request $request)
    {
        $id = $request->id;
        $company = Company::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Data Deleted Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/companies/restore/{id}",
     *     tags={"Company"},
     *     summary="Create a new Restore",
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
    public function restore(Request $request)
    {
        $id = $request->id;
        $company = Company::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Data Restored Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }

    public function createModules(Company $company)
    {
        $moduleArray = [
            ['name' => 'COMPANIES'],
            ['name' => 'USERS'],
            ['name' => 'POSITIONS'],
            ['name' => 'PERMISSIONS'],
            ['name' => 'MODULES'],
            // ['name' => 'OUTLETS'],
            // ['name' => 'PJPS'],
        ];

        foreach ($moduleArray as $module) {
            // Check if the module already exists in the company's modules
            $DBmodule = $company->modules()->where('name', $module['name'])->first();

            // If the module doesn't exist, create and associate it with the company
            if (!$DBmodule) {
                $company->modules()->create($module);
            }
        }

        return response()->json([
            'title' => 'Company',
            'sub-title' => 'Company Modules Added Successfully',
            'success' => true,
            'data' => $company,
        ], 200);
    }
}









