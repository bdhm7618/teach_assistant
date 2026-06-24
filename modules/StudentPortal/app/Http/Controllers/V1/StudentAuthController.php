<?php

namespace Modules\StudentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Modules\Student\App\Models\Student;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\StudentPortal\App\Events\StudentRegistered;
use Modules\StudentPortal\App\Events\StudentPasswordResetRequested;
use Modules\StudentPortal\App\Http\Resources\V1\StudentProfileResource;

/**
 * @OA\Tag(name="Student Auth", description="Student portal authentication")
 */
class StudentAuthController extends Controller
{
    public function __construct(protected OtpRepository $otpRepository) {}

    // =========================================================================
    // Public — no auth required (channel-scoped via identify.tenant middleware)
    // =========================================================================

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/auth/login",
     *     summary="Student login with email/phone + password",
     *     tags={"Student Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="JWT token returned"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account inactive or email not verified"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required_without:phone|string|email',
            'phone'    => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        $channelId = app('current_channel_id');

        $student = $request->filled('phone')
            ? Student::where('channel_id', $channelId)->where('phone', $request->phone)->first()
            : Student::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $student) {
            return errorResponse(__('studentportal::app.not_found'), null, 404);
        }
        if (! $student->email_verified_at) {
            return errorResponse(__('channel::app.user.not_verified'), null, 403);
        }
        if (! Hash::check($request->password, $student->password)) {
            return errorResponse(__('studentportal::app.invalid_credentials'), null, 401);
        }
        if (! $student->isActive()) {
            return errorResponse(__('studentportal::app.account_inactive'), null, 403);
        }

        $token = JWTAuth::guard('student')->fromUser($student);

        return successResponse(
            array_merge((new StudentProfileResource($student))->toArray($request), ['token' => $token]),
            __('studentportal::app.login_success')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/auth/forget-password",
     *     summary="Request password reset OTP",
     *     tags={"Student Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP sent"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $channelId = app('current_channel_id');
        $student   = Student::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $student) {
            return errorResponse(__('studentportal::app.not_found'), null, 404);
        }

        event(new StudentPasswordResetRequested($student));

        return successResponse(null, __('studentportal::app.password_reset_otp_sent'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/auth/reset-password",
     *     summary="Reset password using OTP",
     *     tags={"Student Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","otp","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="otp", type="string"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset — JWT token returned"),
     *     @OA\Response(response=422, description="Invalid or expired OTP")
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $channelId = app('current_channel_id');
        $student   = Student::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $student) {
            return errorResponse(__('studentportal::app.not_found'), null, 404);
        }

        $otpRecord = $this->otpRepository->getLatestUnverified($student, 'password_reset');

        if (! $otpRecord || $otpRecord->code !== $request->otp) {
            return errorResponse(__('studentportal::app.otp_invalid'), null, 422);
        }
        if ($otpRecord->isExpired()) {
            return errorResponse(__('studentportal::app.otp_invalid'), null, 422);
        }

        DB::beginTransaction();
        try {
            $student->update(['password' => $request->password]);
            $this->otpRepository->markAsVerified($otpRecord);
            $token = JWTAuth::guard('student')->fromUser($student);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(__('studentportal::app.operation_failed'), $e);
        }

        return successResponse(
            array_merge((new StudentProfileResource($student))->toArray($request), ['token' => $token]),
            __('studentportal::app.password_reset_success')
        );
    }

    // =========================================================================
    // Protected — auth:student required
    // =========================================================================

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/auth/me",
     *     summary="Get authenticated student profile",
     *     tags={"Student Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Student profile"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $student = auth('student')->user();
        return successResponse(new StudentProfileResource($student), __('studentportal::app.show_success'));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/student/auth/me",
     *     summary="Update student profile (name, phone, image)",
     *     tags={"Student Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="image", type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'image' => 'sometimes|image|max:2048',
        ]);

        $student = auth('student')->user();

        $data = $request->only(['name', 'phone']);

        if ($request->hasFile('image')) {
            $path          = $request->file('image')->store("students/{$student->channel_id}", 'public');
            $data['image'] = $path;
        }

        $student->update($data);

        return successResponse(new StudentProfileResource($student->fresh()), __('studentportal::app.profile_updated'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/auth/change-password",
     *     summary="Change student password",
     *     tags={"Student Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password changed"),
     *     @OA\Response(response=422, description="Current password incorrect")
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $student = auth('student')->user();

        if (! Hash::check($request->current_password, $student->password)) {
            return errorResponse(__('studentportal::app.invalid_credentials'), null, 422);
        }

        $student->update(['password' => $request->password]);

        return successResponse(null, __('studentportal::app.password_changed'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/auth/logout",
     *     summary="Logout student",
     *     tags={"Student Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Logged out"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return successResponse(null, __('studentportal::app.logout_success'));
        } catch (\Throwable $e) {
            return errorResponse(__('studentportal::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/auth/refresh",
     *     summary="Refresh student JWT token",
     *     tags={"Student Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="New token"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function refreshToken(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return successResponse(['token' => $token], __('studentportal::app.token_refreshed'));
        } catch (\Throwable $e) {
            return errorResponse(__('studentportal::app.token_expired'), null, 401);
        }
    }
}
