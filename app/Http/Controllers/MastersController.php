<?php

namespace App\Http\Controllers;

use App\Models\Value;
use App\Models\ValueList;
use Illuminate\Http\Request;

class MastersController extends Controller
{
    public function __construct()
    {
        $this->middleware(['company']);
    }

    /**
     * @OA\Get(
     *     path="/api/masters",
     *     tags={"Master"},
     *     summary="Get all Masters",
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
    public function masters(Request $request)
    {
        $page = $request->input('context');

        $mastersMapping = [
            'user' => ['positions', 'gender', 'roles', 'warehouses', 'company_godowns', 'franchises'],
            'value_list' => ['values'],
            'permissions' => ['modules'],
            'positions' => ['modules', 'permissions'],
            'driver_documents' => ['doc_types'],
            'clinics' => ['states'],
            'labs' => ['states'],
            'franchises/companies/retailers' => ['retailers','franchises', 'company_godowns'],
            'it_employees' => ['it_employees'],
            // Add other pages here with their required masters...
        ];

        $mastersData = [];

        if (!$page || !array_key_exists($page, $mastersMapping)) {
            return response()->json([
                'error' => 'Invalid or missing context',
                'message' => 'The provided context is either missing or does not match any of the allowed contexts.',
            ], 400);
        }

        // If the context exists in the mapping, fetch the required masters
        if (array_key_exists($page, $mastersMapping)) {
            foreach ($mastersMapping[$page] as $master) {
                switch ($master) {
                    case 'modules':
                        $mastersData['modules'] = app(ModulesController::class)->index($request)->getData()->data;
                        break;

                    case 'permissions':
                        $mastersData['permissions'] = app(PermissionsController::class)->index($request)->getData()->data;
                        break;

                    case 'positions':
                        $mastersData['positions'] = app(PositionsController::class)->index($request)->getData()->data;
                        break;

                    case 'values':
                        $mastersData['values'] = app(ValuesController::class)->index($request)->getData()->data;
                        break;

                    case 'gender':
                        $gender_value = Value::where('name', 'GENDER')->where('company_id', $request->company->id)->first();

                        $mastersData['gender'] = $gender_value
                            ? ValueList::where('value_id', $gender_value->id)->where('company_id', $request->company->id)->get()
                            : [];
                        break;

                    case 'doc_types':
                        $doc_types_value = Value::where('name', 'DRIVER DOCUMENTS')->where('company_id', $request->company->id)->first();

                        $mastersData['doc_types'] = $doc_types_value
                            ? ValueList::where('value_id', $doc_types_value->id)->where('company_id', $request->company->id)->get()
                            : [];
                        break;
                    case 'states':
                        $states_value = Value::where('name', 'STATE')->where('company_id', $request->company->id)->first();

                        $mastersData['states'] = $states_value
                            ? ValueList::where('value_id', $states_value->id)->where('company_id', $request->company->id)->get()
                            : [];
                        break;

                    case 'users':
                        $mastersData['users'] = app(UsersController::class)->index($request)->getData()->data;
                        break;
                    case 'franchises':
                        $mastersData['franchises'] = app(FranchisesController::class)->index($request)->getData()->data;
                        break;
                    case 'company_godowns':
                        $mastersData['company_godowns'] = app(CompanyGodownsController::class)->index($request)->getData()->data;
                        break;
                    case 'retailers':
                        $mastersData['retailers'] = app(RetailersController::class)->index($request)->getData()->data;
                        break;
                    case 'it_employees':
                        // Fetch IT Employee position
                        $itEmployeePosition = \App\Models\Position::where('name', 'IT Employee')
                            ->where('company_id', $request->company->id)
                            ->first();

                        if ($itEmployeePosition) {
                            // Fetch users with IT Employee position
                            $itEmployees = \App\Models\User::where('position_id', $itEmployeePosition->id)
                                ->where('deleted_at', null)
                                ->get();

                            $mastersData['it_employees'] = $itEmployees->map(function ($emp) {
                                return [
                                    'id' => $emp->id,
                                    'first_name' => $emp->first_name,
                                    'last_name' => $emp->last_name,
                                    'name' => $emp->name,
                                    'email' => $emp->email,
                                    'phone' => $emp->phone,
                                    'full_name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: $emp->name ?: 'Unnamed Employee',
                                ];
                            });
                        } else {
                            $mastersData['it_employees'] = [];
                        }
                        break;

                    case 'roles':
                        $mastersData['roles'] = app(RolesController::class)->index($request)->getData()->data;
                        break;

                    case 'warehouses':
                        $mastersData['warehouses'] = app(WarehousesController::class)->index($request)->getData()->data;
                        break;
                }
            }
        }

        return response()->json($mastersData, 200);
    }
}

