<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum'])->only('me');
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Register"},
     *     summary="Create a new Register",
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
    public function register(Request $request)
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'soft_password' => $request->password,
        ]);

        $user->assignRole($request->role_id);
        $user->assignCompany(1);

        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Registered Successfully',
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Login"},
     *     summary="Create a new Login",
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
    public function login(Request $request)
    {
        // ✅ Validate input
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // ❌ If authentication fails
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'title' => 'Unauthorized',
                'sub-title' => 'Invalid credentials',
                'success' => false,
                'data' => null,
                'token' => null,
            ], 401);
        }

        // ✅ Authentication successful
        $user = Auth::user();

        // 🔐 Create Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;

        // Save FCM token + api_token together
        $updateData = ['api_token' => $token];
        if ($request->fcm_token != null) {
            $updateData['fcm_token'] = $request->fcm_token;
        }
        $user->update($updateData);
        return response()->json([
            'title' => 'User',
            'sub-title' => 'User Logged In Successfully',
            'success' => true,
            'data' => $user,
            'token' => $token,
            'currentAndroidVersionFromApi' => '1.0.0'
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/forgot_password",
     *     tags={"Forgot_password"},
     *     summary="Create a new Forgot_password",
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
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if ($request->filled('password') && $request->filled('soft_password')) {
            // Scenario 1: Reset Password
            $request->validate([
                'password' => 'required|confirmed',
                'soft_password' => 'required',
            ], ['password.confirmed' => 'Password confirmation does not match.',]);

            $user->update([
                'password' => Hash::make($request->password),
                'soft_password' => $request->soft_password,
            ]);
        } elseif ($request->filled('otp')) {
            // Scenario 2: Verify OTP
            $request->validate(
                ['otp' => 'required|exists:users,otp'],
                ['otp.exists' => 'Entered OTP is invalid.']
            );
        } else {
            // Scenario 3: Generate OTP and Send Email
            $user->update([
                'otp' => random_int(1000, 9999),
            ]);

            // Send email using queue for better performance
            Mail::to($user->email)
                ->cc('support@techieshark.com')
                ->queue(new ForgotPasswordMail($user));
        }

        return response()->json([
            'title' => 'User',
            'sub-title' => 'Updated New Password Successfully',
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Me"},
     *     summary="Get all Me",
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
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'success' => false
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Load relationships
        $user->load(['roles', 'companies', 'position.permissions.module', 'companyGodown', 'warehouse', 'franchise', 'retailer']);

        // Build permissions array in format: MODULE_NAME.PERMISSION_NAME
        $permissions = [];
        if ($user->position && $user->position->permissions) {
            foreach ($user->position->permissions as $permission) {
                if ($permission->module) {
                    $permissions[] = $permission->module->name . '.' . $permission->name;
                }
            }
        }

        $version = Version::latest()->first();

        return response()->json([
            'data' => $user,
            'permissions' => $permissions,
            'version' => $version,
            'success' => true
        ], 200);
    }

    public function logout(Request $request)
    {
        // Clear FCM token on logout
        $request->user()->update(['fcm_token' => null,'api_token' => null]);
        // $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    /**
     * Update FCM token (called when token refreshes on mobile app)
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated',
        ], 200);
    }
}




