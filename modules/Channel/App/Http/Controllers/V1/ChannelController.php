<?php

namespace Modules\Channel\App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Channel\App\Models\Channel;
use Modules\Channel\App\Models\Role;
use Modules\Channel\App\Events\UserRegistered;
use Modules\Channel\App\Events\PasswordResetRequested;
use Modules\Channel\App\Http\Resources\UserResource;
use Modules\Channel\App\Repositories\UserRepository;
use Modules\Channel\App\Repositories\ChannelRepository;
use Modules\Channel\App\Http\Requests\V1\RegisterRequest;
use Modules\Core\App\Repositories\OtpRepository;

/**
 * @OA\Tag(name="Auth", description="Authentication and channel registration")
 * @OA\Tag(name="Channel", description="Channel management (slug-scoped)")
 */
class ChannelController extends Controller
{
    public function __construct(
        protected UserRepository    $userRepository,
        protected ChannelRepository $channelRepository,
        protected OtpRepository     $otpRepository,
    ) {}

    // =========================================================================
    // Public — no authentication required
    // =========================================================================

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register a new channel and owner user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel_name","channel_type","name","email","phone","gender","password","password_confirmation"},
     *             @OA\Property(property="channel_name", type="string", example="Mohamed Math Lessons"),
     *             @OA\Property(property="channel_type", type="string", enum={"teacher","center"}, example="teacher"),
     *             @OA\Property(property="name", type="string", example="Mohamed Hassan"),
     *             @OA\Property(property="email", type="string", format="email", example="mohamed@example.com"),
     *             @OA\Property(property="phone", type="string", example="01001234567"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Secret123!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Channel and user created — OTP sent to email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="channel_slug", type="string", example="mohamed-math-lessons")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $slug    = $this->generateUniqueSlug($data['channel_name']);
            $channel = $this->channelRepository->create([
                'name' => $data['channel_name'],
                'slug' => $slug,
                'type' => $data['channel_type'] ?? 'teacher',
            ]);

            $ownerRole = Role::whereNull('channel_id')->where('name', 'owner')->first();

