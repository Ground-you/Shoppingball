<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicUpload
{
    public static function store(UploadedFile $file, string $folder): string
    {
        if (! in_array($folder, ['products', 'stores'], true)) {
            throw new \InvalidArgumentException('업로드 폴더가 올바르지 않습니다.');
        }

        $extension = strtolower($file->guessExtension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw ValidationException::withMessages([
                'images' => '이미지는 jpg, png, webp, gif만 올릴 수 있습니다.',
            ]);
        }

        $directory = public_path('uploads/'.$folder);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);

        return '/uploads/'.$folder.'/'.$filename;
    }

    public static function delete(?string $url): void
    {
        if (! is_string($url) || ! str_starts_with($url, '/uploads/')) {
            return;
        }

        $root = realpath(public_path('uploads'));
        $path = public_path(ltrim($url, '/'));
        $real = realpath($path);

        if ($root && $real && is_file($real) && str_starts_with($real, $root)) {
            unlink($real);
        }
    }
}
