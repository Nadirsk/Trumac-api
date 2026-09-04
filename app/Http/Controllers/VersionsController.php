<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Version;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VersionsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Version::query();
            $query = Utility::prepareSearchQuery($query, $request, new Version());
            $versions = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Version',
                'sub-title' => 'Version Data Fetched Successfully',
                'success' => true,
                'data' => $versions,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'version'  =>  'required'
        ]);

        $version = Version::create($request->all());

        return response()->json([
            'data'  =>  $version
        ], 201);
    }
}
