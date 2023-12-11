<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\GuideApproved;
use App\Mail\VerifyEmail;
use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\ColorTrait;

class SignUpController extends Controller
{
    use ColorTrait;
    
    public function registerClimber(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'phone_number' => 'required',
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
            $newuser->phone_number = $request->phone_number;
            $newuser->color = $randomColor;
            $newuser->save();
            if( !$token = JWTAuth::fromUser($newuser)) {
                return response()->json('Invalid credentials', 401);
            }
        } catch (\Throwable $th) {
            return response()->json('Could not create user.', 500);
        }
        try {
            $this->sendMail($email, $name);
        } catch (\Throwable $th) {
            return response()->json([
                'user' => $newuser,
                'token' => $token
            ], 200);
        }
        return response()->json([
            'user' => $newuser,
            'token' => $token
        ], 200);
    }
    public function registerGuide(Request $request)
    {
        try {
            $randomColor = $this->getRandomColor();
            $email = $request['email'];
            $name = $request['name'];
            $newGuide = new User();
            $newGuide->name = $name;
            $newGuide->company_email = $email;
            $newGuide->password = bcrypt('dummy_password');
            $newGuide->country = $request->country;
            $newGuide->role = 'guide';
            $newGuide->phone_number = $request['phone_number'];
            $newGuide->guide_insurance = json_encode($request['guide_insurance']);
            $newGuide->guide_certificate = json_encode($request['guide_certificate']);
            $newGuide->guide_awards = json_encode($request['guide_awards']);
            $newGuide->customer_reviews = $request['customer_reviews'];
            $newGuide->guide_experience = json_encode($request['guide_experience']);
            $newGuide->referees = json_encode($request['referees']);
            $newGuide->color = $randomColor;
            $newGuide->save();

        } catch (\Throwable $th) {
            return response()->json('Could not create user.', 500);
        }
        return response()->json('success', 200);
    }
    private function sendMail($email, $name){
        $data = new Email();
        $data->name = $name;
        $data->token = Crypt::encryptString($email);;
        $data->frontend_url = config('hosts.fe');
        $data->s3bucket = config('hosts.s3');
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
    public function AcceptGuide($id) {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        try {
            $guide = User::findOrFail($id);
            $guide->is_approved = true;
            $guide->update();

            //send email
            $email = $guide->company_email;
            $data = new Email();
            $data->name = $guide->name;
            $data->email = $email;
            $data->token = Crypt::encryptString($email);
            $data->frontend_url = config('hosts.fe');
            $data->s3bucket = config('hosts.s3');

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
        try {
            Mail::to($email)->send(new GuideApproved($data));
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Guide has been accepted',
            ], 200);
        }
        return response()->json([
            'message' => 'Guide has been accepted',
        ], 200);
    }
    public function DeclineGuide($id) {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        $guide = User::findOrFail($id);
        $guide->delete();
        return response()->json('Guide is declined',200);
    }
    public function CreateGuideLogin(Request $request) {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);
        $company_email = $this->decryptToken($request['token']);
        return $company_email ? $this->doResetPassword($company_email, $request['email'], $request['password']) : $this->tokenException();

    }
    public function decryptToken ($token) {
        try {
            $email = Crypt::decryptString($token);
            if($email) {
                $validated = DB::table('users')
                ->where('company_email', $email)
                ->where('email', null)
                ->first();
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
        return response()->json('Your token is invalid. Please contact support@climbonsight.ca for help.', 401);
    }
    private function doResetPassword($company_email, $email, $password) {
        $userData = User::whereCompanyEmail($company_email)->first();
        $userData->update([
            'email' => $email,
            'password' => bcrypt($password)
        ]);
        return response()->json($email, 200);
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
