<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CekQueryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cek:query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Storage::disk('public')->makeDirectory('lapak-profiles');
        $profileImage = fake()->image(
            storage_path('app/public/lapak-profiles'),
            640,
            480,
            null,
            false
        );
        if (! $profileImage) {
            $profileImage = Str::uuid() . '.png';
            $fullPath = storage_path('app/public/lapak-profiles/' . $profileImage);

            if (function_exists('imagecreatetruecolor') && function_exists('imagepng')) {
                $image = imagecreatetruecolor(640, 480);
                $bg = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));
                imagefilledrectangle($image, 0, 0, 640, 480, $bg);
                imagepng($image, $fullPath);
                imagedestroy($image);
            } else {
                Storage::disk('public')->put('lapak-profiles/' . $profileImage, '');
            }
        }

        dd($profileImage);
    }
}
