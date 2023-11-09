<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\Mailer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        $user = auth()->user();
        $notifications = [];
        $guides = [];
        $climbers = [];
        $events = [];
        $bookings = [];
        if ($user->role === 'super_admin') {
            $notifications = DB::table('users')
                ->where('role', 'guide')
                ->where('is_approved', false)
                ->get();
            $guides = DB::table('users')
                ->where('role', 'guide')
                ->get();
            $climbers = DB::table('users')
                ->where('role', 'climber')
                ->get();
            $events = DB::table('events')
                // ->where('date', '>=', Carbon::now()->toDateString())
                ->get();
        }elseif ($user->role === 'admin') {
            # code...
        }elseif ($user->role === 'guide') {
            // $bookings = Booking::where('user_id', $user->id)
            //     ->where('guide_id', $user->id)
            //     ->orderBy('id', 'DESC')
            //     ->get();
            $notifications = DB::table('bookings')
                ->join('users', 'bookings.user_id', '=', 'users.id')
                ->where('bookings.guide_id', $user->id)
                ->where('bookings.accepted', false)
                ->where('bookings.guide_delete', false)
                ->select('users.name', 'users.profile_picture', 'bookings.id', 'bookings.receipt_no')
                ->orderBy('id', 'DESC')
                ->get();
            $events = DB::table('events')
            ->join('users', 'events.user_id', '=', 'users.id')
            ->where('events.user_id', $user->id)
            ->select('users.name', 'events.*')
            ->get();
        }elseif ($user->role === 'climber') {
            $notifications = DB::table('bookings')
                ->join('users', 'bookings.user_id', '=', 'users.id')
                ->where('bookings.user_id', $user->id)
                ->where('bookings.accepted', true)
                ->where('bookings.climber_delete', false)
                ->where('bookings.paid', false)
                ->select('bookings.*',)
                ->orderBy('id', 'DESC')
                ->get();
            $bookings = DB::table('bookings')
                ->join('events', 'bookings.event_id', '=', 'events.id')
                ->where('bookings.user_id', $user->id)
                ->where('climber_delete', false)
                ->select('events.*', 'bookings.receipt_no', 'bookings.event_id', 'bookings.paid', 'bookings.accepted', 'bookings.guide_delete')
                ->get();

            $events = DB::table('events')
                // ->where('events.start_date', '>=', Carbon::now()->toDateString())
                ->get();
        }
        return response()->json([
            'user' => $user,
            'notifications' => $notifications,
            'guides' => $guides,
            'climbers' => $climbers,
            'events' => $events,
            'bookings' => $bookings
        ], 200);
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
            $new_skills = array();
            if($request['new_skills'] != [""]) {
                $new_skills = $request['new_skills'];
            }
            $split = explode("/", $request['tempImage']);
            $profile_picture = end($split);
            $user = User::findOrFail($id);
            $user->dob = $request['dob'];
            $user->gender = $request['gender'];
            $user->bio = $request['bio'];
            $user->activities = $request['activities'];
            $user->skills = $request['skills'];
            $user->new_skills = $new_skills;
            $user->profile_picture = 'images/'.$profile_picture;
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
