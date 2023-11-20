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
        $this->middleware('auth:api', ['except' => ['tempPDFUpload', 'deleteUploadedFIle']]);
    }
    public function tempUpload(Request $request)
    {
        if (!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        $user = auth()->user();
        $path = $request->file('file')->store(
            'temp_'.$user->id,
            's3'
        );
        return response()->json($path, 200);
    }
    public function tempPDFUpload(Request $request) {
        $request->validate([
            'file' => 'mimes:pdf,doc,docx,jpeg,png,jpg|max:2048',
        ]);
        $path = $request->file('file')->store(
            'docs',
            's3'
        );
        return response()->json($path, 200);
    }
    // public function setTempUpdate(Request $request) {
    //     if (!JWTAuth::parseToken()->authenticate()) {
    //         return response()->json(['status' => 'User not found!'], 404);
    //     }
    //     $id = auth()->user()->id;
    //     $image = $request->image;
    //     if($image) {
    //         if (Storage::disk('s3')->exists($image)) {
    //             Storage::disk('s3')->copy($image, 'temp_'.$id.'/'.$image);
    //         };
    //     }
    //     return response()->json('temp_'.$id.'/'.$image, 200);
    // }
    public function deleteTempImage() {
        //delete from folder
        $user = auth()->user();
        Storage::disk('s3')->deleteDirectory('temp_'.$user->id);
        return response()->json('success', 200);
    }
    public function deleteUploadedFIle(Request $request) {
        if (Storage::disk('s3')->exists($request->file)) {
            Storage::disk('s3')->delete($request->file);
            return response()->json('File deleted', 200);
        } else {
            return response()->json('File not found.', 200);
        }
    }
    public function deleteGalleryImage(Request $request) {
        $id = auth()->user()->id;
        $path = $request->file;
        $split = explode("/", $path);
        $image = end($split);
        if (Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->copy($path, 'temp_'.$id.'/'.$image);
            return response()->json('File deleted', 200);
        } else {
            return response()->json('File not found.', 200);
        }
    }
    public function destroy(string $id)
    {
        //
    }
}
