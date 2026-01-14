<?php

namespace Modules\Channel\App\Http\Controllers\V1;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Channel\App\Events\UserRegistered;
use Modules\Channel\App\Http\Resources\UserResource;
use Modules\Channel\App\Repositories\UserRepository;
use Modules\Channel\App\Repositories\ChannelRepository;
use Modules\Channel\App\Http\Requests\V1\RegisterRequest;

/**
 * @OA\Tag(
 *     name="Channel",
 *     description="Channel management endpoints"
 * )
 */
class ChannelController extends Controller
{
    protected $userRepository;
    protected $channelRepository;

    public function __construct(UserRepository $userRepository, ChannelRepository $channelRepository)
    {
        $this->userRepository = $userRepository;
        $this->channelRepository = $channelRepository;
    }

    /**
     * Register a new channel and user
     * 
     * @OA\Post(
     *     path="/api/v1/channel/register",
     *     summary="Register a new channel and user",
     *     tags={"Channel"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel_name", "name", "email", "phone", "gender", "password", "password_confirmation"},
     *             @OA\Property(property="channel_name", type="string", example="My Channel"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Channel and user created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Channel created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public  function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $channel = $this->channelRepository->create(["name" => $data['channel_name']]);
        $data["channel_id"] = $channel->id;
        $user = $this->userRepository->create($data);
        DB::commit();
        event(new UserRegistered($user));

        return successResponse(new UserResource($user), trans("channel::app.channel.created"), 201);
    }


    /**
     * Validate OTP and return user with token
     * 
     * @OA\Post(
     *     path="/api/v1/channel/user/verify-email",
     *     summary="Verify email with OTP",
     *     tags={"Channel"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function validateOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|exists:users,email',
            'otp' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $user = $this->userRepository->findByField('email', $request->email)->first();

            if (!$user) {
                return errorResponse(trans('channel::app.user.not_found'));
            }

            if ($user->email_verified_at) {
                return errorResponse(trans('channel::app.user.already_verified'));
            }

            $otpRecord = $user->otps()->latest()->first();


            if (!$otpRecord || $otpRecord->code != $request->otp) {
                return errorResponse(trans('channel::app.otp.invalid'));
            }

            if ($otpRecord->expires_at && now()->gt($otpRecord->expires_at)) {
                return errorResponse(trans('channel::app.otp.expired'));
            }

            $user->email_verified_at = now();
            $user->save();

            $token = JWTAuth::fromUser($user);

            DB::commit();

            return successResponse(
                array_merge(
                    (new UserResource($user))->toArray(request()),
                    ['token' => $token]
                ),
                trans('channel::app.otp.validated')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * User login
     * 
     * @OA\Post(
     *     path="/api/v1/channel/user/login",
     *     summary="User login",
     *     tags={"Channel"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc...")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = $this->userRepository->where('email', $request->email)->first();

        if (!$user) {
            return errorResponse(trans('channel::app.user.not_found'));
        }

        if (!$user->email_verified_at) {
            return errorResponse(trans('channel::app.user.not_verified'));
        }

        if (!Hash::check($request->password, $user->password)) {
            return errorResponse(trans('channel::app.auth.invalid_credentials'));
        }

        if ($user->status == 0) {
            return errorResponse(trans('channel::app.user.blocked'));
        }

       
        $token = JWTAuth::fromUser($user);

        return successResponse(
            array_merge(
                (new UserResource($user))->toArray($request),
                ['token' => $token]
            ),
            trans('channel::app.auth.login_success')
        );
    }


    /**
     * Request password reset OTP
     * 
     * @OA\Post(
     *     path="/api/v1/channel/user/forget-password",
     *     summary="Request password reset OTP",
     *     tags={"Channel"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = $this->userRepository->where('email', $request->email)->first();

        if (!$user) {
            return errorResponse(trans('channel::app.user.not_found'));
        }

        event(new UserRegistered($user));

        return successResponse(
            null,
            trans('channel::app.password.reset_otp_sent')
        );
    }

    /**
     * Reset password with OTP
     * 
     * @OA\Post(
     *     path="/api/v1/channel/user/reset-password",
     *     summary="Reset password with OTP",
     *     tags={"Channel"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp", "password", "password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="password", type="string", format="password", example="NewPassword123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewPassword123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->userRepository->where('email', $request->email)->first();

        if (!$user) {
            return errorResponse(trans('channel::app.user.not_found'));
        }

        $otpRecord = $user->otps()->latest()->first();

        if (!$otpRecord || $otpRecord->code !== $request->otp) {
            return errorResponse(trans('channel::app.otp.invalid'));
        }

        if ($otpRecord->expires_at && now()->gt($otpRecord->expires_at)) {
            return errorResponse(trans('channel::app.otp.expired'));
        }

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        $user->otps()->delete();

     
        $token = JWTAuth::fromUser($user);

        return successResponse(
            array_merge(
                (new UserResource($user))->toArray($request),
                ['token' => $token]
            ),
            trans('channel::app.password.reset_success')
        );
    }
}
