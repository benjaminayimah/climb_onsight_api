<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['store']]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $credentials = $request->only('email', 'password');
        try {
            if( !$token = JWTAuth::attempt($credentials)) {
                return response()->json('Invalid credentials. Check your username and password and try again.', 401);
            }
        } catch (JWTException $e) {
            return response()->json('Could not create token.', 500);
        }
        $user = User::where('email', $request->email)->first();

        return response()->json([
            'token' => $token,
            'user' => $user
        ], 200);
    }

    public function destroy()
    {
        if (!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        auth()->logout(true);
        return response()->json(['status', 'logged out!'], 200);
    }
}
