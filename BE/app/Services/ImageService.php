<?php

namespace App\Services;

use App\Models\Image;
use App\Models\User;
use App\Jobs\UploadImageToS3;
use App\Jobs\DeleteImageFromS3;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Handle both single and multiple file uploads
     *
     * @param UploadedFile|array $files
     * @param User $user
     * @return array
     */
    public function uploadImages($files, User $user): array
    {
        $uploadedImages = [];

        // Convert single file to array if needed
        foreach (Arr::wrap($files) as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $s3Path = $this->generateS3Path($file, $user->id);
            $tempPath = $file->store('temp', 'public');

            $imageRecord = Image::create([
                'user_id' => $user->id,
                'path_name' => $s3Path
            ]);

            UploadImageToS3::dispatch($tempPath, $s3Path);
            $uploadedImages[] = $imageRecord;
        }

        return $uploadedImages;
    }

    public function uploadImage(UploadedFile $file, User $user): string
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload.');
        }

        $s3Path = $this->generateS3Path($file, $user->id);
        $tempPath = $file->store('temp', 'public');

        // Dispatch the job to upload the image to S3
        UploadImageToS3::dispatch($tempPath, $s3Path);

        return $s3Path;
    }

    public function deleteImage(array $imagePaths): void
    {
        // Dispatch the job to delete the images from S3
        DeleteImageFromS3::dispatch($imagePaths);
    }

    public function deleteImages(array $imageIds): void
    {
        $images = Image::whereIn('id', $imageIds)->get();

        if ($images->isNotEmpty()) {
            $paths = $images->pluck('path_name')->toArray();

            DeleteImageFromS3::dispatch($paths);

            Image::whereIn('id', $imageIds)->delete();
        }
    }

    private function generateS3Path(UploadedFile $image, int $userId): string
    {
        $timestamp = now()->timestamp;
        $originalNameWithoutExt = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $image->getClientOriginalExtension();

        return sprintf(
            'users/%d/%s-%s.%s',
            $userId,
            $timestamp,
            Str::slug($originalNameWithoutExt),
            $extension
        );
    }
}
