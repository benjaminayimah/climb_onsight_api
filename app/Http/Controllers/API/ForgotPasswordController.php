<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPassword;
use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Encryption\DecryptException;


class ForgotPasswordController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
        ]);
        try {
            $email = $request['email'];
            if($this->validateEmail($email)) {
                $token = $this->storeToken($email);
                $this->sendMail($token, $email);
                return response()->json('success', 200);
            }else {
                return response()->json('The email '.'"'.$email.'"'.' does not exist in our system.', 404);
            }
        } catch (\Throwable $th) {
            return response()->json($th, 500);
        }
    }
    public function validateEmail($email)
    {
        $user = User::where('email', $email)->first();
        if(isset($user))
        return true;
        else return false;
    }
    public function storeToken($email)
    {
        $token = Crypt::encryptString($email);
        $findUser = DB::table('password_reset_tokens')->where('email', $email);
        if(isset($findUser)) {
            $findUser->delete();
        }
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()  
        ]);
        return $token;
    }
    public function sendMail($token, $email)
    {
        $data = new Email();
        $data->reset_url = 'reset-password/'.$token;
        $data->frontend_url = config('hosts.fe');
        $data->s3bucket = config('hosts.s3');
        $data->hideme = Carbon::now();
        Mail::to($email)->send(new ForgotPassword($data));
    }

    //Reset the password

    public function ResetPassword(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|confirmed|min:8',
        ]);
        $email = $this->decryptToken($request['token']);
        return $email ? $this->doResetPassword($email, $request['password']) : $this->tokenException();
    }
    public function decryptToken ($token) {
        try {
            $email = Crypt::decryptString($token);
            if($email) {
                $validated = DB::table('password_reset_tokens')->where('email', $email)->first();
                if(isset($validated)) {
                    return $email;
                }else {
                    return false;
                }
            }
        } catch (DecryptException $e) {
            return false;
        }
    }
    public function tokenException() {
        return response()->json('Your token is invalid. Please try resending a new "forgot password" link.', 401);
    }
    private function doResetPassword($email, $password) {
        $userData = User::whereEmail($email)->first();
        $userData->update([
            'password' => bcrypt($password)
        ]);
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        return response()->json($email, 200);
    }

   
}
