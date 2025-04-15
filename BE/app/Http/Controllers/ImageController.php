<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use App\Models\User;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    public function index(User $user)
    {
        $images = $user->images()->get()->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => $image->url, 
                'path_name' => $image->path_name,
                'created_at' => $image->created_at
            ];
        });

        return response()->json([
            'images' => $images
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'images' => 'required',
            'images.*' => 'image|max:5120', // 5MB
        ]);

        $images = $this->imageService->uploadImages(
            $request->file('images'),
            $user
        );

        return response()->json([
            'message' => 'Images are being processed',
            'images' => $images
        ], 202);
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:images,id,user_id,' . $user->id
        ]);

        $this->imageService->deleteImages($request->input('image_ids'));

        return response()->json([
            'message' => 'Images are being deleted'
        ], 202);
    }
}
