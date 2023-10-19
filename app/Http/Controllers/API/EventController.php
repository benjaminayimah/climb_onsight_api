<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

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
            //code...
            $user = auth()->user();
            $event = new Event();
            $event->user_id = $user->id;
            $event->event_name = $request->event_name;
            $event->start_time = $request->start_time;
            $event->end_time = $request->end_time;
            $event->date = $request->date;
            $event->price = $request->price;
            $event->gallery = json_encode($request->gallery);
            $event->latitude = $request->latitude;
            $event->longitude = $request->longitude;
            $event->address = $request->address;
            $event->category = $request->category;
            $event->attendance_limit = $request->attendance_limit;
            $event->gears = json_encode($request->gears);
            $event->itinerary = $request->itinerary;
            $event->event_description = $request->event_description;
            $event->save();
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
    public function update(Request $request, string $id)
    {
        //
    }
    public function destroy(string $id)
    {
        //
    }
}
