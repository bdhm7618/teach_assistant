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
     * Display a listing of the resource.
     */
    public  function register(RegisterRequest $request)
    {
        // DB::beginTransaction();
        // try {
        $data = $request->validated();
        $channel = $this->channelRepository->create(["name" => $data['channel_name']]);
        $data["channel_id"] = $channel->id;
        $user = $this->userRepository->create($data);
        DB::commit();
        event(new UserRegistered($user));

        return successResponse(new UserResource($user), trans("channel::app.channel.created"), 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return errorResponse(trans('channel::app.common.operation_failed'), $e);
        // }
    }


    /**
     * Validate OTP and return user with token
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
