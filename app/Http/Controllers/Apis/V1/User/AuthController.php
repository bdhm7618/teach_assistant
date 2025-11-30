<?php

namespace App\Http\Controllers\Apis\Teacher;

use App\Http\Resources\Teacher\TeacherResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required|string"
        ]);

        $credentials = $request->only('email', 'password');

        if (! $token = auth('teacher')->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return successResponse([
            'token' => $token,
            'teacher' => new TeacherResource(auth("teacher")->user())
        ]);
    }
}
