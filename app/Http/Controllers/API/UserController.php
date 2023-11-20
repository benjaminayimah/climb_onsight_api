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
        $admins = [];
        $account = '';
        $balance = [];
        $payouts = [];
        if ($user->role === 'super_admin') {
            $notifications = User::where('role', 'guide')
                ->where('is_approved', false)
                ->get();
            $guides = User::where('role', 'guide')->get();
            $climbers = User::where('role', 'climber')->get();
            $events = DB::table('events')->get();
            $bookings = Booking::all();
            $admins = User::where('role', 'admin')->get();

        }elseif ($user->role === 'admin') {
            # code...
        }elseif ($user->role === 'guide') {
            $bookings = Booking::where('guide_id', $user->id)
                ->where('guide_delete', false)
                ->orderBy('id', 'DESC')
                ->get();
            foreach ($bookings as $key) {
                $climbers[] = User::where('id', $key->user_id)->get();
            }
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
            if($user->charges_enabled && $user->details_submitted && $user->payouts_enabled) {
                $stripe_id = $user->stripe_account_id;
                $stripe = new \Stripe\StripeClient(config('stripe.sk'));
                $account = $stripe->accounts->retrieve($stripe_id, []);
                \Stripe\Stripe::setApiKey(config('stripe.sk'));
                // Retrieve balance details for the connected account
                $balance = \Stripe\Balance::retrieve(
                    ['stripe_account' => $stripe_id]
                )->instant_available;
                // Retrieve all payouts for the connected account
                $payouts = $stripe->transfers->all([
                    'destination' => $stripe_id,
                    'limit' => 10, // You can adjust the limit as needed
                ]);
            }
        }elseif ($user->role === 'climber') {
            $events = DB::table('events')
                ->where('events.start_date', '>=', Carbon::now()->toDateString())
                ->orderByDesc('id')
                ->take(10)
                ->get();
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
                ->select('events.*', 'bookings.receipt_no', 'bookings.guide_id', 'bookings.event_id', 'bookings.paid', 'bookings.accepted', 'bookings.guide_delete')
                ->get();
                foreach ($bookings as $key) {
                    $guides[] = User::where('id', $key->guide_id)->get();
                }
        }
        return response()->json([
            'user' => $user,
            'notifications' => $notifications,
            'guides' => $guides,
            'climbers' => $climbers,
            'events' => $events,
            'bookings' => $bookings,
            'admins' => $admins,
            'account' => $account,
            'balance' => $balance,
            'payouts' => $payouts,
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
