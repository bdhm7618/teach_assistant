<?php

namespace Modules\ParentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\ParentPortal\App\Models\ParentAccount;
use Modules\ParentPortal\App\Events\ParentRegistered;
use Modules\ParentPortal\App\Events\ParentPasswordResetRequested;
use Modules\ParentPortal\App\Http\Resources\V1\ParentProfileResource;

/**
 * @OA\Tag(name="Parent Auth", description="Parent portal authentication")
 */
class ParentAuthController extends Controller
{
    public function __construct(protected OtpRepository $otpRepository) {}

    // =========================================================================
    // Public — identify.tenant only (channel-scoped)
    // =========================================================================

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/register",
     *     summary="Parent self-registration (email verification required)",
     *     tags={"Parent Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","phone","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Registered — OTP sent to email"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $channelId = app('current_channel_id');

        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:20',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Multi-tenant uniqueness — scoped to the channel, not global.
        $exists = ParentAccount::where('channel_id', $channelId)
            ->where(fn($q) => $q->where('email', $request->email)->orWhere('phone', $request->phone))
            ->exists();

        if ($exists) {
            return errorResponse(__('parentportal::app.already_registered'), null, 422);
        }

        DB::beginTransaction();
        try {
            $parent = ParentAccount::create([
                'channel_id' => $channelId,
                'name'       => $request->name,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'password'   => $request->password,
                'status'     => 1,
            ]);

            event(new ParentRegistered($parent));
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(__('parentportal::app.operation_failed'), $e);
        }

        return successResponse(
            new ParentProfileResource($parent),
            __('parentportal::app.registered'),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/verify-email",
     *     summary="Verify parent email with OTP",
     *     tags={"Parent Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","otp"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="otp", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Verified — JWT token returned"),
     *     @OA\Response(response=422, description="Invalid/expired OTP or already verified")
     * )
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        $channelId = app('current_channel_id');
        $parent    = ParentAccount::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $parent) {
            return errorResponse(__('parentportal::app.not_found'), null, 404);
        }
        if ($parent->email_verified_at) {
            return errorResponse(__('parentportal::app.already_verified'), null, 422);
        }

        $otpRecord = $this->otpRepository->getLatestUnverified($parent, 'email_verification');

        if (! $otpRecord || $otpRecord->code !== $request->otp) {
            return errorResponse(__('parentportal::app.otp_invalid'), null, 422);
        }
        if ($otpRecord->isExpired()) {
            return errorResponse(__('parentportal::app.otp_invalid'), null, 422);
        }

        DB::beginTransaction();
        try {
            $parent->update(['email_verified_at' => now()]);
            $this->otpRepository->markAsVerified($otpRecord);
            $token = JWTAuth::guard('parent')->fromUser($parent);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(__('parentportal::app.operation_failed'), $e);
        }

        return successResponse(
            array_merge((new ParentProfileResource($parent))->toArray($request), ['token' => $token]),
            __('parentportal::app.email_verified')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/resend-otp",
     *     summary="Resend email verification OTP",
     *     tags={"Parent Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"email"}, @OA\Property(property="email", type="string", format="email"))
     *     ),
     *     @OA\Response(response=200, description="OTP resent"),
     *     @OA\Response(response=422, description="Already verified or not found")
     * )
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $channelId = app('current_channel_id');
        $parent    = ParentAccount::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $parent) {
            return errorResponse(__('parentportal::app.not_found'), null, 404);
        }
        if ($parent->email_verified_at) {
            return errorResponse(__('parentportal::app.already_verified'), null, 422);
        }

        event(new ParentRegistered($parent));

