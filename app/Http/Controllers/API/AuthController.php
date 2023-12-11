<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Error;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
    public function UpadatePassword(Request $request) {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
        try {
            $current_pass = $user->password;
            $new_password = $request['new_password'];
            if (Hash::check($request['current_password'], $current_pass)) {
                $user->password = bcrypt($new_password);
                $user->update(); 
            }else {
                $new_err = new Error();
                $new_err->current_password = array('The password does not match.');
                return response()->json([
                    'errors' => $new_err
                ], 422);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
        return response()->json('Password is updated', 200);
    }
    public function update(Request $request, $id)
    {        
        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required'
        ]);
        $newImage = $request['tempImage'];
        $updateUser = User::findOrFail($id);
        $oldImage = $updateUser->profile_picture;
        if($request['email'] != $updateUser->email) {
            $this->validate($request, [
                'email' => 'unique:users'
            ]);
        }
        try {
            $updateUser->name = $request['name'];
            $updateUser->email = $request['email'];
            $updateUser->phone_number = $request['phone_number'];
            if($newImage !== null) {
                $split_new = explode("/", $request['tempImage']);
                $exact_new_image_path = end($split_new);
                $split_old = explode("/", $oldImage);
                $exact_old_image_path = end($split_old);
                if($oldImage && ($exact_new_image_path == $exact_old_image_path)) {
                    $this->deleteTemp($id);
                }else {
                    $updateUser->profile_picture = 'images/'.$exact_new_image_path;
                    if (Storage::disk('s3')->exists($newImage)) {
                        Storage::disk('s3')->move($newImage, 'images/'.$exact_new_image_path);
                        $this->deleteTemp($id);
                    };
                    if($oldImage) {
                        $this->deleteImage($oldImage);
                    }
                }
            } else {
                $updateUser->profile_picture = null;
                if($oldImage) {
                    $this->deleteImage($oldImage);
                }
            }
            $updateUser->update();
            return response()->json([
                'user' => $updateUser,
                'message' => 'Your profile is updated'
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function deleteTemp($id) {
        Storage::disk('s3')->deleteDirectory('temp_'.$id);
    }
    public function deleteImage($image) {
        if (Storage::disk('s3')->exists($image)) {
            Storage::disk('s3')->delete($image);
        };
    }

    public function destroy()
    {
        if (!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        auth()->logout(true);
        return response()->json(['message' => 'logged out!'], 200);
    }

    public function DeleteUser($id) {
        try {
            $user = User::findOrFail($id);
            $image = $user->profile_picture;
            $terms = json_decode($user->guide_terms);
            $certificate = json_decode($user->guide_certificate);
            $insurance = json_decode($user->guide_insurance);

            if($image) {
                $this->deleteImage($image);
            }
            if($user->role === 'guide') {
                if (isset($terms)) {
                    $this->deleteImage($terms->url);
                }
                if(!empty($certificate)) {
                    foreach ($certificate as $key) {
                        $this->deleteImage($key->url);
                    }
                }
                if(!empty($insurance)) {
                    foreach ($insurance as $key) {
                        $this->deleteImage($key->url);
                    }
                }
                $events = Event::where('user_id', $user->id)->get();
                $bookings = Booking::where('guide_id', $user->id)->get();
                if(!empty($events)) {
                    foreach ($events as $value) {
                        $value->delete();
                    }
                }
                if(!empty($bookings)) {
                    foreach ($bookings as $value) {
                        $value->delete();
                    }
                }

            }
            elseif($user->role === 'climber') {
                $bookings = Booking::where('user_id', $user->id)->get();
                if(!empty($bookings)) {
                    foreach ($bookings as $value) {
                        $value->delete();
                    }
                }
            }
            $user->delete();
            return response()->json($id, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
}