            $data['channel_id'] = $channel->id;
            $data['role_id']    = $ownerRole?->id;
            $user = $this->userRepository->create($data);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }

        event(new UserRegistered($user));

        return successResponse(
            ['user' => new UserResource($user), 'channel_slug' => $slug],
            trans('channel::app.channel.created'),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/verify-email",
     *     summary="Verify email with OTP code",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","otp"},
     *             @OA\Property(property="email", type="string", format="email", example="mohamed@example.com"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Email verified — JWT token returned",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid or expired OTP")
     * )
     */
    public function validateOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|exists:users,email',
            'otp'   => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $user = $this->userRepository->findByField('email', $request->email)->first();

            if (! $user) {
                return errorResponse(trans('channel::app.user.not_found'), null, 404);
            }
            if ($user->email_verified_at) {
                return errorResponse(trans('channel::app.user.already_verified'), null, 422);
            }

            $otpRecord = $this->otpRepository->getLatestUnverified($user, 'email_verification');

            if (! $otpRecord || $otpRecord->code !== $request->otp) {
                return errorResponse(trans('channel::app.otp.invalid'), null, 422);
            }
            if ($otpRecord->isExpired()) {
                return errorResponse(trans('channel::app.otp.expired'), null, 422);
            }

            $user->email_verified_at = now();
            $user->save();

            $this->otpRepository->markAsVerified($otpRecord);

            $token = JWTAuth::fromUser($user);

            DB::commit();

            return successResponse(
                array_merge((new UserResource($user))->toArray(request()), ['token' => $token]),
                trans('channel::app.otp.validated')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/resend-otp",
     *     summary="Resend email verification OTP",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="mohamed@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP resent"),
     *     @OA\Response(response=422, description="Already verified or user not found")
     * )
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = $this->userRepository->findByField('email', $request->email)->first();

        if (! $user) {
            return errorResponse(trans('channel::app.user.not_found'), null, 404);
        }
        if ($user->email_verified_at) {
            return errorResponse(trans('channel::app.user.already_verified'), null, 422);
        }

        event(new UserRegistered($user));

        return successResponse(null, trans('channel::app.otp.resent'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login with email or phone + password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="email", type="string", format="email", example="mohamed@example.com"),
     *             @OA\Property(property="phone", type="string", example="01001234567"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123!")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful — JWT token returned"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Email not verified or account blocked"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required_without:phone|string|email',
            'phone'    => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        $user = $request->filled('phone')
            ? $this->userRepository->findByField('phone', $request->phone)->first()
            : $this->userRepository->findByField('email', $request->email)->first();

        if (! $user) {
            return errorResponse(trans('channel::app.user.not_found'), null, 404);
        }
        if (! $user->email_verified_at) {
            return errorResponse(trans('channel::app.user.not_verified'), null, 403);
        }
        if (! Hash::check($request->password, $user->password)) {
            return errorResponse(trans('channel::app.auth.invalid_credentials'), null, 401);
        }
        if ($user->status == 0) {
            return errorResponse(trans('channel::app.auth.blocked'), null, 403);
        }

        $token = JWTAuth::fromUser($user);

        return successResponse(
            array_merge((new UserResource($user))->toArray($request), ['token' => $token]),
            trans('channel::app.auth.login_success')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/forget-password",
     *     summary="Request a password-reset OTP via email",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="mohamed@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP sent to email"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = $this->userRepository->findByField('email', $request->email)->first();
        if (! $user) {
            return errorResponse(trans('channel::app.user.not_found'), null, 404);
        }

        event(new PasswordResetRequested($user));

        return successResponse(null, trans('channel::app.password.reset_otp_sent'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     summary="Reset password using OTP",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","otp","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset — JWT token returned"),
     *     @OA\Response(response=422, description="Invalid or expired OTP")
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->userRepository->findByField('email', $request->email)->first();
        if (! $user) {
            return errorResponse(trans('channel::app.user.not_found'), null, 404);
        }

        $otpRecord = $this->otpRepository->getLatestUnverified($user, 'password_reset');

        if (! $otpRecord || $otpRecord->code !== $request->otp) {
            return errorResponse(trans('channel::app.otp.invalid'), null, 422);
        }
        if ($otpRecord->isExpired()) {
            return errorResponse(trans('channel::app.otp.expired'), null, 422);
        }

        DB::beginTransaction();
        try {
            $user->update(['password' => bcrypt($request->password)]);
            $this->otpRepository->markAsVerified($otpRecord);

            $token = JWTAuth::fromUser($user);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }

        return successResponse(
            array_merge((new UserResource($user))->toArray($request), ['token' => $token]),
            trans('channel::app.password.reset_success')
        );
    }

    // =========================================================================
    // Protected — JWT required
    // =========================================================================

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/auth/me",
     *     summary="Get authenticated user",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Authenticated user data"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Channel not found")
     * )
     */
    public function me(Request $request)
    {
        $user = auth('user')->user();
        return successResponse(new UserResource($user), trans('channel::app.common.show_success'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/auth/logout",
     *     summary="Logout — invalidate JWT token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return successResponse(null, trans('channel::app.auth.logout_success'));
        } catch (\Throwable $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/auth/refresh",
     *     summary="Refresh JWT token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="New token returned",
     *         @OA\JsonContent(@OA\Property(property="token", type="string"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function refreshToken()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return successResponse(['token' => $token], trans('channel::app.auth.token_refreshed'));
        } catch (\Throwable $e) {
            return errorResponse(trans('channel::app.auth.token_expired'), null, 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/channel",
     *     summary="Get current channel info",
     *     tags={"Channel"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Channel info"),
     *     @OA\Response(response=404, description="Channel not found"),
     *     @OA\Response(response=403, description="Channel suspended")
     * )
     */
    public function show(Request $request)
    {
        $channel = app('current_channel');
        return successResponse($channel, trans('channel::app.common.show_success'));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'channel';
        $slug = $base;
        $i    = 1;
        while (Channel::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