        return successResponse(null, __('parentportal::app.otp_resent'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/login",
     *     summary="Parent login with email/phone + password",
     *     tags={"Parent Auth"},
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
     *     @OA\Response(response=404, description="Parent not found")
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

        $parent = $request->filled('phone')
            ? ParentAccount::where('channel_id', $channelId)->where('phone', $request->phone)->first()
            : ParentAccount::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $parent) {
            return errorResponse(__('parentportal::app.not_found'), null, 404);
        }
        if (! $parent->email_verified_at) {
            return errorResponse(__('parentportal::app.not_verified'), null, 403);
        }
        if (! Hash::check($request->password, $parent->password)) {
            return errorResponse(__('parentportal::app.invalid_credentials'), null, 401);
        }
        if (! $parent->isActive()) {
            return errorResponse(__('parentportal::app.account_inactive'), null, 403);
        }

        $token = JWTAuth::guard('parent')->fromUser($parent);

        return successResponse(
            array_merge((new ParentProfileResource($parent))->toArray($request), ['token' => $token]),
            __('parentportal::app.login_success')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/forget-password",
     *     summary="Request password reset OTP",
     *     tags={"Parent Auth"},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"email"}, @OA\Property(property="email", type="string", format="email"))
     *     ),
     *     @OA\Response(response=200, description="OTP sent"),
     *     @OA\Response(response=404, description="Parent not found")
     * )
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $channelId = app('current_channel_id');
        $parent    = ParentAccount::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $parent) {
            return errorResponse(__('parentportal::app.not_found'), null, 404);
        }

        event(new ParentPasswordResetRequested($parent));

        return successResponse(null, __('parentportal::app.password_reset_otp_sent'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/reset-password",
     *     summary="Reset password using OTP",
     *     tags={"Parent Auth"},
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
        $parent    = ParentAccount::where('channel_id', $channelId)->where('email', $request->email)->first();

        if (! $parent) {
            return errorResponse(__('parentportal::app.not_found'), null, 404);
        }

        $otpRecord = $this->otpRepository->getLatestUnverified($parent, 'password_reset');

        if (! $otpRecord || $otpRecord->code !== $request->otp) {
            return errorResponse(__('parentportal::app.otp_invalid'), null, 422);
        }
        if ($otpRecord->isExpired()) {
            return errorResponse(__('parentportal::app.otp_invalid'), null, 422);
        }

        DB::beginTransaction();
        try {
            $parent->update(['password' => $request->password]);
            $this->otpRepository->markAsVerified($otpRecord);
            $token = JWTAuth::guard('parent')->fromUser($parent);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(__('parentportal::app.operation_failed'), $e);
        }

        return successResponse(
            array_merge((new ParentProfileResource($parent))->toArray($request), ['token' => $token]),
            __('parentportal::app.password_reset_success')
        );
    }

    // =========================================================================
    // Protected — auth:parent
    // =========================================================================

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/auth/me",
     *     summary="Get authenticated parent profile",
     *     tags={"Parent Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Parent profile"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $parent = auth('parent')->user()->loadCount('students');
        return successResponse(new ParentProfileResource($parent), __('parentportal::app.show_success'));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/parent/auth/me",
     *     summary="Update parent profile (name, phone, image)",
     *     tags={"Parent Auth"},
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

        $parent = auth('parent')->user();
        $data   = $request->only(['name', 'phone']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store("parents/{$parent->channel_id}", 'public');
        }

        $parent->update($data);

        return successResponse(new ParentProfileResource($parent->fresh()), __('parentportal::app.profile_updated'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/change-password",
     *     summary="Change parent password",
     *     tags={"Parent Auth"},
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

        $parent = auth('parent')->user();

        if (! Hash::check($request->current_password, $parent->password)) {
            return errorResponse(__('parentportal::app.invalid_credentials'), null, 422);
        }

        $parent->update(['password' => $request->password]);

        return successResponse(null, __('parentportal::app.password_changed'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/logout",
     *     summary="Logout parent",
     *     tags={"Parent Auth"},
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
            return successResponse(null, __('parentportal::app.logout_success'));
        } catch (\Throwable $e) {
            return errorResponse(__('parentportal::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/auth/refresh",
     *     summary="Refresh parent JWT token",
     *     tags={"Parent Auth"},
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
            return successResponse(['token' => $token], __('parentportal::app.token_refreshed'));
        } catch (\Throwable $e) {
            return errorResponse(__('parentportal::app.token_expired'), null, 401);
        }
    }
}
