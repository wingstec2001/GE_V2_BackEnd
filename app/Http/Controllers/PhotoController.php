<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
class PhotoController extends Controller
{
    public function Add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'img' => 'required|mimes:jpg,jpeg,bmp,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // path = /img/xxx.ext
        $path = '/' . $request->file('img')->store('/images');
        return $this->success($path);
    }
    public function Delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'img' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $img = $request->img;
        if(!Storage::exists($img)){
            $message = "img: $img が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }
        Storage::delete($img);
        return $this->success('delete success');
    }
}