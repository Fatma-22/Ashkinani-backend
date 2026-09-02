<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Upload an image/file and return the path
     */
    public function upload(UploadedFile $file, string $folder = 'uploads'): string
    {
        // Preserve a readable filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($originalName) . '_' . time() . '.' . $extension;
        
        $path = $file->storeAs($folder, $filename, 'public');

        return $path;
    }

    /**
     * Get the full URL for a given path
     */
    public function getFullUrl(?string $path): ?string
    {
        if (!$path)
            return null;
        
        if (filter_var($path, FILTER_VALIDATE_URL))
            return $path;

        return asset('storage/' . $path);
    }

    /**
     * Delete a file from storage
     */
    public function delete(?string $path): bool
    {
        if (!$path)
            return false;

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
