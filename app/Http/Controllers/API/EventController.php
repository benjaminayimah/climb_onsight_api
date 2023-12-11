<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
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
        try {
            $price = $request->price;
            $end_date = $request->end_date;
            $repeat_at = null;
            if ($request->repeat) {
                $repeat_at = $request->repeat_at;
            }else {
                $end_date = $request->start_date;
            }
            if ($request->event_type === 'private') {
                $price = $request->price_range;
            }

            $user = auth()->user();
            $event = new Event();
            $event->user_id = $user->id;
            $event->event_type = $request->event_type;
            $event->event_name = $request->event_name;
            $event->start_date = $request->start_date;
            $event->end_date = $end_date;
            $event->latitude = $request->latitude;
            $event->longitude = $request->longitude;
            $event->address = $request->address;
            $event->category = $request->category;
            $event->attendance_limit = $request->attendance_limit;
            $event->event_duration = $request->event_duration;
            $event->price = json_encode($price);
            $event->event_terms = json_encode($request->event_terms);
            $event->climber_gears = json_encode($request->climber_gears);
            $event->guide_gears = json_encode($request->guide_gears);
            $event->faqs = json_encode($request->faqs);
            $event->experience_required = json_encode($request->experience_required);
            $event->itinerary = $request->itinerary;
            $event->event_description = $request->event_description;
            $event->color_class = $request->color;
            $event->repeat_at = $repeat_at;
            $gallery = [];

            foreach ($request->gallery as $value) {
                $split = explode("/", $value);
                $image = end($split);
                $gallery[] = 'images/'.$image;
                if (Storage::disk('s3')->exists($value)) {
                    Storage::disk('s3')->move($value, 'images/'.$image);
                };
            }
            $event->gallery = json_encode($gallery);
            $event->save();
            Storage::disk('s3')->deleteDirectory('temp_'.$user->id);
            return response()->json([
                'event' => $event,
                'message' => 'Event has been created'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function GetThisGuide($id) {
        $guide = User::findOrFail($id);
        return response()->json($guide, 200);
    }
    public function SearchEvents($searchTerm) {
        $events = DB::table('events')
            ->where(function ($query) use ($searchTerm) {
                $query->where('event_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('address', 'like', '%' . $searchTerm . '%');
            })
            ->get();
        // $guides = DB::table('users')
        //     ->where(function ($query) use ($searchTerm) {
        //         $query->where('name', 'like', '%' . $searchTerm . '%')
        //             ->where('role', 'guide')
        //             ->where('is_approved', true);
        //     })
        //     ->get();
        return response()->json([
            'events' => $events
        ], 200);
    }
    public function GetNearByEvents(Request $request) 
    {
        $events = array();
        $userLat = $request['lat'];
        $userLng = $request['lng'];
        $radius = $request['rad']; // Search within a 100km radius
        $events = DB::table('events')
            ->select('*')
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?', [$userLat, $userLng, $userLat, $radius])
            ->orderBy('id', 'DESC')
            ->get();
        // if(isset($events)) {
        //     foreach ($events as $event) {
        //         $guides[] = DB::table('users')
        //         ->where('id', $event->user_id)
        //         ->first();
        //     }
        // }
        return response()->json([
            'events' => $events
        ], 200);
    }
    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();
            
            $climber_gears = [];
            $guide_gears = [];
            $price = $request->price;
            $end_date = $request->end_date;
            $repeat_at = null;
            if($request->climber_gears != [null]) {
                $climber_gears = $request->climber_gears;
            }
            if($request->guide_gears != [null]) {
                $guide_gears = $request->guide_gears;
            }
            if ($request->repeat) {
                $repeat_at = $request->repeat_at;
            }else {
                $end_date = $request->start_date;
            }
            if ($request->event_type === 'private') {
                $price = $request->price_range;
            }
            $event = Event::findOrFail($id);
            $event->event_name = $request->event_name;
            $event->event_type = $request->event_type;
            $event->start_date = $request->start_date;
            $event->end_date = $end_date;
            $event->price = $request->price;
            $event->latitude = $request->latitude;
            $event->longitude = $request->longitude;
            $event->address = $request->address;
            $event->category = $request->category;
            $event->attendance_limit = $request->attendance_limit;
            $event->event_duration = $request->event_duration;
            $event->price = json_encode($price);
            $event->event_terms = json_encode($request->event_terms);
            $event->climber_gears = json_encode($climber_gears);
            $event->guide_gears = json_encode($guide_gears);
            $event->faqs = json_encode($request->faqs);
            $event->experience_required = json_encode($request->experience_required);
            $event->itinerary = $request->itinerary;
            $event->event_description = $request->event_description;
            $event->repeat_at = $repeat_at;
            $gallery = [];
            foreach ($request->gallery as $value) {
                $split = explode("/", $value);
                $image = end($split);
                $gallery[] = 'images/'.$image;
                if (Storage::disk('s3')->exists('temp_'.$user->id.'/'.$image)) {
                    Storage::disk('s3')->move('temp_'.$user->id.'/'.$image, 'images/'.$image);
                };
            }
            $event->gallery = json_encode($gallery);
            $event->update();
            $bookings = Booking::where('event_id', $event->id)->get();
            foreach ($bookings as $item) {
                $item->event_name = $event->event_name;
                $item->event_type = $event->event_type;
                $item->update();
            }
            Storage::disk('s3')->deleteDirectory('temp_'.$user->id);
            $event = DB::table('events')
                ->join('users', 'events.user_id', '=', 'users.id')
                ->where('events.user_id', $user->id)
                ->where('events.id', $id)
                ->select('users.name', 'events.*')
                ->first();
            return response()->json([
                'event' => $event,
                'message' => 'Event has been updated'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    // public function GetStatistics() {
    //     // $stats_start = Carbon::today()->subDays(7)->toDateTimeString();
    //     // $stats_end = Carbon::today()->addDays(1)->toDateTimeString();
    //     $booking = Booking::all();
    //     $guides = User::where('role', 'guide')
    //     return response()->json([

    //     ], 200)
    // }
    public function destroy(string $id)
    {
        try {
            $event = Event::findOrFail($id);
            if(isset($event->gallery)) {
                foreach (json_decode($event->gallery) as $key) {
                    $split = explode("/", $key);
                    $image = end($split);
                    if (Storage::disk('s3')->exists('images/'.$image)) {
                        Storage::disk('s3')->delete('images/'.$image);
                    }
                }
            }
            $bookings = Booking::where('event_id', $id)->get();
            if(!empty($bookings)) {
                foreach ($bookings as $value) {
                    $value->delete();
                }
            }
            $event->delete();
            return response()->json([
                'id' => $id,
                'message' => 'Event is deleted'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
}
