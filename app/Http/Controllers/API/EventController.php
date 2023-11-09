<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
            $repeat_at = null;
            if ($request->repeat) {
                $repeat_at = $request->repeat_at;
            }
            $user = auth()->user();
            $event = new Event();
            $event->user_id = $user->id;
            $event->event_name = $request->event_name;
            $event->start_date = $request->start_date;
            $event->end_date = $request->end_date;
            $event->start_time = $request->start_time;
            $event->price = $request->price;
            $event->latitude = $request->latitude;
            $event->longitude = $request->longitude;
            $event->address = $request->address;
            $event->category = $request->category;
            $event->attendance_limit = $request->attendance_limit;
            $event->gears = json_encode($request->gears);
            $event->faqs = json_encode($request->faqs);
            $event->itinerary = $request->itinerary;
            $event->event_description = $request->event_description;
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
                'data' => $event,
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
        $guides = DB::table('users')
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->where('role', 'guide')
                    ->where('is_approved', true);
            })
            ->get();
        return response()->json([
            'events' => $events,
            'guides' => $guides
        ], 200);
    }
    public function GetNearByEvents(Request $request) 
    {
        $events = array();
        $guides = [];
        $userLat = $request['lat'];
        $userLng = $request['lng'];
        $radius = $request['rad']; // Search within a 100km radius
        $events = DB::table('events')
            ->select('*')
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?', [$userLat, $userLng, $userLat, $radius])
            ->orderBy('id', 'DESC')
            ->get();
        if(isset($events)) {
            foreach ($events as $event) {
                $guides[] = DB::table('users')
                ->where('id', $event->user_id)
                ->first();
            }
        }
        return response()->json([
            'events' => $events,
            'guides' => $guides
        ], 200);
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
