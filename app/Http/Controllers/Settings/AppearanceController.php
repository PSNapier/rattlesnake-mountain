<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppearanceController extends Controller
{
    /**
     * Upload user avatar.
     */
    public function uploadAvatar(AvatarUploadRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $file = $request->file('avatar');

            // Delete old avatar if exists
            if ($user->avatar) {
                $urlPath = parse_url($user->avatar, PHP_URL_PATH);
                // Handle both old format (/storage/avatars/...) and new format (/avatars/...)
                $oldPath = str_replace(['/storage/', '/avatars/'], '', $urlPath);
                $oldPath = 'avatars/'.$oldPath;
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Generate unique filename
            $filename = Str::random(40).'.webp';

            // Create image manager
            $manager = new ImageManager(new Driver);

            // Process and store image
            $image = $manager->read($file);
            $width = $image->width();
            $height = $image->height();

            // Resize to square and max 400x400 for avatars
            $size = min($width, $height, 400);
            $image->cover($size, $size);

            // Convert to WebP and store
            $webpData = $image->toWebp(85);
            Storage::disk('public')->put('avatars/'.$filename, $webpData);

            // Update user avatar URL using the route
            $avatarUrl = route('avatars.serve', $filename);
            $user->update(['avatar' => $avatarUrl]);

            return response()->json([
                'success' => true,
                'avatar' => $avatarUrl,
                'message' => 'Avatar uploaded successfully!',
            ]);

        } catch (\Exception $e) {
            // Clean up any uploaded files on error
            if (isset($filename) && Storage::disk('public')->exists('avatars/'.$filename)) {
                Storage::disk('public')->delete('avatars/'.$filename);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user->avatar) {
                return response()->json([
                    'success' => false,
                    'message' => 'No avatar to delete.',
                ], 400);
            }

            // Delete file from storage
            $urlPath = parse_url($user->avatar, PHP_URL_PATH);
            // Handle both old format (/storage/avatars/...) and new format (/avatars/...)
            $path = str_replace(['/storage/', '/avatars/'], '', $urlPath);
            $path = 'avatars/'.$path;
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Update user avatar to null
            $user->update(['avatar' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete avatar. Please try again.',
            ], 500);
        }
    }
}
