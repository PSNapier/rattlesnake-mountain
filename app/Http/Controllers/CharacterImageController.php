<?php

namespace App\Http\Controllers;

use App\Http\Requests\CharacterImageUploadRequest;
use App\Models\CharacterImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CharacterImageController extends Controller
{
    public function index(): Response
    {
        $images = Auth::user()->characterImages()
            ->latest()
            ->get()
            ->map(function ($image) {
                // Generate URLs using the image serving route
                $baseUrl = config('app.url');
                $filename = basename($image->storage_path);
                $thumbnailFilename = str_replace('.webp', '_thumb.webp', $filename);
                
                $url = route('character-images.serve', $filename);
                
                // Check if thumbnail exists, if not use main image
                $thumbnailPath = storage_path('app/public/character-images/' . $thumbnailFilename);
                $thumbnailUrl = file_exists($thumbnailPath) ? route('character-images.serve', $thumbnailFilename) : $url;
                
                return [
                    'id' => $image->id,
                    'filename' => $image->filename,
                    'storage_path' => $image->storage_path,
                    'mime_type' => $image->mime_type,
                    'file_size' => $image->file_size,
                    'width' => $image->width,
                    'height' => $image->height,
                    'alt_text' => $image->alt_text,
                    'description' => $image->description,
                    'is_public' => $image->is_public,
                    'created_at' => $image->created_at,
                    'url' => $url,
                    'thumbnail_url' => $thumbnailUrl,
                ];
            });

        return Inertia::render('Dashboard', [
            'characterImages' => $images,
        ]);
    }

    public function store(CharacterImageUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Generate unique filename
            $filename = Str::random(40).'.webp';
            $thumbnailFilename = str_replace('.webp', '_thumb.webp', $filename);

            // Create image manager
            $manager = new ImageManager(new Driver);

            // Process and store main image
            $image = $manager->read($file);
            $width = $image->width();
            $height = $image->height();

            // Resize if too large (max 1200x1200)
            if ($width > 1200 || $height > 1200) {
                $image->scaleDown(1200);
            }

            // Convert to WebP and store
            $webpData = $image->toWebp(85);
            Storage::disk('public')->put('character-images/'.$filename, $webpData);

            // Create thumbnail
            $thumbnail = $manager->read($file);
            $thumbnail->scaleDown(300);
            $thumbnailWebp = $thumbnail->toWebp(80);
            Storage::disk('public')->put('character-images/'.$thumbnailFilename, $thumbnailWebp);

            // Store in database
            $characterImage = CharacterImage::create([
                'user_id' => Auth::id(),
                'filename' => $originalName,
                'storage_path' => 'character-images/'.$filename,
                'mime_type' => 'image/webp',
                'file_size' => Storage::disk('public')->size('character-images/'.$filename),
                'width' => $image->width(),
                'height' => $image->height(),
                'alt_text' => $request->input('alt_text'),
                'description' => $request->input('description'),
                'is_public' => false,
            ]);

            return response()->json([
                'success' => true,
                'image' => $characterImage->fresh(),
                'message' => 'Image uploaded successfully!',
            ]);

        } catch (\Exception $e) {
            // Clean up any uploaded files on error
            if (isset($filename) && Storage::disk('public')->exists('character-images/'.$filename)) {
                Storage::disk('public')->delete('character-images/'.$filename);
            }
            if (isset($thumbnailFilename) && Storage::disk('public')->exists('character-images/'.$thumbnailFilename)) {
                Storage::disk('public')->delete('character-images/'.$thumbnailFilename);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image. Please try again.',
            ], 500);
        }
    }

    public function destroy(CharacterImage $characterImage): JsonResponse
    {
        if ($characterImage->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            // Delete files from storage
            Storage::disk('public')->delete($characterImage->storage_path);

            // Delete thumbnail if exists
            $thumbnailPath = str_replace('.webp', '_thumb.webp', $characterImage->storage_path);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            // Soft delete from database
            $characterImage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image. Please try again.',
            ], 500);
        }
    }
}
