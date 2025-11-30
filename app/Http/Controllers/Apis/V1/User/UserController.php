<?php

namespace App\Http\Controllers\Apis\V1\User;

use App\Models\Channel;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Apis\V1\Teacher\RegisterRequest;

class UserController extends Controller
{

    public function register(RegisterRequest $request)
    {
        $channel = Channel::create([
            'name'       => $request->channel_name,
            'code'       => strtoupper(Str::random(8)),
            'is_private' => 0,
            'created_by' => null,
        ]);

        $user = User::create([
            'name'        => $request->full_name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'gender'      => $request->gender,
            'image'       => $request->image,
            'password'    => Hash::make($request->password),
            'role_id'     => 1,  // admin (owner role)
            'channel_id'  => $channel->id,
            'status'      => 1,
        ]);

        // 3. Set channel creator
        $channel->update([
            'created_by' => $user->id,
        ]);

        // 4. Create JWT token
        $token = auth()->login($user);

        return response()->json([
            'status'  => true,
            'message' => 'Account created successfully.',
            'user'    => $user,
            'channel' => $channel,
            'token'   => $token
        ], 201);
    }
}
