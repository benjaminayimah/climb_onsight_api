<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\NewAdminUser;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Traits\ColorTrait;


class AdminsController extends Controller
{
    use ColorTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function index()
    {
        //
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'password' => 'required|min:8',
        ]);
        try {
            $randomColor = $this->getRandomColor();
            $email = $request->email;
            $name = $request->name;
            $newuser = new User();
            $newuser->name = $name;
            $newuser->email = $email;
            $newuser->password = bcrypt($request->password);
            $newuser->role = 'admin';
            $newuser->color = $randomColor;
            $newuser->save();
            if($request->sendEmail) {
                //Send email
                    $data = new Email();
                    $data->name = $name;
                    $data->email = $email;
                    $data->password = $request->password;
                    $data->frontend_url = config('hosts.fe');
                    $data->s3bucket = config('hosts.s3');
                }
            
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
        try {
            if($request->sendEmail) {
                Mail::to($email)->send(new NewAdminUser($data));
            }
        } catch (\Throwable $th) {
            return response()->json([
                'user' => $newuser,
                'message' => 'User is created'
            ], 200);
        }
        return response()->json([
            'user' => $newuser,
            'message' => 'User is created'
        ], 200);
    }
    public function UpdatePermissions(Request $request, $id) {
        try {
            $user = User::findOrFail($id);
            $user->permissions = json_encode($request->permissions);
            $user->update();
            return response()->json([
                'user' => $user,
                'message' => 'User permission is updated'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function ChangeAdminPassword(Request $request, $id) {
        $this->validate($request, [
            'new_password' => 'required|min:8',
        ]);
        try {
            $thisUser = User::findOrFail($id);
            $thisUser->password = bcrypt($request->new_password);
            $thisUser->update();
            return response()->json( 'Password is updated', 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function update(Request $request, string $id)
    {
        //
    }
    public function destroy(string $id)
    {
        //
    }
}
