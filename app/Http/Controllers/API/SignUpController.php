<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class SignUpController extends Controller
{
    
    public function registerClimber(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'phone_number' => 'required',
            'password' => 'required|min:8',
        ]);
        try {
            $email = $request['email'];
            $name = $request['name'];
            $newuser = new User();
            $newuser->name = $name;
            $newuser->email = $email;
            $newuser->password = bcrypt($request['password']);
            $newuser->phone_number = $request['phone_number'];
            $newuser->save();
            $this->sendMail($email, $name);
            if( !$token = JWTAuth::fromUser($newuser)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Could not create user.'
            ], 500);
        }
        return response()->json([
            'user' => $newuser,
            'token' => $token
        ], 200);
    }
    public function sendMail($email, $name){
        $data = new Email();
        $data->name = $name;
        $data->token = Crypt::encryptString($email);;
        $data->frontend_url = config('hosts.fe');
        $data->hostname = config('hosts.be');
        Mail::to($email)->send(new VerifyEmail($data));
    }

    public function VerifyAccount(Request $request)
    {
        try {
            $email = Crypt::decryptString($request->token);
            $user = User::whereEmail($email)->first();
            if(isset($user)) {
                if(!$user->email_verified) {
                    $user->email_verified = true;
                    $user->email_verified_at = Carbon::now();
                    $user->update();
                }
                return response()->json([
                    'status' => 'success',
                    'message' => 'Your account has been verified successfully',
                ], 200);
            }
            return response()->json( $this->NotFound(), 401);
        } catch (DecryptException $e) {
            return response()->json( $this->NotFound(), 401);
        }
    }
    public function NotFound()
    {
        return [
            'status' => 'failed',
            'message' => 'Sorry we couldn\'t verify your email with the submitted credentials. Click the button below to try again. If the issue persists, please contact support.'
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
