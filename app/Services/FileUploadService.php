<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function upload(UploadedFile $file, string $folder = 'uploads'): string
    {
        // Create a unique file name
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

        // Store file in the given folder on 'public' disk
        $path = $file->storeAs($folder, $filename, 'public');

        // Return full URL including domain
        return Storage::disk('public')->url($path);
    }

    /*************************************************************************************/
    public function deleteByUrl(string $url): bool
    {
        // Get base url from config/app URL
        $baseUrl = config('app.url');

        // Remove the base URL part from the full URL to get relative path
        $relativePath = str_replace($baseUrl . '/storage/', '', $url);

        // Delete file from 'public' disk storage
        return Storage::disk('public')->delete($relativePath);
    }
}
