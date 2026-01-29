<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Common\StoreUploadRequest;

class UploadController extends Controller
{
    public function store(StoreUploadRequest $request)
    {
        // Validation is handled by StoreUploadRequest

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads', 'public');
            return response()->json([
                'url' => '/storage/' . $path,
                'path' => $path
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }
}
