<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\File;


class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        if (!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
    }
    public function tempUpload(Request $request)
    {
        $user = auth()->user();
        $path = $request->file('file')->store(
            'temp_'.$user->id,
            's3'
        );
        return response()->json($path, 200);
    }
    // public function setTempUpdate(Request $request) {
    //     if (! $user = JWTAuth::parseToken()->authenticate()) {
    //         return response()->json(['status' => 'User not found!'], 404);
    //     }
    //     $id = $user->id;
    //     $image = $request->image;
    //     if (!Storage::directories('public/'.$id.'/temp')) {
    //         Storage::makeDirectory('public/'.$id.'/temp');
    //     }
    //     if (!Storage::disk('public')->exists($id.'/temp'.'/'.$image)) {
    //         Storage::disk('public')->copy($id.'/'.$image, $id.'/temp'.'/'.$image);
    //     };
    //     return response()->json([
    //         'status' => 'success',
    //         'image' => $image
    //     ], 200);
    // }
    public function deleteTempImage() {
        //delete from folder
        $user = auth()->user();
        Storage::disk('s3')->deleteDirectory('temp_'.$user->id);
        return response()->json('success', 200);
    }
}
