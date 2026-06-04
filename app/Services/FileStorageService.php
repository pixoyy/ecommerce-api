<?php

namespace App\Services;

use App\Models\FileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileStorageService
{
    public function upload(UploadedFile $file, string $path = 'uploads'): FileStorage
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($path, $filename, 'public');

        return FileStorage::create([
            'link' => $filePath,
        ]);
    }
}
