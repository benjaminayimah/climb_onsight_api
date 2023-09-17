<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\Mailer;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use Mailer;
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['store']]);
        if (!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
    }
    public function index()
    {
        return response()->json(auth()->user(), 200);
    }
    public function store(Request $request)
    {
        // if (! $user = JWTAuth::parseToken()->authenticate()) {
        //     return response()->json(['status' => 'User not found!'], 404);
        // };
        // return response()->json($this->sendMail(), 200);
    }

    public function update(Request $request, string $id)
    {
        try {
            $split = explode("/", $request['tempImage']);
            $profile_picture = end($split);
            $user = User::findOrFail($id);
            $user->dob = $request['dob'];
            $user->gender = $request['gender'];
            $user->bio = $request['bio'];
            $user->activities = $request['activities'];
            $user->skills = $request['skills'];
            $user->new_skills = $request['new_skills'];
            $user->profile_picture = $profile_picture;
            $user->update();

            if($request['tempImage'] != null) {
                if (Storage::disk('s3')->exists($request['tempImage'])) {
                    Storage::disk('s3')->move($request['tempImage'], 'images/'.$profile_picture);
                    Storage::disk('s3')->deleteDirectory('temp_'.$user->id);
                };
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Could not create user.'
            ], 500);
        }
        return response()->json($user, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
