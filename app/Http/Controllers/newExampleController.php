<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\newExample;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class newExampleController extends Controller
{
    // public function __construct()
    // {
    //     $this->authorizeResource(example::class, null, ['except' => []]);
    // }
    public function index(Request $request)
    {
        try {
            $query = newExample::query();
            $query = Utility::prepareSearchQuery($query, $request, new newExample());
            $examples = Utility::getSearchRequestQueryResults($request, $query);

            return $examples;
        } catch (Exception $th) {
            //throw $th;
            throw ValidationException::withMessages(['error' => $th->getMessage()]);
        }
    }
    public function store(Request $request)
    {
        $attributes = $request->validated();
        try {
            $example = newExample::create($attributes);
            return $example;
        } catch (Exception $th) {
            //throw $th;
            throw ValidationException::withMessages(['error' => $th->getMessage()]);
        }
    }
    public function show(Request $request, newExample $example)
    {
        return $example;
    }

    public function update(Request $request, newExample $example)
    {
        $attributes = $request->validated();
        $example->update($attributes);
        return $example;
    }

    public function destroy(Request $request, newExample $example)
    {
        $example->delete();
        return response()->json(['message' => 'example deleted successfully'], 200);
    }
}